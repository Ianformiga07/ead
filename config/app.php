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
define('SESSION_NAME', 'ead_session');

// Segurança
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
date_default_timezone_set('America/Sao_Paulo');

session_name(SESSION_NAME);
session_start();

// Upload de vídeo para aulas
define('ALLOWED_VIDEO', ['mp4','webm','ogg','mov']);
define('VIDEO_PATH', UPLOAD_PATH . '/videos');
