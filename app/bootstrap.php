<?php
// app/bootstrap.php — incluso no topo de todas as páginas admin/aluno
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/helpers/functions.php';
require_once __DIR__ . '/models/Model.php';
require_once __DIR__ . '/models/UsuarioModel.php';
require_once __DIR__ . '/models/CursoModel.php';
require_once __DIR__ . '/models/AulaModel.php';
require_once __DIR__ . '/models/OtherModels.php';
require_once __DIR__ . '/models/CertificadoModel.php';
