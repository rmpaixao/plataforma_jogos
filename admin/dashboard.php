<?php
/**
 * ADMIN - Dashboard do Professor
 * 
 * Controle de salas: criar, alterar status, ver jogadores
 * 
 * Acesse: /plataforma_jogos/admin/dashboard.php
 */

session_start();
if (!isset($_SESSION['admin_logado']) || $_SESSION['admin_logado'] !== true) {
    header('Location: index.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';

$pdo = getDBConnection();
$mensagem = '';
$erro = '';

// ---- Ações do formulário ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';

    try {
        switch ($acao) {
            case 'criar_sala':
                $roomId   = strtoupper(trim($_POST['room_id'] ?? ''));
                $hostName = trim($_POST['host_name'] ?? 'Professor');
                if ($roomId === '') throw new Exception('Código da sala é obrigatório.');
                if (strlen($roomId) > 10) throw new Exception('Código máximo 10 caracteres.');
                if (strlen($hostName) > 20) throw new Exception('Nome máximo 20 caracteres.');

                $stmt = $pdo->prepare("INSERT INTO salas (room_id, host_name, status) VALUES (?, ?, 'aguardando')");
                $stmt->execute([$roomId, $hostName]);
                $mensagem = "✅ Sala '{$roomId}' criada com sucesso!";
                break;

            case 'alterar_status':
                $roomId = $_POST['room_id'] ?? '';
                $status = $_POST['status'] ?? '';
                $statusValidos = ['aguardando', 'pergunta_1', 'pergunta_2', 'pergunta_3', 'pergunta_4', 'pergunta_5', 'minigame', 'fim'];

                if (!in_array($status, $statusValidos)) throw new Exception('Status inválido.');
                if ($roomId === '') throw new Exception('Sala não informada.');

                $stmt = $pdo->prepare("UPDATE salas SET status = ? WHERE room_id = ?");
                $stmt->execute([$status, $roomId]);
                $mensagem = "✅ Sala '{$roomId}' alterada para '{$status}'!";
                break;

            case 'deletar_sala':
                $roomId = $_POST['room_id'] ?? '';
                if ($roomId === '') throw new Exception('Sala não informada.');

                $stmt = $pdo->prepare("DELETE FROM salas WHERE room_id = ?");
                $stmt->execute([$roomId]);
                $mensagem = "🗑️ Sala '{$roomId}' removida!";
                break;
        }
    } catch (Exception $e) {
        $erro = $e->getMessage();
    }
}

// ---- Busca dados ----
$salas = $pdo->query("SELECT s.*, (SELECT COUNT(*) FROM jogadores WHERE room_id = s.room_id) AS qtd_jogadores FROM salas s ORDER BY s.criada_em DESC")->fetchAll();

$totalSalas   = count($salas);
$totalAlunos  = $pdo->query("SELECT COUNT(*) FROM jogadores")->fetchColumn();
$totalPerguntas = $pdo->query("SELECT COUNT(*) FROM perguntas")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Game Arena Admin</title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body {
            font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;
            background:#0f0c29; color:#fff; min-height:100vh;
        }
        .topo {
            background:linear-gradient(90deg,#f7971e,#ffd200);
            color:#1a1a2e; padding:12px 20px;
            display:flex; justify-content:space-between; align-items:center;
        }
        .topo h1 { font-size:1.3rem; }
        .topo nav a {
            color:#1a1a2e; text-decoration:none; font-weight:600;
            margin-left:15px; padding:4px 10px; border-radius:6px;
            background:rgba(0,0,0,0.1); font-size:0.85rem;
        }
        .topo nav a:hover { background:rgba(0,0,0,0.2); }
        .topo .sair { background:rgba(231,76,60,0.3); }

        .container { max-width:1100px; margin:0 auto; padding:20px; }

        /* Cards de estatísticas */
        .stats { display:flex; gap:15px; margin-bottom:20px; flex-wrap:wrap; }
        .stat-card {
            background:rgba(255,255,255,0.06); border-radius:12px;
            padding:16px 20px; flex:1; min-width:150px; border:1px solid rgba(255,255,255,0.06);
        }
        .stat-card .num { font-size:1.8rem; font-weight:900; color:#ffd200; }
        .stat-card .label { font-size:0.8rem; color:#aaa; margin-top:2px; }

        /* Mensagens */
        .msg { padding:10px 14px; border-radius:8px; margin-bottom:12px; font-size:0.9rem; }
        .msg.sucesso { background:rgba(46,204,113,0.15); color:#2ecc71; border:1px solid rgba(46,204,113,0.3); }
        .msg.erro { background:rgba(231,76,60,0.15); color:#e74c3c; border:1px solid rgba(231,76,60,0.3); }

        /* Cards */
        .card {
            background:rgba(255,255,255,0.04); border-radius:14px;
            padding:20px; margin-bottom:20px; border:1px solid rgba(255,255,255,0.06);
        }
        .card h2 { font-size:1.1rem; margin-bottom:12px; color:#ffd200; }
        .card label { display:block; font-size:0.85rem; color:#aaa; margin-top:10px; margin-bottom:4px; }
        .card input, .card select {
            width:100%; padding:10px 12px; border-radius:8px;
            border:1px solid rgba(255,255,255,0.12); background:rgba(255,255,255,0.05);
            color:#fff; font-size:0.95rem; outline:none;
        }
        .card input:focus, .card select:focus { border-color:#f7971e; }
        .card button {
            margin-top:12px; padding:10px 20px; border:none; border-radius:8px;
            background:linear-gradient(90deg,#f7971e,#ffd200); color:#1a1a2e;
            font-weight:700; font-size:0.9rem; cursor:pointer; transition:transform 0.2s;
        }
        .card button:hover { transform:translateY(-2px); }
        .card .btn-sm { padding:6px 12px; font-size:0.8rem; margin:2px; }
        .card .btn-danger { background:linear-gradient(90deg,#e74c3c,#c0392b); color:#fff; }
        .card .btn-success { background:linear-gradient(90deg,#2ecc71,#27ae60); color:#fff; }
        .card .btn-warning { background:linear-gradient(90deg,#f39c12,#e67e22); color:#fff; }

        .grid-2 { display:grid; grid-template-columns:1fr 1fr; gap:20px; }
        @media(max-width:700px){ .grid-2 { grid-template-columns:1fr; } }

        /* Tabela de salas */
        table { width:100%; border-collapse:collapse; font-size:0.85rem; }
        th, td { padding:10px 12px; text-align:left; border-bottom:1px solid rgba(255,255,255,0.06); }
        th { color:#aaa; font-weight:400; font-size:0.8rem; text-transform:uppercase; }
        .badge {
            display:inline-block; padding:2px 8px; border-radius:8px;
            font-size:0.7rem; font-weight:600;
        }
        .badge-aguardando { background:#f39c12; color:#fff; }
        .badge-pergunta { background:#3498db; color:#fff; }
        .badge-minigame { background:#2ecc71; color:#fff; }
        .badge-fim { background:#e74c3c; color:#fff; }

        .status-actions { display:flex; gap:4px; flex-wrap:wrap; margin-top:6px; }
        .inline-form { display:inline; }

        .vazio { color:#666; text-align:center; padding:30px; font-size:0.9rem; }
    </style>
</head>
<body>
    <div class="topo">
        <h1>🎮 Game Arena - Painel do Professor</h1>
        <nav>
            <a href="dashboard.php">📊 Dashboard</a>
            <a href="perguntas.php">📝 Perguntas</a>
            <a href="../index.php" target="_blank">👀 Ver Jogo</a>
            <a href="logout.php" class="sair">🚪 Sair</a>
        </nav>
    </div>

    <div class="container">
        <!-- Estatísticas -->
        <div class="stats">
            <div class="stat-card">
                <div class="num"><?= $totalSalas ?></div>
                <div class="label">Salas ativas</div>
            </div>
            <div class="stat-card">
                <div class="num"><?= $totalAlunos ?></div>
                <div class="label">Alunos cadastrados</div>
            </div>
            <div class="stat-card">
                <div class="num"><?= $totalPerguntas ?></div>
                <div class="label">Perguntas no banco</div>
            </div>
        </div>

        <?php if ($mensagem): ?><div class="msg sucesso"><?= htmlspecialchars($mensagem) ?></div><?php endif; ?>
        <?php if ($erro): ?><div class="msg erro"><?= htmlspecialchars($erro) ?></div><?php endif; ?>

        <!-- Grid: Criar Sala + Alterar Status -->
        <div class="grid-2">
            <!-- Criar Sala -->
            <div class="card">
                <h2>➕ Criar Nova Sala</h2>
                <form method="POST">
                    <input type="hidden" name="acao" value="criar_sala">
                    <label>Código da Sala (ex: ABC123)</label>
                    <input type="text" name="room_id" maxlength="10" required style="text-transform:uppercase" placeholder="EX: TURMA1">
                    <label>Seu Nome (Professor)</label>
                    <input type="text" name="host_name" maxlength="20" value="Professor" placeholder="Professor">
                    <button type="submit">🎯 Criar Sala</button>
                </form>
            </div>

            <!-- Alterar Status -->
            <div class="card">
                <h2>🔄 Controlar Status da Sala</h2>
                <form method="POST">
                    <input type="hidden" name="acao" value="alterar_status">
                    <label>Sala</label>
                    <select name="room_id" required>
                        <option value="">-- Selecione --</option>
                        <?php foreach ($salas as $s): ?>
                            <option value="<?= htmlspecialchars($s['room_id']) ?>">
                                <?= htmlspecialchars($s['room_id']) ?> - [<?= $s['status'] ?>]
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <label>Novo Status</label>
                    <select name="status" required>
                        <option value="aguardando">⏳ Aguardando</option>
                        <option value="pergunta_1">❓ Pergunta 1</option>
                        <option value="pergunta_2">❓ Pergunta 2</option>
                        <option value="pergunta_3">❓ Pergunta 3</option>
                        <option value="pergunta_4">❓ Pergunta 4</option>
                        <option value="pergunta_5">❓ Pergunta 5</option>
                        <option value="minigame">🎮 Mini-game</option>
                        <option value="fim">🏁 Fim</option>
                    </select>
                    <button type="submit">🔄 Aplicar</button>
                </form>
            </div>
        </div>

        <!-- Lista de Salas -->
        <div class="card">
            <h2>📋 Salas Ativas</h2>
            <?php if (empty($salas)): ?>
                <div class="vazio">Nenhuma sala criada ainda. Crie uma ao lado.</div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Sala</th>
                            <th>Professor</th>
                            <th>Status</th>
                            <th>Jogadores</th>
                            <th>Criada em</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($salas as $sala): 
                            $badgeClass = 'badge-aguardando';
                            if (str_starts_with($sala['status'], 'pergunta')) $badgeClass = 'badge-pergunta';
                            if ($sala['status'] === 'minigame') $badgeClass = 'badge-minigame';
                            if ($sala['status'] === 'fim') $badgeClass = 'badge-fim';
                        ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($sala['room_id']) ?></strong></td>
                            <td><?= htmlspecialchars($sala['host_name']) ?></td>
                            <td><span class="badge <?= $badgeClass ?>"><?= htmlspecialchars($sala['status']) ?></span></td>
                            <td><?= $sala['qtd_jogadores'] ?></td>
                            <td><?= date('d/m/Y H:i', strtotime($sala['criada_em'])) ?></td>
                            <td>
                                <!-- Botões rápidos de status -->
                                <div class="status-actions">
                                    <form class="inline-form" method="POST">
                                        <input type="hidden" name="acao" value="alterar_status">
                                        <input type="hidden" name="room_id" value="<?= htmlspecialchars($sala['room_id']) ?>">
                                        <input type="hidden" name="status" value="pergunta_1">
                                        <button type="submit" class="btn-sm btn-warning">❓ Q1</button>
                                    </form>
                                    <form class="inline-form" method="POST">
                                        <input type="hidden" name="acao" value="alterar_status">
                                        <input type="hidden" name="room_id" value="<?= htmlspecialchars($sala['room_id']) ?>">
                                        <input type="hidden" name="status" value="minigame">
                                        <button type="submit" class="btn-sm btn-success">🎮 Jogo</button>
                                    </form>
                                    <form class="inline-form" method="POST">
                                        <input type="hidden" name="acao" value="alterar_status">
                                        <input type="hidden" name="room_id" value="<?= htmlspecialchars($sala['room_id']) ?>">
                                        <input type="hidden" name="status" value="fim">
                                        <button type="submit" class="btn-sm btn-danger">🏁 Fim</button>
                                    </form>
                                    <form class="inline-form" method="POST" onsubmit="return confirm('Excluir sala <?= htmlspecialchars($sala['room_id']) ?>?')">
                                        <input type="hidden" name="acao" value="deletar_sala">
                                        <input type="hidden" name="room_id" value="<?= htmlspecialchars($sala['room_id']) ?>">
                                        <button type="submit" class="btn-sm btn-danger">🗑️</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>