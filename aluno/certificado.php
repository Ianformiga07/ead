<?php
/**
 * aluno/certificado.php — CRMV-TO EAD
 * Certificado institucional padrão CRMV-TO com frente e verso
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
    setFlash('error', 'Você precisa concluir o curso para emitir o certificado.');
    redirect(APP_URL . '/aluno/dashboard.php');
}

// Buscar ou criar certificado
$cert = $certModel->buscar($user['id'], $cursoId);
if (!$cert) {
    $codigo = gerarCodigoCertificado();
    $certModel->criar($user['id'], $cursoId, $codigo);
    $cert = $certModel->buscar($user['id'], $cursoId);
}

$validarUrl  = APP_URL . '/validar.php?codigo=' . urlencode($cert['codigo']);
$dataConclusao = dataBR($mat['concluido_em'] ?? $cert['emitido_em']);

// Formatar conteúdo programático em lista
$conteudoItems = [];
if (!empty($cert['conteudo_programatico'])) {
    $conteudoItems = array_filter(array_map('trim', explode("\n", $cert['conteudo_programatico'])));
}

// Formatar instrutores em lista
$instrutoresList = [];
if (!empty($cert['instrutores'])) {
    $instrutoresList = array_filter(array_map('trim', explode(',', $cert['instrutores'])));
}

$pageTitle = 'Certificado — ' . $curso['nome'];
include __DIR__ . '/../app/views/layouts/aluno_header.php';
?>

<style>
/* ── Geral ──────────────────────────────────────── */
.cert-actions { display: flex; gap: 10px; justify-content: center; margin-bottom: 32px; flex-wrap: wrap; }

/* ── Certificado Frente ─────────────────────────── */
.cert-page {
    background: #fff;
    width: 100%;
    max-width: 860px;
    margin: 0 auto 32px;
    border: 1px solid #dde3ec;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 4px 24px rgba(0,0,0,.08);
    page-break-after: always;
}
.cert-border {
    border: 8px solid #003d7c;
    margin: 12px;
    border-radius: 4px;
    padding: 40px 50px;
}
.cert-header { text-align: center; margin-bottom: 32px; border-bottom: 2px solid #003d7c; padding-bottom: 24px; }
.cert-logo-area { display: flex; align-items: center; justify-content: center; gap: 16px; margin-bottom: 12px; }
.cert-logo-icon {
    width: 72px; height: 72px; background: #003d7c; border-radius: 50%;
    display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}
.cert-logo-icon svg { width: 44px; height: 44px; fill: white; }
.cert-org-name { text-align: left; }
.cert-org-sigla { font-size: 28px; font-weight: 800; color: #003d7c; letter-spacing: 2px; line-height: 1; }
.cert-org-full { font-size: 11px; color: #555; line-height: 1.4; max-width: 220px; }
.cert-subtitulo { font-size: 13px; color: #666; letter-spacing: 3px; text-transform: uppercase; margin-top: 4px; }

.cert-body { text-align: center; }
.cert-certifica { font-size: 13px; color: #666; letter-spacing: 3px; text-transform: uppercase; margin-bottom: 8px; }
.cert-nome { font-size: 34px; font-weight: 700; color: #003d7c; margin-bottom: 16px; line-height: 1.2; }
.cert-texto { font-size: 14px; color: #444; line-height: 1.8; margin-bottom: 24px; }
.cert-curso-destaque { font-size: 20px; font-weight: 700; color: #c8841a; display: block; margin: 8px 0; }
.cert-detalhes {
    display: flex; justify-content: center; gap: 32px; margin: 24px 0;
    padding: 16px; background: #f0f5ff; border-radius: 8px; flex-wrap: wrap;
}
.cert-detalhe-item { text-align: center; }
.cert-detalhe-label { font-size: 11px; color: #888; text-transform: uppercase; letter-spacing: 1px; }
.cert-detalhe-valor { font-size: 16px; font-weight: 700; color: #003d7c; }

.cert-assinaturas { display: flex; justify-content: center; gap: 60px; margin-top: 36px; flex-wrap: wrap; }
.cert-assinatura { text-align: center; }
.cert-assinatura-linha { border-top: 1px solid #333; padding-top: 8px; margin-top: 40px; width: 180px; }
.cert-assinatura-nome { font-size: 12px; font-weight: 700; color: #333; }
.cert-assinatura-cargo { font-size: 11px; color: #666; }

.cert-footer {
    margin-top: 32px; padding-top: 16px; border-top: 1px solid #dde3ec;
    display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;
}
.cert-codigo { font-family: monospace; font-size: 11px; color: #888; word-break: break-all; }
.cert-qr { text-align: center; }
.cert-qr img { width: 80px; height: 80px; }

/* ── Verso ──────────────────────────────────────── */
.cert-verso .cert-border { padding: 36px 50px; }
.cert-verso-title { font-size: 16px; font-weight: 700; color: #003d7c; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #003d7c; }
.cert-section { margin-bottom: 28px; }
.cert-section h3 { font-size: 13px; font-weight: 700; color: #c8841a; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 12px; }
.cert-conteudo-list { list-style: none; padding: 0; margin: 0; }
.cert-conteudo-list li { padding: 6px 0 6px 20px; border-bottom: 1px solid #f0f0f0; font-size: 13px; color: #444; position: relative; }
.cert-conteudo-list li::before { content: '▸'; position: absolute; left: 0; color: #003d7c; }
.cert-instrutores-grid { display: flex; gap: 16px; flex-wrap: wrap; }
.cert-instrutor-card { background: #f7f9fc; border: 1px solid #dde3ec; border-radius: 6px; padding: 10px 16px; }
.cert-instrutor-nome { font-size: 13px; font-weight: 600; color: #333; }
.cert-instrutor-label { font-size: 11px; color: #888; }

/* ── Impressão ──────────────────────────────────── */
@media print {
    .aluno-navbar, .aluno-footer, .cert-actions, .mb-4, .breadcrumb { display: none !important; }
    .aluno-wrapper { padding: 0 !important; background: none !important; }
    .container-fluid { padding: 0 !important; }
    .cert-page { box-shadow: none !important; border: none !important; max-width: 100% !important; margin: 0 !important; }
    .cert-border { margin: 0 !important; border: 8px solid #003d7c !important; }
    @page { size: A4 landscape; margin: 10mm; }
}
</style>

<!-- Navegação -->
<div class="mb-4 d-flex align-items-center gap-3">
  <a href="<?= APP_URL ?>/aluno/dashboard.php" class="btn btn-sm btn-outline-secondary">
    <i class="bi bi-arrow-left"></i>
  </a>
  <div>
    <h4 class="mb-0">Certificado de Conclusão</h4>
    <small class="text-muted"><?= e($curso['nome']) ?></small>
  </div>
</div>

<!-- Botões de ação -->
<div class="cert-actions">
  <button onclick="window.print()" class="btn btn-primary px-4">
    <i class="bi bi-printer me-2"></i>Imprimir / Salvar PDF
  </button>
  <a href="<?= $validarUrl ?>" target="_blank" class="btn btn-outline-success px-4">
    <i class="bi bi-patch-check me-2"></i>Validar Online
  </a>
  <a href="<?= APP_URL ?>/aluno/dashboard.php" class="btn btn-outline-secondary px-4">
    <i class="bi bi-grid me-2"></i>Meus Cursos
  </a>
</div>

<!-- ════════════════════════════════════════════════════════
     FRENTE DO CERTIFICADO
     ════════════════════════════════════════════════════════ -->
<div class="cert-page" id="certFrente">
  <div class="cert-border">

    <!-- Cabeçalho Institucional -->
    <div class="cert-header">
      <div class="cert-logo-area">
        <div class="cert-logo-icon">
          <!-- Ícone veterinário estilizado -->
          <svg viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
            <path d="M50 10C28 10 10 28 10 50s18 40 40 40 40-18 40-40S72 10 50 10zm0 6c9 0 17 3 24 8L18 72c-5-7-8-15-8-22C10 31 29 16 50 16zm0 68c-9 0-17-3-24-8l56-48c5 7 8 15 8 22C90 69 71 84 50 84z"/>
          </svg>
        </div>
        <div class="cert-org-name">
          <div class="cert-org-sigla">CRMV-TO</div>
          <div class="cert-org-full">Conselho Regional de Medicina<br>Veterinária do Tocantins</div>
        </div>
      </div>
      <div class="cert-subtitulo">Plataforma de Educação Continuada</div>
    </div>

    <!-- Corpo -->
    <div class="cert-body">
      <p class="cert-certifica">Certificamos que</p>
      <div class="cert-nome"><?= e($cert['aluno_nome']) ?></div>

      <p class="cert-texto">
        concluiu com êxito o curso
        <span class="cert-curso-destaque"><?= e($cert['curso_nome']) ?></span>
        realizado pelo Conselho Regional de Medicina Veterinária do Tocantins (CRMV-TO),
        tendo demonstrado dedicação e aproveitamento satisfatório em todas as etapas do programa.
      </p>

      <div class="cert-detalhes">
        <div class="cert-detalhe-item">
          <div class="cert-detalhe-label">Carga Horária</div>
          <div class="cert-detalhe-valor"><?= $cert['carga_horaria'] ?>h</div>
        </div>
        <div class="cert-detalhe-item">
          <div class="cert-detalhe-label">Data de Conclusão</div>
          <div class="cert-detalhe-valor"><?= $dataConclusao ?></div>
        </div>
        <div class="cert-detalhe-item">
          <div class="cert-detalhe-label">Modalidade</div>
          <div class="cert-detalhe-valor">EAD</div>
        </div>
      </div>

      <!-- Assinaturas -->
      <div class="cert-assinaturas">
        <div class="cert-assinatura">
          <div class="cert-assinatura-linha">
            <div class="cert-assinatura-nome">Presidente do CRMV-TO</div>
            <div class="cert-assinatura-cargo">Conselho Regional de Medicina Veterinária<br>do Tocantins</div>
          </div>
        </div>
        <?php if (!empty($instrutoresList)): ?>
        <div class="cert-assinatura">
          <div class="cert-assinatura-linha">
            <div class="cert-assinatura-nome"><?= e(reset($instrutoresList)) ?></div>
            <div class="cert-assinatura-cargo">Instrutor(a) Responsável</div>
          </div>
        </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Rodapé com código e QR -->
    <div class="cert-footer">
      <div>
        <div class="cert-codigo">
          <strong>Código de Verificação:</strong><br>
          <?= $cert['codigo'] ?>
        </div>
        <small style="font-size:10px;color:#aaa">Valide em: <?= e($validarUrl) ?></small>
      </div>
      <div class="cert-qr">
        <img src="https://api.qrserver.com/v1/create-qr-code/?size=80x80&data=<?= urlencode($validarUrl) ?>"
             alt="QR Code de Validação">
        <div style="font-size:10px;color:#aaa;margin-top:4px">Escanear para validar</div>
      </div>
    </div>

  </div><!-- /.cert-border -->
</div><!-- /.cert-page (frente) -->


<!-- ════════════════════════════════════════════════════════
     VERSO DO CERTIFICADO
     ════════════════════════════════════════════════════════ -->
<?php if (!empty($conteudoItems) || !empty($instrutoresList)): ?>
<div class="cert-page cert-verso">
  <div class="cert-border">

    <div class="cert-verso-title">
      <i class="bi bi-list-check me-2"></i>Informações Complementares — <?= e($cert['curso_nome']) ?>
    </div>

    <?php if (!empty($conteudoItems)): ?>
    <div class="cert-section">
      <h3><i class="bi bi-book me-1"></i>Conteúdo Programático</h3>
      <ul class="cert-conteudo-list">
        <?php foreach ($conteudoItems as $item): ?>
        <li><?= e($item) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
    <?php endif; ?>

    <?php if (!empty($instrutoresList)): ?>
    <div class="cert-section">
      <h3><i class="bi bi-person-check me-1"></i>Corpo Docente</h3>
      <div class="cert-instrutores-grid">
        <?php foreach ($instrutoresList as $i => $inst): ?>
        <div class="cert-instrutor-card">
          <div class="cert-instrutor-label">Instrutor(a) <?= $i + 1 ?></div>
          <div class="cert-instrutor-nome"><?= e(trim($inst)) ?></div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <!-- Rodapé verso -->
    <div style="margin-top:auto;padding-top:20px;border-top:1px solid #dde3ec;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;">
      <div style="font-size:11px;color:#888">
        <strong>CRMV-TO</strong> · Conselho Regional de Medicina Veterinária do Tocantins<br>
        Este documento é emitido eletronicamente e pode ser verificado no endereço indicado na frente.
      </div>
      <div style="font-size:11px;color:#aaa;font-family:monospace"><?= substr($cert['codigo'], 0, 16) ?>...</div>
    </div>

  </div>
</div>
<?php endif; ?>

<?php include __DIR__ . '/../app/views/layouts/aluno_footer.php'; ?>
