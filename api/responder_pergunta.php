<?php
/**
 * API: responder_pergunta.php
 * 
 * Recebe a resposta do aluno no quiz, valida contra o gabarito
 * e atualiza a pontuação no banco.
 * 
 * Uso (POST):
 *   api/responder_pergunta.php
 *   Body: { "room_id": "ABC123", "jogador_id": "jogador_42",
 *           "pergunta_id": 1, "resposta": "B" }
 * 
 * Retorno (JSON):
 *   { "acertou": true, "resposta_certa": "B", "score_quiz": 100 }
 */

require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Responde preflight CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['erro' => 'Método não permitido. Use POST.']);
    exit;
}

// Lê o body JSON
$input = json_decode(file_get_contents('php://input'), true);

$roomId     = isset($input['room_id'])     ? trim($input['room_id'])     : '';
$jogadorId  = isset($input['jogador_id'])  ? trim($input['jogador_id'])  : '';
$perguntaId = isset($input['pergunta_id']) ? (int) $input['pergunta_id'] : 0;
$resposta   = isset($input['resposta'])    ? strtoupper(trim($input['resposta'])) : '';

if ($roomId === '' || $jogadorId === '' || $perguntaId <= 0 || $resposta === '') {
    http_response_code(400);
    echo json_encode(['erro' => 'Campos obrigatórios: room_id, jogador_id, pergunta_id, resposta.']);
    exit;
}

if (!in_array($resposta, ['A', 'B', 'C', 'D'])) {
    http_response_code(400);
    echo json_encode(['erro' => 'Resposta inválida. Use A, B, C ou D.']);
    exit;
}

try {
    $pdo = getDBConnection();

    // 1. Busca a pergunta e o gabarito
    $stmt = $pdo->prepare("SELECT id, resposta_certa FROM perguntas WHERE id = ?");
    $stmt->execute([$perguntaId]);
    $pergunta = $stmt->fetch();

    if (!$pergunta) {
        http_response_code(404);
        echo json_encode(['erro' => 'Pergunta não encontrada.']);
        exit;
    }

    $respostaCerta = $pergunta['resposta_certa'];
    $acertou = ($resposta === $respostaCerta);
    $pontos = $acertou ? 100 : 0;

    // 2. Registra a resposta (evita duplicidade com INSERT IGNORE)
    $stmt = $pdo->prepare(
        "INSERT IGNORE INTO respostas (jogador_id, room_id, pergunta_id, resposta, acertou)
         VALUES (?, ?, ?, ?, ?)"
    );
    $stmt->execute([$jogadorId, $roomId, $perguntaId, $resposta, $acertou ? 1 : 0]);

    $linhasAfetadas = $stmt->rowCount();

    // 3. Se foi inserido (primeira vez), atualiza score do jogador
    if ($linhasAfetadas > 0 && $acertou) {
        $stmt = $pdo->prepare(
            "UPDATE jogadores SET score_quiz = score_quiz + ? WHERE jogador_id = ? AND room_id = ?"
        );
        $stmt->execute([$pontos, $jogadorId, $roomId]);
    }

    // 4. Busca score atualizado
    $stmt = $pdo->prepare("SELECT score_quiz, score_acao FROM jogadores WHERE jogador_id = ? AND room_id = ?");
    $stmt->execute([$jogadorId, $roomId]);
    $jogador = $stmt->fetch();

    // 5. Resposta
    echo json_encode([
        'acertou'        => $acertou,
        'resposta_certa' => $respostaCerta,
        'pontos_ganhos'  => $linhasAfetadas > 0 ? $pontos : 0,
        'score_quiz'     => $jogador ? (int) $jogador['score_quiz'] : 0,
        'ja_respondida'  => $linhasAfetadas === 0,
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['erro' => 'Erro interno: ' . $e->getMessage()]);
}