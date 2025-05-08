<?php
global $pdo;
require_once 'config.php';

// Verifica se o usuário já está logado
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

// Processa o formulário de registro
$error = '';
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verifica token CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Token CSRF inválido.");
    }

    // Validação de entrada
    $username = htmlspecialchars($_POST['username']);
    $email = htmlspecialchars($_POST['email']);
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validações
    if (empty($username) || empty($password) || empty($confirm_password)) {
        $error = "Por favor, preencha todos os campos.";
    } elseif ($password !== $confirm_password) {
        $error = "As senhas não coincidem.";
    } elseif (strlen($password) < 8) {
        $error = "A senha deve ter pelo menos 8 caracteres.";
    } elseif (!preg_match('/[A-Z]/', $password) || !preg_match('/[0-9]/', $password) || !preg_match('/[^a-zA-Z0-9]/', $password)) {
        $error = "A senha deve conter pelo menos uma letra maiúscula, um número e um caractere especial.";
    } else {
        // Verifica se o usuário já existe
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);

        if ($stmt->fetch()) {
            $error = "Nome de usuário já está em uso.";
        } else {
            // Cria hash seguro da senha
            $password_hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

            // Insere o novo usuário no banco de dados
            try {
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
                $stmt->execute([$username, $email, $password_hash]);

                $success = "Registro realizado com sucesso! Você já pode fazer login.";
            } catch (PDOException $e) {
                error_log("Erro ao registrar usuário: " . $e->getMessage());
                $error = "Erro ao registrar. Por favor, tente novamente.";
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
    <title>Registro Seguro</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 400px; margin: 0 auto; padding: 20px; }
        .error { color: red; margin-bottom: 15px; }
        .success { color: green; margin-bottom: 15px; }
        input { display: block; width: 100%; padding: 8px; margin-bottom: 10px; box-sizing: border-box; }
        button { background-color: #4CAF50; color: white; padding: 10px; border: none; cursor: pointer; width: 100%; }
        .password-rules { font-size: 0.9em; color: #666; margin-bottom: 15px; }
    </style>
</head>
<body>
<h2>Registro de Usuário</h2>
<?php if (!empty($error)): ?>
    <div class="error"><?php echo htmlspecialchars($error); ?></div>
<?php elseif (!empty($success)): ?>
    <div class="success"><?php echo htmlspecialchars($success); ?></div>
<?php endif; ?>

<form method="POST" action="register.php">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">

    <label for="email">Email:</label>
    <input type="email" id="email" name="email" required value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>">

    <label for="username">Usuário:</label>
    <input type="text" id="username" name="username" required value="<?php echo isset($username) ? htmlspecialchars($username) : ''; ?>">

    <label for="password">Senha:</label>
    <input type="password" id="password" name="password" required>

    <label for="confirm_password">Confirme a Senha:</label>
    <input type="password" id="confirm_password" name="confirm_password" required>

    <div class="password-rules">
        <strong>A senha deve:</strong>
        <ul>
            <li>Ter no mínimo 8 caracteres</li>
            <li>Conter pelo menos uma letra maiúscula</li>
            <li>Conter pelo menos um número</li>
            <li>Conter pelo menos um caractere especial</li>
        </ul>
    </div>

    <button type="submit">Registrar</button>
</form>

<p>Já tem uma conta? <a href="login.php">Faça login aqui</a></p>
</body>
</html>