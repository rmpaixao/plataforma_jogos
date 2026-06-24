<?php
/**
 * ADMIN - Gerenciamento de Perguntas (CRUD)
 * 
 * Listar, criar, editar e excluir perguntas do quiz.
 * 
 * Acesse: /plataforma_jogos/admin/perguntas.php
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

// ---- Ações CRUD ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';

    try {
        switch ($acao) {
            case 'criar':
            case 'editar':
                $pergunta     = trim($_POST['pergunta'] ?? '');
                $opcao_a      = trim($_POST['opcao_a'] ?? '');
                $opcao_b      = trim($_POST['opcao_b'] ?? '');
                $opcao_c      = trim($_POST['opcao_c'] ?? '');
                $opcao_d      = trim($_POST['opcao_d'] ?? '');
                $resposta     = strtoupper(trim($_POST['resposta_certa'] ?? ''));
                $id           = (int)($_POST['id'] ?? 0);

                if ($pergunta === '') throw new Exception('Texto da pergunta é obrigatório.');
                if ($opcao_a === '' || $opcao_b === '' || $opcao_c === '' || $opcao_d === '') {
                    throw new Exception('Todas as 4 opções são obrigatórias.');
                }
                if (!in_array($resposta, ['A', 'B', 'C', 'D'])) {
                    throw new Exception('Resposta certa deve ser A, B, C ou D.');
                }

                if ($acao === 'criar') {
                    $stmt = $pdo->prepare("INSERT INTO perguntas (pergunta, opcao_a, opcao_b, opcao_c, opcao_d, resposta_certa) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$pergunta, $opcao_a, $opcao_b, $opcao_c, $opcao_d, $resposta]);
                    $mensagem = "✅ Pergunta criada com sucesso!";
                } else {
                    $stmt = $pdo->prepare("UPDATE perguntas SET pergunta=?, opcao_a=?, opcao_b=?, opcao_c=?, opcao_d=?, resposta_certa=? WHERE id=?");
                    $stmt->execute([$pergunta, $opcao_a, $opcao_b, $opcao_c, $opcao_d, $resposta, $id]);
                    $mensagem = "✅ Pergunta #{$id} editada com sucesso!";
                }
                break;

            case 'excluir':
                $id = (int)($_POST['id'] ?? 0);
                if ($id <= 0) throw new Exception('ID inválido.');
                $stmt = $pdo->prepare("DELETE FROM perguntas WHERE id = ?");
                $stmt->execute([$id]);
                $mensagem = "🗑️ Pergunta #{$id} excluída!";
                break;
        }
    } catch (Exception $e) {
        $erro = $e->getMessage();
    }
}

// ---- Busca perguntas ----
$busca = trim($_GET['busca'] ?? '');
if ($busca !== '') {
    $stmt = $pdo->prepare("SELECT * FROM perguntas WHERE pergunta LIKE ? ORDER BY id ASC");
    $stmt->execute(['%' . $busca . '%']);
} else {
    $stmt = $pdo->query("SELECT * FROM perguntas ORDER BY id ASC");
}
$perguntas = $stmt->fetchAll();

// ---- Se for editar, carrega dados da pergunta ----
$editando = null;
if (isset($_GET['editar'])) {
    $id = (int)$_GET['editar'];
    $stmt = $pdo->prepare("SELECT * FROM perguntas WHERE id = ?");
    $stmt->execute([$id]);
    $editando = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perguntas - Game Arena Admin</title>
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

        .msg { padding:10px 14px; border-radius:8px; margin-bottom:12px; font-size:0.9rem; }
        .msg.sucesso { background:rgba(46,204,113,0.15); color:#2ecc71; border:1px solid rgba(46,204,113,0.3); }
        .msg.erro { background:rgba(231,76,60,0.15); color:#e74c3c; border:1px solid rgba(231,76,60,0.3); }

        .card {
            background:rgba(255,255,255,0.04); border-radius:14px;
            padding:20px; margin-bottom:20px; border:1px solid rgba(255,255,255,0.06);
        }
        .card h2 { font-size:1.1rem; margin-bottom:12px; color:#ffd200; }
        .card label { display:block; font-size:0.85rem; color:#aaa; margin-top:10px; margin-bottom:4px; }
        .card input, .card textarea, .card select {
            width:100%; padding:10px 12px; border-radius:8px;
            border:1px solid rgba(255,255,255,0.12); background:rgba(255,255,255,0.05);
            color:#fff; font-size:0.95rem; outline:none; font-family:inherit;
        }
        .card input:focus, .card textarea:focus, .card select:focus { border-color:#f7971e; }
        .card textarea { min-height:60px; resize:vertical; }
        .card button {
            margin-top:12px; padding:10px 20px; border:none; border-radius:8px;
            background:linear-gradient(90deg,#f7971e,#ffd200); color:#1a1a2e;
            font-weight:700; font-size:0.9rem; cursor:pointer; transition:transform 0.2s;
        }
        .card button:hover { transform:translateY(-2px); }
        .card .btn-sm { padding:6px 12px; font-size:0.8rem; margin:2px; }
        .card .btn-danger { background:linear-gradient(90deg,#e74c3c,#c0392b); color:#fff; }
        .card .btn-warning { background:linear-gradient(90deg,#f39c12,#e67e22); color:#fff; }
        .card .btn-secondary { background:rgba(255,255,255,0.1); color:#fff; }
        .grid-2 { display:grid; grid-template-columns:1fr 1fr; gap:20px; }
        @media(max-width:700px){ .grid-2 { grid-template-columns:1fr; } }

        table { width:100%; border-collapse:collapse; font-size:0.85rem; }
        th, td { padding:10px 12px; text-align:left; border-bottom:1px solid rgba(255,255,255,0.06); }
        th { color:#aaa; font-weight:400; font-size:0.8rem; text-transform:uppercase; }
        .vazio { color:#666; text-align:center; padding:30px; font-size:0.9rem; }
        .inline-form { display:inline; }
        .opcao { display:flex; align-items:center; gap:8px; margin-bottom:6px; }
        .opcao .letra {
            display:inline-flex; width:28px; height:28px; border-radius:50%;
            background:rgba(255,255,255,0.08); align-items:center; justify-content:center;
            font-weight:700; font-size:0.85rem; flex-shrink:0;
        }
        .resposta-certa { color:#2ecc71; font-weight:700; }
        .busca { margin-bottom:15px; }
        .busca form { display:flex; gap:8px; }
        .busca input { flex:1; }
        .busca button { margin-top:0; }
    </style>
</head>
<body>
    <div class="topo">
        <h1>📝 Gerenciar Perguntas</h1>
        <nav>
            <a href="dashboard.php">📊 Dashboard</a>
            <a href="perguntas.php">📝 Perguntas</a>
            <a href="../index.php" target="_blank">👀 Ver Jogo</a>
            <a href="logout.php" class="sair">🚪 Sair</a>
        </nav>
    </div>

    <div class="container">
        <?php if ($mensagem): ?><div class="msg sucesso"><?= htmlspecialchars($mensagem) ?></div><?php endif; ?>
        <?php if ($erro): ?><div class="msg erro"><?= htmlspecialchars($erro) ?></div><?php endif; ?>

        <div class="grid-2">
            <!-- Formulário de Pergunta -->
            <div class="card">
                <h2><?= $editando ? '✏️ Editar Pergunta #' . $editando['id'] : '➕ Nova Pergunta' ?></h2>
                <form method="POST">
                    <input type="hidden" name="acao" value="<?= $editando ? 'editar' : 'criar' ?>">
                    <?php if ($editando): ?>
                        <input type="hidden" name="id" value="<?= $editando['id'] ?>">
                    <?php endif; ?>

                    <label>Pergunta</label>
                    <textarea name="pergunta" maxlength="255" required><?= htmlspecialchars($editando['pergunta'] ?? '') ?></textarea>

                    <div style="margin-top:12px;">
                        <div class="opcao">
                            <span class="letra" style="background:rgba(231,76,60,0.3);color:#e74c3c;">A</span>
                            <input type="text" name="opcao_a" placeholder="Opção A" maxlength="100" required
                                   value="<?= htmlspecialchars($editando['opcao_a'] ?? '') ?>">
                        </div>
                        <div class="opcao">
                            <span class="letra" style="background:rgba(52,152,219,0.3);color:#3498db;">B</span>
                            <input type="text" name="opcao_b" placeholder="Opção B" maxlength="100" required
                                   value="<?= htmlspecialchars($editando['opcao_b'] ?? '') ?>">
                        </div>
                        <div class="opcao">
                            <span class="letra" style="background:rgba(46,204,113,0.3);color:#2ecc71;">C</span>
                            <input type="text" name="opcao_c" placeholder="Opção C" maxlength="100" required
                                   value="<?= htmlspecialchars($editando['opcao_c'] ?? '') ?>">
                        </div>
                        <div class="opcao">
                            <span class="letra" style="background:rgba(241,196,15,0.3);color:#f1c40f;">D</span>
                            <input type="text" name="opcao_d" placeholder="Opção D" maxlength="100" required
                                   value="<?= htmlspecialchars($editando['opcao_d'] ?? '') ?>">
                        </div>
                    </div>

                    <label>Resposta Correta</label>
                    <select name="resposta_certa" required>
                        <option value="A" <?= ($editando['resposta_certa'] ?? '') === 'A' ? 'selected' : '' ?>>A</option>
                        <option value="B" <?= ($editando['resposta_certa'] ?? '') === 'B' ? 'selected' : '' ?>>B</option>
                        <option value="C" <?= ($editando['resposta_certa'] ?? '') === 'C' ? 'selected' : '' ?>>C</option>
                        <option value="D" <?= ($editando['resposta_certa'] ?? '') === 'D' ? 'selected' : '' ?>>D</option>
                    </select>

                    <div style="display:flex;gap:8px;">
                        <button type="submit"><?= $editando ? '💾 Salvar' : '➕ Adicionar' ?></button>
                        <?php if ($editando): ?>
                            <a href="perguntas.php" style="margin-top:12px;">
                                <button type="button" class="btn-secondary">Cancelar</button>
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- Lista de Perguntas -->
            <div class="card">
                <h2>📋 Perguntas Cadastradas (<?= count($perguntas) ?>)</h2>

                <!-- Busca -->
                <div class="busca">
                    <form method="GET">
                        <input type="text" name="busca" placeholder="Buscar pergunta..." value="<?= htmlspecialchars($busca) ?>">
                        <button type="submit">🔍</button>
                        <?php if ($busca): ?>
                            <a href="perguntas.php"><button type="button" class="btn-secondary">Limpar</button></a>
                        <?php endif; ?>
                    </form>
                </div>

                <?php if (empty($perguntas)): ?>
                    <div class="vazio">Nenhuma pergunta encontrada.</div>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Pergunta</th>
                                <th>Correta</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($perguntas as $p): ?>
                            <tr>
                                <td><?= $p['id'] ?></td>
                                <td><?= htmlspecialchars(mb_substr($p['pergunta'], 0, 60)) . (mb_strlen($p['pergunta']) > 60 ? '...' : '') ?></td>
                                <td><span class="resposta-certa"><?= $p['resposta_certa'] ?></span></td>
                                <td>
                                    <a href="?editar=<?= $p['id'] ?>" class="btn-sm btn-warning" style="text-decoration:none;display:inline-block;">✏️</a>
                                    <form class="inline-form" method="POST" onsubmit="return confirm('Excluir pergunta #<?= $p['id'] ?>?')">
                                        <input type="hidden" name="acao" value="excluir">
                                        <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                        <button type="submit" class="btn-sm btn-danger">🗑️</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>