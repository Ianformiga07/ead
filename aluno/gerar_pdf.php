<?php
/**
 * aluno/gerar_pdf.php — CRMV EAD
 * Gera página otimizada para impressão/salvar PDF via browser (Ctrl+P)
 * Suporta verso_conteudo do CKEditor
 */
require_once __DIR__ . '/../app/bootstrap.php';
authCheck('aluno');

$user       = currentUser();
$cursoId    = (int)($_GET['curso_id'] ?? 0);
$cursoModel = new CursoModel();
$certModel  = new CertificadoModel();
$matriModel = new MatriculaModel();

$curso = $cursoModel->findById($cursoId);
$mat   = $matriModel->buscar($user['id'], $cursoId);
if (!$curso || !$mat || $mat['status'] !== 'concluida') {
    die('Acesso negado.');
}

$cert = $certModel->buscar($user['id'], $cursoId);
if (!$cert) { die('Certificado não encontrado.'); }

$modelo     = $certModel->modelo($cursoId);
$validarUrl = APP_URL . '/validar.php?codigo=' . urlencode($cert['codigo']);
$qrUrl      = 'https://api.qrserver.com/v1/create-qr-code/?size=100x100&data=' . urlencode($validarUrl);
$dataConcl  = dataBR($mat['concluido_em'] ?? $cert['emitido_em']);

// Texto frente
$textoFrenteCustom = $modelo['texto_frente'] ?? '';
$textoFrente = null;
if ($textoFrenteCustom) {
    $textoFrente = str_replace(
        ['[NOME]', '[CURSO]', '[CARGA_HORARIA]', '[DATA]'],
        [$cert['aluno_nome'], $cert['curso_nome'], $cert['carga_horaria'].'h', $dataConcl],
        $textoFrenteCustom
    );
}

// Verso
$versoConteudo = $modelo['verso_conteudo'] ?? '';
$temVerso = trim(strip_tags($versoConteudo)) !== '';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Certificado — <?= e($cert['aluno_nome']) ?></title>
<style>
  @page { margin: 0; size: A4 landscape; }
  * { margin:0; padding:0; box-sizing:border-box; }
  body { font-family: 'Segoe UI', Arial, sans-serif; background:#fff; }

  /* Página */
  .cert-page {
    width: 297mm; min-height: 210mm;
    position: relative;
    page-break-after: always; break-after: page;
    overflow: hidden;
    display: flex; flex-direction: column;
  }

  /* Barra lateral */
  .cert-sidebar {
    position: absolute; left:0; top:0; bottom:0; width:16px;
    background: linear-gradient(180deg, #003d7c 60%, #c8841a 100%);
    z-index: 2;
  }

  /* Imagem de fundo */
  .cert-bg {
    position: absolute; inset:0;
    width:100%; height:100%; object-fit:cover;
    opacity: .07; z-index: 0;
  }

  /* Borda interna */
  .cert-inner {
    margin: 12px 12px 12px 28px;
    border: 2px solid #003d7c;
    padding: 28px 44px 24px;
    flex: 1;
    position: relative; z-index: 1;
    display: flex; flex-direction: column;
  }

  /* Cabeçalho */
  .cert-header {
    display: flex; justify-content: space-between; align-items: center;
    padding-bottom: 16px; margin-bottom: 20px;
    border-bottom: 2px solid #003d7c;
  }
  .logo-area { display: flex; align-items: center; gap: 12px; }
  .logo-circle {
    width: 58px; height: 58px; background: #003d7c; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
  }
  .logo-circle svg { width: 34px; height: 34px; fill: white; }
  .org-sigla { font-size: 22pt; font-weight: 900; color: #003d7c; letter-spacing: 2px; }
  .org-full { font-size: 8.5pt; color: #555; line-height: 1.4; max-width: 180px; }
  .cert-titulo { font-size: 34pt; font-weight: 900; color: #003d7c; text-align: right; }
  .cert-edu { font-size: 8pt; color: #888; text-transform: uppercase; letter-spacing: 2px; text-align: right; }

  /* Corpo */
  .cert-body { flex:1; text-align: center; }
  .certifica-label { font-size: 9pt; color:#888; letter-spacing: 2.5px; text-transform: uppercase; margin-bottom: 4px; }
  .cert-nome { font-size: 26pt; font-weight: 900; color: #003d7c; margin-bottom: 14px; line-height: 1.2; }
  .cert-texto { font-size: 11pt; color: #444; line-height: 1.8; margin-bottom: 16px; max-width: 580px; margin-left: auto; margin-right: auto; }
  .cert-curso-destaque { font-size: 15pt; font-weight: 900; color: #1a2a3a; display: block; margin: 4px 0; }

  /* Destaques */
  .cert-destaques {
    display: flex; justify-content: center; gap: 28px;
    background: #f0f5fb; border-radius: 6px;
    padding: 10px 20px; margin-bottom: 20px; flex-wrap: wrap;
  }
  .dest-label { font-size: 7.5pt; color: #8898aa; text-transform: uppercase; letter-spacing: 1px; }
  .dest-val   { font-size: 12pt; font-weight: 900; color: #003d7c; }

  /* Assinatura */
  .cert-assinaturas { display: flex; justify-content: flex-start; gap: 50px; margin-top: 16px; }
  .assin-linha { border-top: 1px solid #333; padding-top: 5px; margin-top: 36px; width: 160px; }
  .assin-nome  { font-size: 9pt; font-weight: 700; color: #222; }
  .assin-cargo { font-size: 8pt; color: #666; }

  /* Rodapé */
  .cert-footer {
    display: flex; justify-content: space-between; align-items: flex-end;
    margin-top: 16px; padding-top: 12px;
    border-top: 1px solid #dde6f0;
  }
  .codigo-label { font-size: 7pt; color: #aaa; text-transform: uppercase; }
  .codigo-val { font-family: monospace; font-size: 8pt; color: #888; word-break: break-all; max-width: 380px; }
  .qr-wrap { text-align: center; }
  .qr-wrap img { width: 60px; height: 60px; border: 1px solid #dde6f0; padding: 2px; }
  .qr-label { font-size: 6pt; color: #aaa; }

  /* ── VERSO ── */
  .verso-inner { font-size: 11pt; color: #333; line-height: 1.7; }
  .verso-header {
    display: flex; justify-content: space-between; align-items: center;
    padding-bottom: 12px; margin-bottom: 18px;
    border-bottom: 2px solid #003d7c;
  }
  .verso-titulo { font-size: 13pt; font-weight: 900; color: #003d7c; }
  .verso-curso  { font-size: 8.5pt; color: #888; }
  .verso-content h2 { font-size: 12pt; font-weight: 700; color: #003d7c; margin: 14px 0 6px; padding-bottom: 3px; border-bottom: 1px solid #e0eaf6; }
  .verso-content h3 { font-size: 10pt; font-weight: 700; color: #c8841a; text-transform: uppercase; letter-spacing: .5px; margin: 12px 0 4px; }
  .verso-content ul { padding-left: 18px; margin-bottom: 8px; }
  .verso-content ul li { margin-bottom: 3px; font-size: 10.5pt; }
  .verso-content p  { font-size: 10.5pt; margin-bottom: 8px; }
  .verso-content table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
  .verso-content table td, .verso-content table th { border: 1px solid #dde6f0; padding: 4px 8px; font-size: 9.5pt; }
  .verso-content table th { background: #f0f5fb; font-weight: 700; }
  .verso-footer {
    display: flex; justify-content: space-between; align-items: center;
    margin-top: 16px; padding-top: 12px;
    border-top: 1px solid #dde6f0;
    font-size: 7pt; color: #aaa;
  }

  /* Print */
  .no-print { background: #f0f4f9; padding: 16px; text-align: center; }
  @media print { .no-print { display: none !important; } }
</style>
</head>
<body>

<!-- ═══ FRENTE ═══ -->
<div class="cert-page">
  <div class="cert-sidebar"></div>

  <?php if (!empty($modelo['frente'])): ?>
  <img src="<?= APP_URL ?>/public/uploads/modelos/<?= e($modelo['frente']) ?>" class="cert-bg" alt="">
  <?php endif; ?>

  <div class="cert-inner">

    <div class="cert-header">
      <div class="logo-area">
        <div class="logo-circle">
          <svg viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
            <path d="M50 8C27 8 8 27 8 50s19 42 42 42 42-19 42-42S73 8 50 8zm0 8c11 0 21 4 29 11L19 79C12 71 8 61 8 50 8 27 27 16 50 16zm0 68c-11 0-21-4-29-11l60-52c7 8 11 18 11 29 0 23-19 34-42 34z"/>
          </svg>
        </div>
        <div>
          <div class="org-sigla">CRMV-TO</div>
          <div class="org-full">Conselho Regional de Medicina<br>Veterinária do Tocantins</div>
        </div>
      </div>
      <div>
        <div class="cert-titulo">Certificado</div>
        <div class="cert-edu">Educação Continuada</div>
      </div>
    </div>

    <div class="cert-body">
      <div class="certifica-label">O CRMV-TO certifica que</div>
      <div class="cert-nome"><?= e($cert['aluno_nome']) ?></div>

      <?php if ($textoFrente): ?>
      <p class="cert-texto"><?= nl2br(e($textoFrente)) ?></p>
      <?php else: ?>
      <p class="cert-texto">
        participou do
        <span class="cert-curso-destaque"><?= e($cert['curso_nome']) ?></span>
        realizado pelo Conselho Regional de Medicina Veterinária do Tocantins (CRMV-TO),
        tendo concluído com êxito todas as etapas do programa.
      </p>
      <?php endif; ?>

      <div class="cert-destaques">
        <div><div class="dest-label">Carga Horária</div><div class="dest-val"><?= $cert['carga_horaria'] ?> horas</div></div>
        <div><div class="dest-label">Data de Conclusão</div><div class="dest-val"><?= $dataConcl ?></div></div>
        <div><div class="dest-label">Modalidade</div><div class="dest-val"><?= strtoupper($cert['curso_tipo'] ?? 'EAD') ?></div></div>
      </div>

      <div class="cert-assinaturas">
        <div>
          <div class="assin-linha">
            <div class="assin-nome">Presidente do CRMV-TO</div>
            <div class="assin-cargo">Conselho Regional de Medicina Veterinária<br>do Tocantins</div>
          </div>
        </div>
      </div>
    </div>

    <div class="cert-footer">
      <div>
        <div class="codigo-label">Código de Verificação</div>
        <div class="codigo-val"><?= $cert['codigo'] ?></div>
        <div style="font-size:7pt;color:#ccc;margin-top:2px">Valide em: <?= $validarUrl ?></div>
      </div>
      <div class="qr-wrap">
        <img src="<?= $qrUrl ?>" alt="QR Code">
        <div class="qr-label">Escanear para validar</div>
      </div>
    </div>

  </div>
</div>

<?php if ($temVerso): ?>
<!-- ═══ VERSO ═══ -->
<div class="cert-page">
  <div class="cert-sidebar"></div>

  <?php if (!empty($modelo['verso'])): ?>
  <img src="<?= APP_URL ?>/public/uploads/modelos/<?= e($modelo['verso']) ?>" class="cert-bg" alt="">
  <?php endif; ?>

  <div class="cert-inner verso-inner">
    <div class="verso-header">
      <div>
        <div class="verso-titulo">Informações Complementares</div>
        <div class="verso-curso"><?= e($cert['curso_nome']) ?></div>
      </div>
      <div style="text-align:right">
        <div class="org-sigla" style="font-size:16pt">CRMV-TO</div>
        <div style="font-size:7pt;color:#aaa">Educação Continuada</div>
      </div>
    </div>

    <div class="verso-content">
      <?= $versoConteudo ?>
    </div>

    <div class="verso-footer">
      <span>CRMV-TO · Emitido em <?= $dataConcl ?> · Código: <?= substr($cert['codigo'],0,24) ?>...</span>
      <span><?= $validarUrl ?></span>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- Botão de impressão (some no print) -->
<div class="no-print">
  <p style="color:#374151;margin-bottom:12px">Use <strong>Ctrl+P</strong> → "Salvar como PDF" para baixar.</p>
  <button onclick="window.print()"
          style="background:#003d7c;color:#fff;border:none;padding:10px 28px;border-radius:8px;cursor:pointer;font-size:14px;font-weight:600">
    🖨️ Imprimir / Salvar PDF
  </button>
  <a href="<?= APP_URL ?>/aluno/certificado.php?curso_id=<?= $cursoId ?>"
     style="margin-left:14px;color:#003d7c;font-size:14px">← Voltar ao certificado</a>
</div>

<script>window.onload = () => setTimeout(() => window.print(), 400);</script>
</body>
</html>
