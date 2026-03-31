<?php
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/app/helpers/functions.php';
logAction('logout', 'Logout realizado');
session_destroy();
redirect(APP_URL . '/login.php');
