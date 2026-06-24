<?php
/**
 * API: salvar_score_acao.php
 * 
 * Recebe a pontuação final do mini-game de ação (navinha/snake)
 * quando o aluno termina a partida e salva no banco.
 * 
 * Uso (POST):
 *   api/salvar_score_acao.php
 *   Body: { "room_id": "ABC123", "jogador_id": "jogador_42", "score": 1500 }
 * 
 * Retorno (JSON):
 *   { "sucesso": true, "score_acao": 1500, "total": 1500 }
 */

require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['erro' => 'Método não permitido. Use POST.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

$roomId    = isset($input['room_id'])    ? trim($input['room_id'])           : '';
$jogadorId = isset($input['jogador_id']) ? trim($input['jogador_id'])        : '';
$score     = isset($input['score'])      ? max(0, (int) $input['score'])     : 0;

if ($roomId === '' || $jogadorId === '') {
    http_response_code(400);
    echo json_encode(['erro' => 'Campos obrigatórios: room_id, jogador_id, score.']);
    exit;
}

try {
    $pdo = getDBConnection();

    // Verifica se sala existe
    $stmt = $pdo->prepare("SELECT id FROM salas WHERE room_id = ?");
    $stmt->execute([$roomId]);
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode(['erro' => 'Sala não encontrada.']);
        exit;
    }

    // Se o jogador não existe, cria registro
    $stmt = $pdo->prepare("SELECT id FROM jogadores WHERE jogador_id = ? AND room_id = ?");
    $stmt->execute([$jogadorId, $roomId]);
    $jogador = $stmt->fetch();

    if (!$jogador) {
        // Cria jogador automaticamente (para o caso de vir direto do mini-game)
        $stmt = $pdo->prepare(
            "INSERT INTO jogadores (room_id, jogador_id, nome, score_acao)
             VALUES (?, ?, ?, ?)"
        );
        $stmt->execute([$roomId, $jogadorId, 'Jogador#' . substr($jogadorId, -4), $score]);
    } else {
        // Atualiza score_acao (soma se já existir)
        $stmt = $pdo->prepare(
            "UPDATE jogadores SET score_acao = score_acao + ? WHERE jogador_id = ? AND room_id = ?"
        );
        $stmt->execute([$score, $jogadorId, $roomId]);
    }

    // Busca scores atualizados
    $stmt = $pdo->prepare("SELECT score_quiz, score_acao FROM jogadores WHERE jogador_id = ? AND room_id = ?");
    $stmt->execute([$jogadorId, $roomId]);
    $jogadorAtual = $stmt->fetch();

    echo json_encode([
        'sucesso'     => true,
        'score_acao'  => (int) $jogadorAtual['score_acao'],
        'score_quiz'  => (int) $jogadorAtual['score_quiz'],
        'total'       => (int) $jogadorAtual['score_quiz'] + (int) $jogadorAtual['score_acao'],
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['erro' => 'Erro interno: ' . $e->getMessage()]);
}