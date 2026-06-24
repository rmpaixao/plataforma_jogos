<?php
/**
 * ADMIN - Login do Professor
 * 
 * A senha é lida do .env: ADMIN_PASS=sua_senha
 * Padrão: admin123
 * 
 * Acesse: /plataforma_jogos/admin/index.php
 */

session_start();

if (isset($_SESSION['admin_logado']) && $_SESSION['admin_logado'] === true) {
    header('Location: dashboard.php');
    exit;
}

// Carrega .env manualmente
function loadEnv(string $caminho): array
{
    $vars = [];
    if (!file_exists($caminho)) return $vars;
    $linhas = file($caminho, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($linhas as $linha) {
        $linha = trim($linha);
        if ($linha === '' || str_starts_with($linha, '#')) continue;
        $pos = strpos($linha, '=');
        if ($pos === false) continue;
        $chave = trim(substr($linha, 0, $pos));
        $valor = trim(substr($linha, $pos + 1));
        $vars[$chave] = $valor;
    }
    return $vars;
}

$envVars = loadEnv(__DIR__ . '/../.env');
$senhaAdmin = $envVars['ADMIN_PASS'] ?? 'admin123';
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (($_POST['senha'] ?? '') === $senhaAdmin) {
        $_SESSION['admin_logado'] = true;
        header('Location: dashboard.php');
        exit;
    }
    $erro = 'Senha incorreta!';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Game Arena</title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body {
            font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;
            background:linear-gradient(135deg,#0f0c29,#302b63,#24243e);
            min-height:100vh;
            display:flex;
            justify-content:center;
            align-items:center;
            color:#fff;
        }
        .login-box {
            background:rgba(255,255,255,0.06);
            backdrop-filter:blur(10px);
            border-radius:20px;
            padding:2.5rem;
            max-width:380px;
            width:100%;
            border:1px solid rgba(255,255,255,0.08);
            text-align:center;
        }
        .login-box h1 {
            font-size:1.8rem;
            background:linear-gradient(90deg,#f7971e,#ffd200);
            -webkit-background-clip:text;
            -webkit-text-fill-color:transparent;
            margin-bottom:0.3rem;
        }
        .login-box p { color:#aaa; margin-bottom:1.5rem; font-size:0.9rem; }
        .login-box input {
            width:100%; padding:12px 16px; margin:8px 0;
            border:2px solid rgba(255,255,255,0.12); border-radius:10px;
            background:rgba(255,255,255,0.05); color:#fff; font-size:1rem; outline:none;
        }
        .login-box input:focus { border-color:#f7971e; }
        .login-box button {
            width:100%; padding:12px; margin-top:12px; border:none; border-radius:10px;
            background:linear-gradient(90deg,#f7971e,#ffd200);
            color:#1a1a2e; font-size:1rem; font-weight:700; cursor:pointer;
            transition:transform 0.2s;
        }
        .login-box button:hover { transform:translateY(-2px); }
        .erro { color:#e74c3c; margin-top:10px; font-size:0.9rem; }
        .admin-tag {
            display:inline-block; background:rgba(231,76,60,0.2);
            color:#e74c3c; padding:2px 10px; border-radius:10px;
            font-size:0.7rem; font-weight:600; margin-bottom:10px;
        }
        .info { color:#666; margin-top:12px; font-size:0.75rem; }
    </style>
</head>
<body>
    <div class="login-box">
        <div class="admin-tag">🔒 ÁREA RESTRITA</div>
        <h1>🎮 Game Arena</h1>
        <p>Painel do Professor</p>
        <form method="POST">
            <input type="password" name="senha" placeholder="Senha do Administrador" required autofocus>
            <button type="submit">🔑 Entrar</button>
        </form>
        <?php if ($erro): ?>
            <div class="erro">❌ <?= htmlspecialchars($erro) ?></div>
        <?php endif; ?>
        <div class="info">
            Configure no .env: <code>ADMIN_PASS=sua_senha</code><br>
            (padrão: admin123)
        </div>
    </div>
</body>
</html>