<?php
require_once 'config.php';

// Verifica autenticação e tempo de sessão
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Verifica tempo de inatividade
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT)) {
    session_unset();
    session_destroy();
    header("Location: login.php?timeout=1");
    exit();
}

// Atualiza tempo de atividade
$_SESSION['last_activity'] = time();

// Busca informações do usuário logado
$stmt = $pdo->prepare("SELECT id, username, email FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$current_user = $stmt->fetch();

// Busca todos os usuários (apenas para demonstração)
$users = [];
try {
    $stmt = $pdo->query("SELECT id, username, email, created_at FROM users ORDER BY created_at DESC");
    $users = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao buscar usuários: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 1000px; margin: 0 auto; padding: 20px; }
        .user-info { background-color: #f5f5f5; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #f2f2f2; }
        tr:hover { background-color: #f5f5f5; }
        .action-btn {
            padding: 5px 10px;
            margin: 0 2px;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            color: white;
        }
        .edit-btn { background-color: #4CAF50; }
        .delete-btn { background-color: #f44336; }
        .logout { margin-top: 20px; }
    </style>
</head>
<body>
<div class="user-info">
    <h2>Bem-vindo, <?php echo htmlspecialchars($current_user['username']); ?>!</h2>
    <p>Email: <?php echo htmlspecialchars($current_user['email']); ?></p>
    <p>Esta é uma área restrita do sistema.</p>
</div>

<h3>Lista de Usuários</h3>
<table>
    <thead>
    <tr>
        <th>ID</th>
        <th>Usuário</th>
        <th>Email</th>
        <th>Data de Registro</th>
        <th>Ações</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($users as $user): ?>
        <tr>
            <td><?php echo htmlspecialchars($user['id']); ?></td>
            <td><?php echo htmlspecialchars($user['username']); ?></td>
            <td><?php echo htmlspecialchars($user['email']); ?></td>
            <td><?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($user['created_at']))); ?></td>
            <td>
                <button class="action-btn edit-btn" onclick="alert('Funcionalidade de edição seria implementada aqui')">Alterar</button>
                <button class="action-btn delete-btn" onclick="alert('Funcionalidade de exclusão seria implementada aqui')">Excluir</button>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<div class="logout">
    <a href="logout.php">Sair do Sistema</a>
</div>

<script>
    // Apenas para demonstração - os botões não terão funcionalidade real
    document.querySelectorAll('.edit-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const username = this.closest('tr').querySelector('td:nth-child(2)').textContent;
            alert(`Editar usuário: ${username}\n\nEsta é apenas uma demonstração. Em um sistema real, aqui abriria um formulário de edição.`);
        });
    });

    document.querySelectorAll('.delete-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const username = this.closest('tr').querySelector('td:nth-child(2)').textContent;
            if (confirm(`Deseja realmente excluir o usuário ${username}?\n\nEsta é apenas uma demonstração. Em um sistema real, isso removeria o usuário do banco de dados.`)) {
                alert(`Usuário ${username} seria excluído aqui.`);
            }
        });
    });
</script>
</body>
</html>