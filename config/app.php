<?php
// config/app.php

define('APP_NAME', 'CRMV EAD');
define('APP_VERSION', '1.0.0');

// ── Detecta automaticamente protocolo, host e porta ──────
// Funciona em qualquer porta (80, 82, 8080, etc.)
$_ead_protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$_ead_host     = $_SERVER['HTTP_HOST'] ?? 'localhost'; // já inclui a porta, ex: localhost:82
$_ead_subdir   = '/ead'; // ← mude aqui se a pasta tiver outro nome dentro do www

define('APP_URL', $_ead_protocol . '://' . $_ead_host . $_ead_subdir);

unset($_ead_protocol, $_ead_host, $_ead_subdir);

// Caminhos
define('ROOT_PATH',    __DIR__ . '/..');
define('PUBLIC_PATH',  ROOT_PATH . '/public');
define('UPLOAD_PATH',  PUBLIC_PATH . '/uploads');
define('CERT_PATH',    UPLOAD_PATH . '/certificados');
define('MAT_PATH',     UPLOAD_PATH . '/materiais');
define('MODEL_PATH',   UPLOAD_PATH . '/modelos');

// Upload
define('MAX_UPLOAD_MB', 20);
define('ALLOWED_MATERIAL', ['pdf','doc','docx','ppt','pptx','xls','xlsx','zip','jpg','jpeg','png']);
define('ALLOWED_IMAGE',    ['jpg','jpeg','png','gif','webp']);

// Sessão
define('SESSION_NAME',    'ead_session');
define('SESSION_TIMEOUT', 300); // 5 minutos em segundos

// Segurança
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
ini_set('session.gc_maxlifetime',  SESSION_TIMEOUT);
ini_set('session.cookie_samesite', 'Lax');
date_default_timezone_set('America/Sao_Paulo');

session_name(SESSION_NAME);
session_start();

// ── Expiração automática de sessão por inatividade ────────────
if (!empty($_SESSION['usuario_id'])) {
    $agora        = time();
    $ultimaAtiv   = $_SESSION['_ultima_atividade'] ?? $agora;
    $inativo      = $agora - $ultimaAtiv;

    if ($inativo > SESSION_TIMEOUT) {
        // Sessão expirada por inatividade: destrói e redireciona
        session_unset();
        session_destroy();
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host     = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $subdir   = '/ead';
        header('Location: ' . $protocol . '://' . $host . $subdir . '/login.php?timeout=1');
        exit;
    }

    // Renova timestamp a cada requisição
    $_SESSION['_ultima_atividade'] = $agora;
} else {
    // Inicializa o timestamp para novos logins
    $_SESSION['_ultima_atividade'] = time();
}

// Upload de vídeo para aulas
define('ALLOWED_VIDEO', ['mp4','webm','ogg','mov']);
define('VIDEO_PATH', UPLOAD_PATH . '/videos');
