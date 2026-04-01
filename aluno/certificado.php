<?php
/**
 * aluno/certificado.php — CRMV EAD
 * Certificado institucional: frente (estilo ADAPEC/CRMV) + verso rico (CKEditor)
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

$modelo        = $certModel->modelo($cursoId);
$validarUrl    = APP_URL . '/validar.php?codigo=' . urlencode($cert['codigo']);
$dataConclusao = dataBR($mat['concluido_em'] ?? $cert['emitido_em']);

// Texto da frente: usa custom ou monta o padrão com variáveis
$textoFrenteCustom = $modelo['texto_frente'] ?? '';
if ($textoFrenteCustom) {
    $textoFrente = str_replace(
        ['[NOME]',            '[CURSO]',            '[CARGA_HORARIA]',         '[DATA]'],
        [$cert['aluno_nome'], $cert['curso_nome'],   $cert['carga_horaria'].'h', $dataConclusao],
        $textoFrenteCustom
    );
} else {
    $textoFrente = null; // usará padrão embutido no HTML
}

// Verso: HTML rico do CKEditor
$versoConteudo = $modelo['verso_conteudo'] ?? '';
$temVerso      = trim(strip_tags($versoConteudo)) !== '';

$pageTitle = 'Certificado — ' . $curso['nome'];
include __DIR__ . '/../app/views/layouts/aluno_header.php';
?>

<style>
/* ═══════════════════════════════════════════════════
   CERTIFICADO CRMV-TO — Estilo institucional
   Baseado no padrão visual ADAPEC/CRMV-TO
   ═══════════════════════════════════════════════════ */

/* ── Layout geral ──────────────────────────────── */
.cert-wrap {
  max-width: 900px;
  margin: 0 auto;
}
.cert-actions {
  display: flex; gap: 10px; justify-content: center;
  margin-bottom: 28px; flex-wrap: wrap;
}

/* ── Página (frente ou verso) ──────────────────── */
.cert-page {
  background: #fff;
  border-radius: 10px;
  overflow: hidden;
  box-shadow: 0 4px 28px rgba(0,0,0,.10);
  margin-bottom: 32px;
  position: relative;
}

/* ── Barra lateral colorida (estilo ADAPEC) ─────── */
.cert-sidebar {
  position: absolute;
  left: 0; top: 0; bottom: 0;
  width: 18px;
  background: linear-gradient(180deg, #003d7c 60%, #c8841a 100%);
}

/* ── Borda interna ──────────────────────────────── */
.cert-inner {
  margin: 14px 14px 14px 32px;
  border: 2px solid #003d7c;
  border-radius: 4px;
  padding: 36px 48px 32px;
  min-height: 480px;
  display: flex;
  flex-direction: column;
}

/* ── Cabeçalho ──────────────────────────────────── */
.cert-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding-bottom: 20px;
  margin-bottom: 24px;
  border-bottom: 2px solid #003d7c;
}
.cert-logo-left {
  display: flex;
  align-items: center;
  gap: 14px;
}
.cert-logo-circle {
  width: 66px; height: 66px;
  background: #003d7c;
  border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
}
.cert-logo-circle svg { width: 40px; height: 40px; fill: white; }
.cert-org-name { }
.cert-org-sigla {
  font-size: 26px; font-weight: 800; color: #003d7c;
  letter-spacing: 2px; line-height: 1;
}
.cert-org-full {
  font-size: 10px; color: #555; line-height: 1.4; max-width: 200px;
}
.cert-title-right {
  text-align: right;
}
.cert-titulo-word {
  font-size: 42px; font-weight: 800;
  color: #003d7c; letter-spacing: 1px;
  line-height: 1;
}
.cert-edu-label {
  font-size: 11px; color: #888;
  text-transform: uppercase; letter-spacing: 2px;
}

/* ── Corpo da frente ─────────────────────────────── */
.cert-body { flex: 1; }
.cert-certifica-label {
  font-size: 12px; color: #888;
  letter-spacing: 2.5px; text-transform: uppercase;
  text-align: center; margin-bottom: 6px;
}
.cert-nome {
  font-size: 30px; font-weight: 800;
  color: #003d7c;
  text-align: center;
  margin-bottom: 18px;
  line-height: 1.2;
}
.cert-texto-principal {
  font-size: 14px; color: #444;
  text-align: center;
  line-height: 1.8;
  margin-bottom: 20px;
  max-width: 640px;
  margin-left: auto; margin-right: auto;
}
.cert-curso-nome {
  font-size: 19px; font-weight: 800;
  color: #1a2a3a;
  display: block;
  margin: 6px 0;
}
.cert-periodo {
  font-size: 13px; color: #555;
  text-align: center;
  margin-bottom: 24px;
}

/* Destaques (período, carga horária) */
.cert-destaques {
  display: flex;
  justify-content: center;
  gap: 28px;
  background: #f0f5fb;
  border-radius: 8px;
  padding: 14px 24px;
  margin-bottom: 28px;
  flex-wrap: wrap;
}
.cert-destaque-item { text-align: center; }
.cert-destaque-label {
  font-size: 10px; color: #8898aa;
  text-transform: uppercase; letter-spacing: 1px;
}
.cert-destaque-valor {
  font-size: 15px; font-weight: 800; color: #003d7c;
}

/* Assinaturas */
.cert-assinaturas {
  display: flex;
  justify-content: flex-start;
  gap: 60px;
  margin-top: 20px;
  flex-wrap: wrap;
}
.cert-assinatura { text-align: center; }
.cert-assinatura-img {
  height: 52px;
  margin-bottom: 4px;
  display: flex; align-items: flex-end; justify-content: center;
}
.cert-assinatura-linha {
  border-top: 1px solid #333;
  padding-top: 6px;
  margin-top: 4px;
  width: 170px;
}
.cert-assinatura-nome { font-size: 12px; font-weight: 700; color: #222; }
.cert-assinatura-cargo { font-size: 11px; color: #666; }

/* Rodapé (código + QR) */
.cert-footer-bar {
  display: flex;
  justify-content: space-between;
  align-items: flex-end;
  margin-top: 24px;
  padding-top: 16px;
  border-top: 1px solid #dde6f0;
  flex-wrap: wrap;
  gap: 12px;
}
.cert-codigo-wrap { }
.cert-codigo-label { font-size: 10px; color: #aaa; text-transform: uppercase; letter-spacing: 1px; }
.cert-codigo-val {
  font-family: monospace; font-size: 11px; color: #888;
  word-break: break-all;
}
.cert-qr-wrap { text-align: center; }
.cert-qr-wrap img { width: 76px; height: 76px; border: 1px solid #dde6f0; padding: 3px; background: #fff; }
.cert-qr-label { font-size: 9px; color: #aaa; margin-top: 3px; }

/* ── VERSO ───────────────────────────────────────── */
.cert-verso .cert-inner { padding: 32px 48px; }
.cert-verso-header {
  display: flex; align-items: center; justify-content: space-between;
  padding-bottom: 16px; margin-bottom: 24px;
  border-bottom: 2px solid #003d7c;
}
.cert-verso-titulo {
  font-size: 16px; font-weight: 800; color: #003d7c;
}
.cert-verso-curso {
  font-size: 12px; color: #888;
}

/* Conteúdo rico do CKEditor */
.cert-verso-content {
  font-size: 13px; line-height: 1.7; color: #333;
}
.cert-verso-content h2 {
  font-size: 15px; font-weight: 700; color: #003d7c;
  margin-top: 20px; margin-bottom: 8px;
  padding-bottom: 4px; border-bottom: 1px solid #e0eaf6;
}
.cert-verso-content h3 {
  font-size: 13px; font-weight: 700; color: #c8841a;
  text-transform: uppercase; letter-spacing: .5px;
  margin-top: 16px; margin-bottom: 6px;
}
.cert-verso-content ul {
  padding-left: 20px; margin-bottom: 12px;
}
.cert-verso-content ul li {
  margin-bottom: 4px; padding-left: 4px;
}
.cert-verso-content table {
  width: 100%; border-collapse: collapse; margin-bottom: 12px;
}
.cert-verso-content table td,
.cert-verso-content table th {
  border: 1px solid #dde6f0; padding: 6px 10px; font-size: 12px;
}
.cert-verso-content table th { background: #f0f5fb; font-weight: 700; }

.cert-verso-footer {
  margin-top: 24px; padding-top: 14px;
  border-top: 1px solid #dde6f0;
  display: flex; justify-content: space-between; align-items: center;
  flex-wrap: wrap; gap: 10px;
}
.cert-verso-rodape { font-size: 10px; color: #aaa; line-height: 1.6; }
.cert-verso-codigo { font-family: monospace; font-size: 10px; color: #ccc; }

/* ── Impressão ───────────────────────────────────── */
@media print {
  .aluno-navbar,
  .aluno-footer,
  .cert-actions,
  .breadcrumb-nav { display: none !important; }

  .aluno-wrapper { padding: 0 !important; background: none !important; }
  .container-fluid { padding: 0 !important; }
  body { background: #fff !important; }

  .cert-page {
    box-shadow: none !important;
    border: none !important;
    margin-bottom: 0 !important;
    page-break-after: always;
    break-after: page;
  }
  .cert-inner { min-height: 0; }

  @page { size: A4 landscape; margin: 8mm; }
}
</style>

<!-- Navegação -->
<div class="breadcrumb-nav mb-4 d-flex align-items-center gap-3">
  <a href="<?= APP_URL ?>/aluno/dashboard.php" class="btn btn-sm btn-outline-secondary">
    <i class="bi bi-arrow-left"></i>
  </a>
  <div>
    <h5 class="mb-0">Certificado de Conclusão</h5>
    <small class="text-muted"><?= e($curso['nome']) ?></small>
  </div>
</div>

<!-- Botões -->
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

<div class="cert-wrap">

<!-- ════════════════════════════════════════════════
     FRENTE DO CERTIFICADO
     ════════════════════════════════════════════════ -->
<div class="cert-page" id="certFrente">
  <!-- Barra lateral colorida estilo ADAPEC -->
  <div class="cert-sidebar"></div>

  <!-- Imagem de fundo (se houver) -->
  <?php if (!empty($modelo['frente'])): ?>
  <img src="<?= APP_URL ?>/public/uploads/modelos/<?= e($modelo['frente']) ?>"
       style="position:absolute;inset:0;width:100%;height:100%;object-fit:cover;opacity:.08;z-index:0"
       alt="">
  <?php endif; ?>

  <div class="cert-inner" style="position:relative;z-index:1">

    <!-- Cabeçalho: Logo + Título -->
    <div class="cert-header">
      <div class="cert-logo-left">
        <div class="cert-logo-circle">
          <svg viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
            <path d="M50 8C27 8 8 27 8 50s19 42 42 42 42-19 42-42S73 8 50 8zm0 8c11 0 21 4 29 11L19 79C12 71 8 61 8 50 8 27 27 16 50 16zm0 68c-11 0-21-4-29-11l60-52c7 8 11 18 11 29 0 23-19 34-42 34z"/>
          </svg>
        </div>
        <div class="cert-org-name">
          <div class="cert-org-sigla">CRMV-TO</div>
          <div class="cert-org-full">Conselho Regional de Medicina<br>Veterinária do Tocantins</div>
        </div>
      </div>
      <div class="cert-title-right">
        <div class="cert-titulo-word">Certificado</div>
        <div class="cert-edu-label">Educação Continuada</div>
      </div>
    </div>

    <!-- Corpo -->
    <div class="cert-body">
      <p class="cert-certifica-label">O CRMV-TO certifica que</p>
      <div class="cert-nome"><?= e($cert['aluno_nome']) ?></div>

      <?php if ($textoFrente): ?>
      <!-- Texto customizado pelo admin -->
      <p class="cert-texto-principal"><?= nl2br(e($textoFrente)) ?></p>
      <?php else: ?>
      <!-- Texto padrão institucional -->
      <p class="cert-texto-principal">
        participou do
        <span class="cert-curso-nome"><?= e($cert['curso_nome']) ?></span>
        realizado pelo Conselho Regional de Medicina Veterinária do Tocantins (CRMV-TO),
        tendo concluído com êxito todas as etapas do programa.
      </p>
      <?php endif; ?>

      <!-- Destaques -->
      <div class="cert-destaques">
        <div class="cert-destaque-item">
          <div class="cert-destaque-label">Data de Conclusão</div>
          <div class="cert-destaque-valor"><?= $dataConclusao ?></div>
        </div>
        <div class="cert-destaque-item">
          <div class="cert-destaque-label">Carga Horária</div>
          <div class="cert-destaque-valor"><?= $cert['carga_horaria'] ?> horas</div>
        </div>
        <div class="cert-destaque-item">
          <div class="cert-destaque-label">Modalidade</div>
          <div class="cert-destaque-valor"><?= strtoupper($cert['curso_tipo'] ?? 'EAD') ?></div>
        </div>
      </div>

      <!-- Área de assinatura -->
      <div class="cert-assinaturas">
        <div class="cert-assinatura">
          <div class="cert-assinatura-img">
            <!-- Espaço para assinatura digitalizada -->
          </div>
          <div class="cert-assinatura-linha">
            <div class="cert-assinatura-nome">Presidente do CRMV-TO</div>
            <div class="cert-assinatura-cargo">Conselho Regional de Medicina Veterinária<br>do Tocantins</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Rodapé: Código + QR Code -->
    <div class="cert-footer-bar">
      <div class="cert-codigo-wrap">
        <div class="cert-codigo-label">Código de Verificação</div>
        <div class="cert-codigo-val"><?= $cert['codigo'] ?></div>
        <div style="font-size:10px;color:#ccc;margin-top:3px">Valide em: <?= e($validarUrl) ?></div>
      </div>
      <div class="cert-qr-wrap">
        <img src="https://api.qrserver.com/v1/create-qr-code/?size=76x76&data=<?= urlencode($validarUrl) ?>"
             alt="QR Code">
        <div class="cert-qr-label">Escanear para validar</div>
      </div>
    </div>

  </div><!-- /.cert-inner -->
</div><!-- /.cert-page frente -->


<!-- ════════════════════════════════════════════════
     VERSO DO CERTIFICADO (HTML rico do CKEditor)
     ════════════════════════════════════════════════ -->
<?php if ($temVerso): ?>
<div class="cert-page cert-verso">
  <div class="cert-sidebar"></div>

  <?php if (!empty($modelo['verso'])): ?>
  <img src="<?= APP_URL ?>/public/uploads/modelos/<?= e($modelo['verso']) ?>"
       style="position:absolute;inset:0;width:100%;height:100%;object-fit:cover;opacity:.06;z-index:0"
       alt="">
  <?php endif; ?>

  <div class="cert-inner" style="position:relative;z-index:1">

    <!-- Cabeçalho do verso -->
    <div class="cert-verso-header">
      <div>
        <div class="cert-verso-titulo">
          <i class="bi bi-list-check me-2"></i>Informações Complementares
        </div>
        <div class="cert-verso-curso"><?= e($cert['curso_nome']) ?></div>
      </div>
      <div style="text-align:right">
        <div class="cert-org-sigla" style="font-size:18px">CRMV-TO</div>
        <div style="font-size:10px;color:#aaa">Educação Continuada</div>
      </div>
    </div>

    <!-- Conteúdo rico do CKEditor -->
    <div class="cert-verso-content">
      <?= $versoConteudo /* HTML sanitizado pelo CKEditor — nunca vem de input direto do aluno */ ?>
    </div>

    <!-- Rodapé do verso -->
    <div class="cert-verso-footer">
      <div class="cert-verso-rodape">
        <strong>CRMV-TO</strong> · Conselho Regional de Medicina Veterinária do Tocantins<br>
        Certificado emitido em <?= $dataConclusao ?> · Este documento pode ser verificado online.
      </div>
      <div class="cert-verso-codigo"><?= substr($cert['codigo'], 0, 20) ?>...</div>
    </div>

  </div>
</div>
<?php endif; ?>

</div><!-- /.cert-wrap -->

<?php include __DIR__ . '/../app/views/layouts/aluno_footer.php'; ?>
