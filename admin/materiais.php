<?php
/**
 * admin/materiais.php — CRMV EAD
 * Redireciona para o gerenciador unificado de cursos (aba Materiais)
 */
require_once __DIR__ . '/../app/bootstrap.php';
authCheck('admin');

$cursoId = (int)($_GET['curso_id'] ?? 0);
if (!$cursoId) {
    setFlash('error', 'Curso não informado.');
    redirect(APP_URL . '/admin/cursos.php');
}

redirect(APP_URL . "/admin/cursos.php?acao=detalhe&id={$cursoId}&tab=certificado");
