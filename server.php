<?php
/**
 * Servidor WebSocket para Plataforma de Jogos Multiplayer
 * 
 * MVP com Ratchet (ReactPHP)
 * Gerencia salas, movimentos em tempo real e quiz
 * Persiste dados no MySQL via PDO
 * Configurações lidas do arquivo .env
 * 
 * Uso: php server.php
 * Escuta na porta definida no .env (padrão: 8080)
 */

require __DIR__ . '/vendor/autoload.php';

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use Dotenv\Dotenv;

// Carrega configurações do .env
if (file_exists(__DIR__ . '/.env')) {
    $dotenv = Dotenv::createImmutable(__DIR__);
    $dotenv->load();
    $dotenv->required(['DB_HOST', 'DB_PORT', 'DB_NAME', 'DB_USER']);
}

require_once __DIR__ . '/config/database.php';

/**
 * GameServer - Núcleo do backend multiplayer
 */
class GameServer implements MessageComponentInterface
{
    /** @var \SplObjectStorage Todas as conexões ativas */
    protected \SplObjectStorage $clients;

    /** @var array<string, array> Salas em memória: roomId => [players => [connId => [name, x, y, score]], host => connId] */
    protected array $rooms;

    /** @var array<int, string> Mapa conexão -> roomId */
    protected array $connectionRoom;

    /** @var array<int, string> Mapa conexão -> playerName */
    protected array $connectionName;

    public function __construct()
    {
        $this->clients = new \SplObjectStorage();
        $this->rooms = [];
        $this->connectionRoom = [];
        $this->connectionName = [];
        echo "[INICIO] Servidor WebSocket de Jogos Multiplayer iniciado.\n";
    }

    public function onOpen(ConnectionInterface $conn): void
    {
        $this->clients->attach($conn);
        $connId = (int) $conn->resourceId;
        echo "[CONEXAO] Nova conexão: #{$connId}\n";
    }

    public function onMessage(ConnectionInterface $from, $msg): void
    {
        $data = json_decode($msg, true);
        if (!$data || !isset($data['type'])) {
            $from->send(json_encode([
                'type'    => 'error',
                'message' => 'Formato inválido. Envie um JSON com campo "type".'
            ]));
            return;
        }

        switch ($data['type']) {
            case 'join_room':
                $this->handleJoinRoom($from, $data);
                break;
            case 'send_movement':
                $this->handleMovement($from, $data);
                break;
            case 'submit_answer':
                $this->handleSubmitAnswer($from, $data);
                break;
            case 'leave_room':
                $this->handleLeaveRoom($from);
                break;
            default:
                $from->send(json_encode([
                    'type'    => 'error',
                    'message' => "Tipo de evento desconhecido: {$data['type']}"
                ]));
        }
    }

    public function onClose(ConnectionInterface $conn): void
    {
        $this->clients->detach($conn);
        $this->handleLeaveRoom($conn);
        echo "[DESCONEXAO] Conexão #{$conn->resourceId} encerrada.\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e): void
    {
        echo "[ERRO] #{$conn->resourceId}: {$e->getMessage()}\n";
        $conn->close();
    }

    // ----------------------------------------------------------------
    // HANDLERS DOS EVENTOS
    // ----------------------------------------------------------------

    /**
     * join_room: Entrar em uma sala existente ou criar uma nova
     * Espera: { type: "join_room", roomId: "ABC123", name: "Jogador" }
     */
    private function handleJoinRoom(ConnectionInterface $conn, array $data): void
    {
        $connId = (int) $conn->resourceId;
        $roomId = strtoupper(trim($data['roomId'] ?? ''));
        $name   = trim($data['name'] ?? '');

        // Validações
        if ($roomId === '' || strlen($roomId) > 10) {
            $conn->send(json_encode([
                'type'    => 'error',
                'message' => 'Código da sala inválido (máx 10 caracteres).'
            ]));
            return;
        }
        if ($name === '' || strlen($name) > 20) {
            $conn->send(json_encode([
                'type'    => 'error',
                'message' => 'Nome inválido (máx 20 caracteres).'
            ]));
            return;
        }

        // Remove de outra sala se já estiver
        $this->handleLeaveRoom($conn);

        // Cria sala em memória se não existir
        if (!isset($this->rooms[$roomId])) {
            $this->rooms[$roomId] = [
                'host'    => $connId,
                'players' => []
            ];
            echo "[SALA] Sala '{$roomId}' criada por #{$connId} ({$name}).\n";
        }

        $maxPlayers = 10;
        if (count($this->rooms[$roomId]['players']) >= $maxPlayers) {
            $conn->send(json_encode([
                'type'    => 'error',
                'message' => "Sala cheia (limite de {$maxPlayers} jogadores)."
            ]));
            return;
        }

        // Adiciona jogador à sala em memória
        $playerData = [
            'id'    => $connId,
            'name'  => $name,
            'x'     => rand(100, 700),
            'y'     => rand(100, 500),
            'score' => 0
        ];
        $this->rooms[$roomId]['players'][$connId] = $playerData;
        $this->connectionRoom[$connId] = $roomId;
        $this->connectionName[$connId] = $name;

        echo "[SALA] #{$connId} ({$name}) entrou na sala '{$roomId}'. Total: "
             . count($this->rooms[$roomId]['players']) . "\n";

        // --- Persistência MySQL ---
        $this->persistRoomAndPlayer($roomId, $connId, $name);

        // Confirma para o jogador que entrou
        $conn->send(json_encode([
            'type'       => 'joined_room',
            'roomId'     => $roomId,
            'yourId'     => $connId,
            'players'    => array_values($this->rooms[$roomId]['players'])
        ]));

        // Broadcast para os outros na sala: novo jogador
        $this->broadcastToRoom($roomId, [
            'type'    => 'player_joined',
            'player'  => $playerData
        ], $connId);
    }

    /**
     * send_movement: Atualiza posição do jogador e replica para a sala
     * Espera: { type: "send_movement", x: 400, y: 300 }
     */
    private function handleMovement(ConnectionInterface $conn, array $data): void
    {
        $connId = (int) $conn->resourceId;
        $roomId = $this->connectionRoom[$connId] ?? null;

        if (!$roomId || !isset($this->rooms[$roomId]['players'][$connId])) {
            $conn->send(json_encode([
                'type'    => 'error',
                'message' => 'Você precisa estar em uma sala para enviar movimentos.'
            ]));
            return;
        }

        $x = max(0, min(800, (float)($data['x'] ?? 0)));
        $y = max(0, min(600, (float)($data['y'] ?? 0)));

        $this->rooms[$roomId]['players'][$connId]['x'] = $x;
        $this->rooms[$roomId]['players'][$connId]['y'] = $y;

        // Atualiza posição no MySQL
        $this->updatePlayerPosition($connId, $x, $y);

        // Broadcast do movimento para TODOS na sala
        $this->broadcastToRoom($roomId, [
            'type'   => 'player_moved',
            'player' => [
                'id' => $connId,
                'x'  => $x,
                'y'  => $y
            ]
        ]);
    }

    /**
     * submit_answer: Resposta do quiz (Kahoot-style)
     * Espera: { type: "submit_answer", answer: 2, questionId: 1 }
     */
    private function handleSubmitAnswer(ConnectionInterface $conn, array $data): void
    {
        $connId = (int) $conn->resourceId;
        $roomId = $this->connectionRoom[$connId] ?? null;

        if (!$roomId || !isset($this->rooms[$roomId]['players'][$connId])) {
            $conn->send(json_encode([
                'type'    => 'error',
                'message' => 'Você precisa estar em uma sala para responder.'
            ]));
            return;
        }

        $answer     = $data['answer'] ?? null;
        $questionId = (int)($data['questionId'] ?? 1);

        // Busca a pergunta no MySQL para validar a resposta
        $acertou = false;
        $respostaCerta = null;

        try {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare("SELECT resposta_certa FROM perguntas WHERE id = ?");
            $stmt->execute([$questionId]);
            $pergunta = $stmt->fetch();

            if ($pergunta) {
                $respostaCerta = $pergunta['resposta_certa'];
                $acertou = (strtoupper($answer) === strtoupper($respostaCerta));
            }

            // Registra resposta no MySQL
            $stmt = $pdo->prepare(
                "INSERT INTO respostas (jogador_id, room_id, pergunta_id, resposta, acertou)
                 VALUES (?, ?, ?, ?, ?)"
            );
            $stmt->execute([
                $this->connectionName[$connId] ?? 'unknown',
                $roomId,
                $questionId,
                strtoupper($answer),
                $acertou ? 1 : 0
            ]);
        } catch (\Exception $e) {
            echo "[ERRO MySQL] submit_answer: {$e->getMessage()}\n";
        }

        // Calcula pontuação
        $scoreGanho = $acertou ? 100 : 0;
        $this->rooms[$roomId]['players'][$connId]['score'] += $scoreGanho;

        echo "[QUIZ] #{$connId} respondeu questão #{$questionId} com '{$answer}'. "
             . ($acertou ? "ACERTOU!" : "ERROU (certa: {$respostaCerta})")
             . " Score: {$scoreGanho}\n";

        // Notifica o jogador sobre sua resposta
        $conn->send(json_encode([
            'type'        => 'answer_result',
            'correct'     => $acertou,
            'correctAnswer' => $respostaCerta,
            'score'       => $scoreGanho,
            'totalScore'  => $this->rooms[$roomId]['players'][$connId]['score']
        ]));

        // Broadcast de placar atualizado para a sala
        $this->broadcastToRoom($roomId, [
            'type'    => 'scoreboard_update',
            'players' => array_map(function ($p) {
                return ['id' => $p['id'], 'name' => $p['name'], 'score' => $p['score']];
            }, $this->rooms[$roomId]['players'])
        ]);
    }

    /**
     * leave_room: Remove jogador da sala atual
     */
    private function handleLeaveRoom(ConnectionInterface $conn): void
    {
        $connId = (int) $conn->resourceId;
        $roomId = $this->connectionRoom[$connId] ?? null;

        if (!$roomId || !isset($this->rooms[$roomId])) {
            return;
        }

        // Marca jogador como offline no MySQL
        $this->setPlayerOffline($connId);

        unset($this->rooms[$roomId]['players'][$connId]);
        unset($this->connectionRoom[$connId]);
        unset($this->connectionName[$connId]);

        echo "[SALA] #{$connId} saiu da sala '{$roomId}'.\n";

        // Notifica os outros jogadores
        $this->broadcastToRoom($roomId, [
            'type'     => 'player_left',
            'playerId' => $connId
        ]);

        // Se a sala ficou vazia, remove
        if (empty($this->rooms[$roomId]['players'])) {
            unset($this->rooms[$roomId]);
            echo "[SALA] Sala '{$roomId}' removida (vazia).\n";
        } elseif ($this->rooms[$roomId]['host'] === $connId) {
            // Passa host para próximo jogador
            $remaining = array_keys($this->rooms[$roomId]['players']);
            if (!empty($remaining)) {
                $newHost = $remaining[0];
                $this->rooms[$roomId]['host'] = $newHost;
                echo "[SALA] Novo host da sala '{$roomId}': #{$newHost}\n";
                $this->broadcastToRoom($roomId, [
                    'type'   => 'new_host',
                    'hostId' => $newHost
                ]);
            }
        }
    }

    // ----------------------------------------------------------------
    // PERSISTÊNCIA MySQL
    // ----------------------------------------------------------------

    /**
     * Salva/atualiza a sala e o jogador no MySQL
     */
    private function persistRoomAndPlayer(string $roomId, int $connId, string $name): void
    {
        try {
            $pdo = getDBConnection();

            // Upsert da sala
            $stmt = $pdo->prepare(
                "INSERT INTO salas (room_id, host_name, status)
                 VALUES (?, ?, 'waiting')
                 ON DUPLICATE KEY UPDATE updated_at = NOW()"
            );
            $stmt->execute([$roomId, $name]);

            // Insere jogador
            $x = $this->rooms[$roomId]['players'][$connId]['x'] ?? 400;
            $y = $this->rooms[$roomId]['players'][$connId]['y'] ?? 300;
            $stmt = $pdo->prepare(
                "INSERT INTO jogadores (room_id, conn_id, nome, pos_x, pos_y, is_online)
                 VALUES (?, ?, ?, ?, ?, 1)
                 ON DUPLICATE KEY UPDATE is_online = 1, nome = VALUES(nome), ultima_acao = NOW()"
            );
            $stmt->execute([$roomId, $connId, $name, $x, $y]);

            echo "[MySQL] Jogador '{$name}' persistido na sala '{$roomId}'.\n";
        } catch (\Exception $e) {
            echo "[ERRO MySQL] persistRoomAndPlayer: {$e->getMessage()}\n";
        }
    }

    /**
     * Atualiza a posição do jogador no MySQL
     */
    private function updatePlayerPosition(int $connId, float $x, float $y): void
    {
        try {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare(
                "UPDATE jogadores SET pos_x = ?, pos_y = ?, ultima_acao = NOW()
                 WHERE conn_id = ?"
            );
            $stmt->execute([$x, $y, $connId]);
        } catch (\Exception $e) {
            // Falha silenciosa para não travar o jogo
        }
    }

    /**
     * Marca jogador como offline no MySQL
     */
    private function setPlayerOffline(int $connId): void
    {
        try {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare(
                "UPDATE jogadores SET is_online = 0, ultima_acao = NOW()
                 WHERE conn_id = ?"
            );
            $stmt->execute([$connId]);
        } catch (\Exception $e) {
            // Falha silenciosa
        }
    }

    // ----------------------------------------------------------------
    // UTILITÁRIOS
    // ----------------------------------------------------------------

    /**
     * Envia uma mensagem JSON para todos os jogadores em uma sala
     */
    private function broadcastToRoom(string $roomId, array $data, ?int $excludeConnId = null): void
    {
        if (!isset($this->rooms[$roomId])) {
            return;
        }

        $payload = json_encode($data);

        foreach ($this->rooms[$roomId]['players'] as $playerId => $player) {
            if ($excludeConnId !== null && $playerId === $excludeConnId) {
                continue;
            }
            foreach ($this->clients as $client) {
                if ((int) $client->resourceId === $playerId) {
                    $client->send($payload);
                    break;
                }
            }
        }
    }
}

// ----------------------------------------------------------------
// INICIALIZAÇÃO DO SERVIDOR
// ----------------------------------------------------------------
$host = getenv('WS_HOST') ?: '0.0.0.0';
$port = (int)(getenv('WS_PORT') ?: 8080);

$dbHost = getenv('DB_HOST') ?: 'localhost';
$dbPort = getenv('DB_PORT') ?: '3306';
$dbName = getenv('DB_NAME') ?: 'plataforma_jogos';

echo "================================================\n";
echo "  Plataforma de Jogos - WebSocket Server\n";
echo "  Endereço: {$host}:{$port}\n";
echo "  MySQL:    {$dbHost}:{$dbPort}/{$dbName}\n";
echo "  Pressione Ctrl+C para parar\n";
echo "================================================\n";

$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            new GameServer()
        )
    ),
    $port,
    $host
);

$server->run();