<?php
require_once __DIR__ . '/../app/bootstrap.php';
authCheck('aluno');
header('Content-Type: application/json');

$user      = currentUser();
$aulaId    = (int)($_GET['aula_id'] ?? 0);
$cursoId   = (int)($_GET['curso_id'] ?? 0);

if (!$aulaId || !$cursoId) { echo json_encode(['ok'=>false]); exit; }

$aulaModel  = new AulaModel();
$matriModel = new MatriculaModel();

// Verificar matrícula
$mat = $matriModel->buscar($user['id'], $cursoId);
if (!$mat) { echo json_encode(['ok'=>false]); exit; }

$aulaModel->marcarAssistida($user['id'], $aulaId);

// Recalcular progresso
$total     = $aulaModel->totalPorCurso($cursoId);
$assistidas= count($aulaModel->assistidas($user['id'], $cursoId));
$prog      = $total > 0 ? (int)(($assistidas/$total)*100) : 0;
$matriModel->atualizarProgresso($user['id'], $cursoId, $prog);

if ($prog >= 100) $matriModel->concluir($user['id'], $cursoId);

echo json_encode(['ok'=>true, 'progresso'=>$prog]);
