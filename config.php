<?php

// Configurações de segurança
session_start([
    'cookie_lifetime' => 1800, // 30 minutos
    'cookie_secure' => true,  // Apenas HTTPS
    'cookie_httponly' => true,  // Acessível apenas via HTTP (não JS)
    'use_strict_mode' => true   // IDs de sessão mais seguros
]);

// Headers de segurança
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");
header("X-Content-Type-Options: nosniff");
header("Referrer-Policy: strict-origin-when-cross-origin");

// Configurações do banco de dados
define('DB_HOST', 'localhost');
define('DB_NAME', 'login-sec');
define('DB_USER', 'root');
define('DB_PASS', '');

// Configurações de segurança
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_ATTEMPT_TIMEOUT', 300); // 5 minutos em segundos
define('SESSION_TIMEOUT', 1800); // 30 minutos em segundos

// Função para gerar token CSRF
function generateCsrfToken()
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Conexão PDO com o banco de dados
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    error_log("Erro de conexão com o banco de dados: " . $e->getMessage());
    die("Erro no sistema. Por favor, tente novamente mais tarde.");
}
