<?php
/**
 * API: obter_status.php
 * 
 * O cliente (aluno) chama esta API a cada ~1.5s (polling)
 * para saber o estado atual da sala e se deve exibir
 * quiz, mini-game ou tela de espera.
 * 
 * Uso: api/obter_status.php?room_id=ABC123&jogador_id=jogador_42
 * 
 * Retorno (JSON):
 * {
 *   "status": "aguardando|pergunta_1|...|minigame|fim",
 *   "pergunta": { ... } | null,
 *   "jogador": { "score_quiz": 0, "score_acao": 0 },
 *   "ranking": [ ... ]
 * }
 */

require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

// ---- Valida parâmetros ----
$roomId    = isset($_GET['room_id'])    ? trim($_GET['room_id'])    : '';
$jogadorId = isset($_GET['jogador_id']) ? trim($_GET['jogador_id']) : '';

if ($roomId === '' || $jogadorId === '') {
    http_response_code(400);
    echo json_encode(['erro' => 'Parâmetros room_id e jogador_id são obrigatórios.']);
    exit;
}

try {
    $pdo = getDBConnection();

    // 1. Busca a sala
    $stmt = $pdo->prepare("SELECT * FROM salas WHERE room_id = ?");
    $stmt->execute([$roomId]);
    $sala = $stmt->fetch();

    if (!$sala) {
        http_response_code(404);
        echo json_encode(['erro' => 'Sala não encontrada.', 'room_id' => $roomId]);
        exit;
    }

    // 2. Busca dados do jogador
    $stmt = $pdo->prepare("SELECT * FROM jogadores WHERE jogador_id = ? AND room_id = ?");
    $stmt->execute([$jogadorId, $roomId]);
    $jogador = $stmt->fetch();

    // 3. Monta resposta base
    $resposta = [
        'status'    => $sala['status'],
        'room_id'   => $sala['room_id'],
        'jogador'   => $jogador ? [
            'jogador_id' => $jogador['jogador_id'],
            'nome'       => $jogador['nome'],
            'score_quiz' => (int) $jogador['score_quiz'],
            'score_acao' => (int) $jogador['score_acao'],
        ] : null,
        'pergunta'  => null,
        'ranking'   => [],
    ];

    // 4. Se o status for "pergunta_X", busca a pergunta correspondente
    if (preg_match('/^pergunta_(\d+)$/', $sala['status'], $matches)) {
        $numPergunta = (int) $matches[1];

        $stmt = $pdo->prepare("SELECT id, pergunta, opcao_a, opcao_b, opcao_c, opcao_d
                                FROM perguntas
                                WHERE (room_id = ? OR room_id IS NULL)
                                ORDER BY id
                                LIMIT 1 OFFSET ?");
        $offset = $numPergunta - 1;
        $stmt->execute([$roomId, $offset]);
        $pergunta = $stmt->fetch();

        if ($pergunta) {
            $resposta['pergunta'] = [
                'id'       => (int) $pergunta['id'],
                'pergunta' => $pergunta['pergunta'],
                'a'        => $pergunta['opcao_a'],
                'b'        => $pergunta['opcao_b'],
                'c'        => $pergunta['opcao_c'],
                'd'        => $pergunta['opcao_d'],
            ];
        }
    }

    // 5. Ranking (top 10 da sala)
    $stmt = $pdo->prepare(
        "SELECT nome, (score_quiz + score_acao) AS total
         FROM jogadores
         WHERE room_id = ?
         ORDER BY total DESC
         LIMIT 10"
    );
    $stmt->execute([$roomId]);
    $resposta['ranking'] = $stmt->fetchAll();

    // 6. Verifica se o jogador já respondeu esta pergunta
    if ($resposta['pergunta'] && $jogador) {
        $stmt = $pdo->prepare(
            "SELECT COUNT(*) AS ja_respondeu
             FROM respostas
             WHERE jogador_id = ? AND pergunta_id = ? AND room_id = ?"
        );
        $stmt->execute([$jogadorId, $resposta['pergunta']['id'], $roomId]);
        $row = $stmt->fetch();
        $resposta['ja_respondeu'] = ($row['ja_respondeu'] > 0);
    } else {
        $resposta['ja_respondeu'] = false;
    }

    echo json_encode($resposta);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['erro' => 'Erro interno: ' . $e->getMessage()]);
}