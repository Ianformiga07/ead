<?php
// index.php — ponto de entrada / redirect
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/app/helpers/functions.php';

if (isLoggedIn()) {
    if ($_SESSION['perfil'] === 'admin') {
        redirect(APP_URL . '/admin/dashboard.php');
    } else {
        redirect(APP_URL . '/aluno/dashboard.php');
    }
}
redirect(APP_URL . '/login.php');
