<?php
/**
 * Gerador de Certificado em PDF (HTML → PDF via página impressa)
 * Para PDF real em servidor, instale: composer require dompdf/dompdf
 * Esta versão gera HTML otimizado para impressão/salvar como PDF.
 */
require_once __DIR__ . '/../app/bootstrap.php';
authCheck('aluno');

$user      = currentUser();
$cursoId   = (int)($_GET['curso_id'] ?? 0);
$cursoModel= new CursoModel();
$certModel = new CertificadoModel();
$matriModel= new MatriculaModel();

$curso = $cursoModel->findById($cursoId);
$mat   = $matriModel->buscar($user['id'], $cursoId);
if (!$curso || !$mat || $mat['status'] !== 'concluida') {
    die('Acesso negado.');
}

$cert       = $certModel->buscar($user['id'], $cursoId);
if (!$cert) { die('Certificado não encontrado.'); }

$modelo     = $certModel->modelo($cursoId);
$validarUrl = APP_URL . '/validar.php?codigo=' . urlencode($cert['codigo']);
$qrUrl      = 'https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=' . urlencode($validarUrl);
$dataConcl  = dataBR($mat['concluido_em'] ?? $cert['emitido_em']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Certificado — <?= e($cert['aluno_nome']) ?></title>
<style>
  @page { margin: 0; size: A4 landscape; }
  * { margin:0; padding:0; box-sizing:border-box; }
  body { font-family: 'Times New Roman', serif; background:#fff; }

  .cert-page {
    width: 297mm; height: 210mm;
    position: relative;
    display: flex; align-items: center; justify-content: center;
    page-break-after: always;
    overflow: hidden;
  }

  .cert-bg-model {
    position: absolute; top:0; left:0; width:100%; height:100%;
    object-fit: cover; z-index: 0;
  }

  .cert-content {
    position: relative; z-index: 1;
    text-align: center; padding: 40px 80px;
    width: 100%;
  }

  .cert-border {
    border: 8px double #4f46e5;
    padding: 40px 80px;
    background: linear-gradient(135deg, rgba(248,249,255,.97) 0%, rgba(255,255,255,.97) 100%);
    text-align: center;
  }

  .cert-header { font-size: 11pt; letter-spacing: 3px; text-transform: uppercase; color: #6b7280; margin-bottom: 20px; }
  .cert-title  { font-size: 36pt; font-weight: bold; color: #1e1b4b; margin-bottom: 10px; font-family: 'Georgia', serif; }
  .cert-certif { font-size: 13pt; color: #374151; margin-bottom: 8px; }
  .cert-nome   { font-size: 28pt; font-weight: bold; color: #4f46e5; margin: 10px 0; font-style: italic; }
  .cert-curso-label { font-size: 12pt; color: #6b7280; }
  .cert-curso  { font-size: 18pt; font-weight: bold; color: #1e1b4b; margin: 8px 0; }
  .cert-ch     { font-size: 12pt; color: #374151; margin-bottom: 6px; }
  .cert-data   { font-size: 11pt; color: #6b7280; }
  .cert-linha  { border-top: 1px solid #e5e7eb; margin: 20px auto; width: 80%; }
  .cert-instrutores { font-size: 10pt; color: #6b7280; }
  .cert-codigo { font-size: 8pt; color: #9ca3af; margin-top: 10px; }
  .cert-qr     { position: absolute; bottom: 20px; right: 20px; text-align: center; }
  .cert-qr img { width: 70px; height: 70px; border: 2px solid #e5e7eb; padding: 3px; background: #fff; }
  .cert-qr p   { font-size: 7pt; color: #9ca3af; margin-top: 2px; }

  /* VERSO */
  .verso-page { padding: 40px 60px; }
  .verso-page h1 { font-size: 20pt; color: #1e1b4b; border-bottom: 2px solid #4f46e5; padding-bottom: 10px; margin-bottom: 20px; }
  .verso-page h2 { font-size: 13pt; color: #374151; margin-bottom: 10px; }
  .verso-page p  { font-size: 11pt; color: #4b5563; line-height: 1.6; }

  @media print { .no-print { display: none; } }
</style>
</head>
<body>

<!-- FRENTE -->
<div class="cert-page">
  <?php if (!empty($modelo['frente'])): ?>
  <img src="<?= APP_URL ?>/public/uploads/modelos/<?= e($modelo['frente']) ?>" class="cert-bg-model">
  <?php endif; ?>

  <div class="cert-content">
    <div class="cert-border" style="<?= !empty($modelo['frente']) ? 'background:rgba(255,255,255,.92)' : '' ?>">
      <div class="cert-header">Plataforma EAD · Certificado de Conclusão</div>
      <div class="cert-title">CERTIFICADO</div>
      <div class="cert-certif">Certificamos que</div>
      <div class="cert-nome"><?= e($cert['aluno_nome']) ?></div>
      <div class="cert-curso-label">concluiu com êxito o curso</div>
      <div class="cert-curso"><?= e($cert['curso_nome']) ?></div>
      <div class="cert-ch">com carga horária de <strong><?= $cert['carga_horaria'] ?> horas</strong></div>
      <div class="cert-data">em <?= $dataConcl ?></div>
      <div class="cert-linha"></div>
      <?php if (!empty($cert['instrutores'])): ?>
      <div class="cert-instrutores">Instrutor(es): <strong><?= e($cert['instrutores']) ?></strong></div>
      <?php endif; ?>
      <div class="cert-codigo">Código: <?= $cert['codigo'] ?></div>
    </div>
  </div>

  <div class="cert-qr">
    <img src="<?= $qrUrl ?>">
    <p>Validar</p>
  </div>
</div>

<!-- VERSO -->
<div class="cert-page verso-page">
  <?php if (!empty($modelo['verso'])): ?>
  <img src="<?= APP_URL ?>/public/uploads/modelos/<?= e($modelo['verso']) ?>" class="cert-bg-model">
  <div class="cert-content" style="background:rgba(255,255,255,.93);border-radius:8px;padding:30px;max-width:80%;margin:auto">
  <?php else: ?>
  <div class="cert-content" style="max-width:80%;margin:auto">
  <?php endif; ?>
    <h1><?= e($cert['curso_nome']) ?> — Conteúdo Programático</h1>
    <?php if (!empty($cert['conteudo_programatico'])): ?>
    <p><?= nl2br(e($cert['conteudo_programatico'])) ?></p>
    <?php else: ?>
    <p style="color:#9ca3af">Conteúdo programático não cadastrado.</p>
    <?php endif; ?>
    <?php if (!empty($cert['instrutores'])): ?>
    <h2 style="margin-top:20px">Instrutores</h2>
    <p><?= e($cert['instrutores']) ?></p>
    <?php endif; ?>
    <div style="margin-top:24px; font-size:9pt; color:#9ca3af">
      Certificado emitido em <?= $dataConcl ?> · Código: <?= $cert['codigo'] ?><br>
      Validar em: <?= $validarUrl ?>
    </div>
  </div>
</div>

<div class="no-print" style="text-align:center;padding:20px;background:#f1f5f9">
  <p style="margin-bottom:10px;color:#374151">Use <strong>Ctrl+P</strong> (ou Cmd+P) e selecione "Salvar como PDF"</p>
  <button onclick="window.print()" style="background:#4f46e5;color:#fff;border:none;padding:10px 24px;border-radius:8px;cursor:pointer;font-size:14px">
    🖨️ Imprimir / Salvar PDF
  </button>
  <a href="<?= APP_URL ?>/aluno/certificado.php?curso_id=<?= $cursoId ?>"
     style="margin-left:10px;color:#4f46e5">← Voltar</a>
</div>

<script>window.onload = () => window.print();</script>
</body>
</html>
