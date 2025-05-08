<?php
require_once 'config.php';

// Verifica se o usuário já está logado
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

// Verifica tentativas de login
$ip = $_SERVER['REMOTE_ADDR'];
$stmt = $pdo->prepare("SELECT attempts, last_attempt FROM login_attempts WHERE ip = ?");
$stmt->execute([$ip]);
$attempt = $stmt->fetch();

if ($attempt && $attempt['attempts'] >= MAX_LOGIN_ATTEMPTS) {
    $last_attempt = strtotime($attempt['last_attempt']);
    if (time() - $last_attempt < LOGIN_ATTEMPT_TIMEOUT) {
        die("Muitas tentativas de login. Por favor, tente novamente em " . ceil((LOGIN_ATTEMPT_TIMEOUT - (time() - $last_attempt)) / 60) . " minutos.");
    }
}

// Processa o formulário de login
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verifica token CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Token CSRF inválido.");
    }

    // Validação de entrada
    $username = htmlspecialchars( $_POST['username']);
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = "Por favor, preencha todos os campos.";
    } else {
        // Busca usuário no banco de dados
        $stmt = $pdo->prepare("SELECT id, username, password, salt FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Login bem-sucedido
            session_regenerate_id(true);

            // Limpa tentativas de login
            $stmt = $pdo->prepare("DELETE FROM login_attempts WHERE ip = ?");
            $stmt->execute([$ip]);

            // Define dados da sessão
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['last_activity'] = time();

            header("Location: dashboard.php");
            exit();
        } else {
            // Login falhou - registra tentativa
            $error = "Credenciais inválidas.";

            if ($attempt) {
                $stmt = $pdo->prepare("UPDATE login_attempts SET attempts = attempts + 1, last_attempt = NOW() WHERE ip = ?");
                $stmt->execute([$ip]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO login_attempts (ip, attempts, last_attempt) VALUES (?, 1, NOW())");
                $stmt->execute([$ip]);
            }
        }
    }
}

// Gera novo token CSRF para o formulário
$csrf_token = generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Seguro</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 400px; margin: 0 auto; padding: 20px; }
        .error { color: red; margin-bottom: 15px; }
        input { display: block; width: 100%; padding: 8px; margin-bottom: 10px; box-sizing: border-box; }
        button { background-color: #4CAF50; color: white; padding: 10px; border: none; cursor: pointer; width: 100%; }
    </style>
</head>
<body>
<h2>Login Seguro</h2>
<?php if (!empty($error)): ?>
    <div class="error"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<form method="POST" action="login.php">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">

    <label for="username">Usuário:</label>
    <input type="text" id="username" name="username" required>

    <label for="password">Senha:</label>
    <input type="password" id="password" name="password" required>

    <button type="submit">Entrar</button>
</form>
</body>
</html>