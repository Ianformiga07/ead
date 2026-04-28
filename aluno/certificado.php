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

// Dados do aluno completos (busca direto para pegar crmv)
$userModel  = new UsuarioModel();
$alunoData  = $userModel->findById($user['id']);
$alunoCrmv  = $alunoData['crmv'] ?? '';

// Instrutor e nome do certificado do modelo
$nomeCert   = !empty($modelo['nome_cert'])  ? $modelo['nome_cert']  : $cert['curso_nome'];
$instrutor  = !empty($modelo['instrutor'])  ? $modelo['instrutor']  : ($curso['instrutores'] ?? '');
$contProg   = $modelo['conteudo_prog']       ?? $curso['conteudo_programatico'] ?? '';

// Texto da frente: usa custom ou padrão
$substituicoes = [
    '[NOME]'          => $cert['aluno_nome'],
    '[CURSO]'         => $cert['curso_nome'],
    '[CARGA_HORARIA]' => $cert['carga_horaria'] . 'h',
    '[DATA]'          => $dataConclusao,
    '[CRMV]'          => $alunoCrmv,
    '[INSTRUTOR]'     => $instrutor,
];

$textoFrenteCustom = $modelo['texto_frente'] ?? '';
if ($textoFrenteCustom) {
    $textoFrente = str_replace(array_keys($substituicoes), array_values($substituicoes), $textoFrenteCustom);
} else {
    $textoFrente = null; // usará padrão embutido no HTML
}

// Verso: ativado no modelo?
$ativarVerso   = ($modelo['ativar_verso'] ?? 0) == 1;
$versoConteudo = $modelo['verso_conteudo'] ?? '';
$temVerso      = $ativarVerso && trim(strip_tags($contProg)) !== '';

$pageTitle = 'Certificado — ' . $curso['nome'];
include __DIR__ . '/../app/views/layouts/aluno_header.php';
?>

<style>
/* ═══════════════════════════════════════════════════
   CERTIFICADO CRMV-TO — Estilo institucional
   ═══════════════════════════════════════════════════ */

/* ── Área de visualização na tela ──────────────────── */
.cert-actions {
  display: flex; gap: 10px; justify-content: center;
  margin-bottom: 20px; flex-wrap: wrap;
}
.cert-wrap {
  /* Centraliza os cards na tela */
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 32px;
  padding-bottom: 40px;
}

/* ── Página A4: tamanho FIXO real ──────────────────── */
/* A4 landscape = 297mm × 210mm  */
.cert-page {
  width:  297mm;
  height: 210mm;
  background: #fff;
  border-radius: 6px;
  overflow: hidden;
  box-shadow: 0 4px 28px rgba(0,0,0,.14);
  position: relative;
  flex-shrink: 0;
  box-sizing: border-box;

  /* Escala proporcional para caber na tela — JS calcula --cert-scale */
  transform-origin: top center;
  transform: scale(var(--cert-scale, 1));
  /* Remove espaco fantasma que transform deixa no fluxo */
  margin-bottom: calc((var(--cert-scale, 1) - 1) * 210mm);
}

/* ── Barra lateral colorida ─────────────────────────── */
.cert-sidebar {
  position: absolute;
  left: 0; top: 0; bottom: 0;
  width: 14px;
  background: linear-gradient(180deg, #003d7c 60%, #c8841a 100%);
  z-index: 2;
}

/* ── Borda interna ──────────────────────────────────── */
.cert-inner {
  position: absolute;
  inset: 10px 10px 10px 24px;  /* deixa espaço para a barra lateral */
  border: 2px solid #003d7c;
  border-radius: 4px;
  padding: 20px 32px 16px;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  box-sizing: border-box;
}

/* ── Cabeçalho ──────────────────────────────────────── */
.cert-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding-bottom: 12px;
  margin-bottom: 12px;
  border-bottom: 2px solid #003d7c;
  flex-shrink: 0;
}
.cert-logo-left { display: flex; align-items: center; gap: 10px; }
.cert-logo-circle {
  width: 52px; height: 52px;
  background: #003d7c;
  border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
}
.cert-logo-circle svg { width: 32px; height: 32px; fill: white; }
.cert-org-sigla  { font-size: 22px; font-weight: 800; color: #003d7c; letter-spacing: 2px; line-height: 1; }
.cert-org-full   { font-size: 9px; color: #555; line-height: 1.4; max-width: 180px; }
.cert-title-right { text-align: right; }
.cert-titulo-word { font-size: 34px; font-weight: 800; color: #003d7c; letter-spacing: 1px; line-height: 1; }
.cert-edu-label   { font-size: 10px; color: #888; text-transform: uppercase; letter-spacing: 2px; }

/* ── Corpo ──────────────────────────────────────────── */
.cert-body { flex: 1; display: flex; flex-direction: column; justify-content: center; overflow: hidden; }

.cert-certifica-label {
  font-size: 10px; color: #888;
  letter-spacing: 2.5px; text-transform: uppercase;
  text-align: center; margin-bottom: 4px;
}
.cert-nome {
  font-size: 24px; font-weight: 800; color: #003d7c;
  text-align: center; margin-bottom: 10px; line-height: 1.2;
}
.cert-texto-principal {
  font-size: 12px; color: #444; text-align: center;
  line-height: 1.6; margin-bottom: 10px;
  max-width: 580px; margin-left: auto; margin-right: auto;
}
.cert-curso-nome { font-size: 15px; font-weight: 800; color: #1a2a3a; display: block; margin: 4px 0; }

/* Destaques */
.cert-destaques {
  display: flex; justify-content: center; gap: 24px;
  background: #f0f5fb; border-radius: 6px;
  padding: 8px 20px; margin-bottom: 10px; flex-wrap: wrap; flex-shrink: 0;
}
.cert-destaque-item { text-align: center; }
.cert-destaque-label { font-size: 9px; color: #8898aa; text-transform: uppercase; letter-spacing: 1px; }
.cert-destaque-valor { font-size: 13px; font-weight: 800; color: #003d7c; }

/* Assinaturas */
.cert-assinaturas  { display: flex; justify-content: flex-start; gap: 40px; margin-top: 8px; flex-wrap: wrap; flex-shrink: 0; }
.cert-assinatura   { text-align: center; }
.cert-assinatura-img { height: 36px; margin-bottom: 2px; display: flex; align-items: flex-end; justify-content: center; }
.cert-assinatura-linha { border-top: 1px solid #333; padding-top: 4px; margin-top: 2px; width: 150px; }
.cert-assinatura-nome  { font-size: 11px; font-weight: 700; color: #222; }
.cert-assinatura-cargo { font-size: 10px; color: #666; }

/* Rodapé */
.cert-footer-bar {
  display: flex; justify-content: space-between; align-items: flex-end;
  margin-top: 8px; padding-top: 8px; border-top: 1px solid #dde6f0;
  flex-wrap: wrap; gap: 8px; flex-shrink: 0;
}
.cert-codigo-label { font-size: 9px; color: #aaa; text-transform: uppercase; letter-spacing: 1px; }
.cert-codigo-val   { font-family: monospace; font-size: 10px; color: #888; word-break: break-all; }
.cert-qr-wrap      { text-align: center; }
.cert-qr-wrap img  { width: 60px; height: 60px; border: 1px solid #dde6f0; padding: 2px; background: #fff; }
.cert-qr-label     { font-size: 8px; color: #aaa; margin-top: 2px; }

/* ── VERSO ───────────────────────────────────────────── */
.cert-verso .cert-inner { padding: 18px 32px 14px; }
.cert-verso-header {
  display: flex; align-items: center; justify-content: space-between;
  padding-bottom: 10px; margin-bottom: 12px; border-bottom: 2px solid #003d7c;
  flex-shrink: 0;
}
.cert-verso-titulo { font-size: 14px; font-weight: 800; color: #003d7c; }
.cert-verso-curso  { font-size: 11px; color: #888; }

/* ── CONTEÚDO PROGRAMÁTICO ────────────────────────────
   CORREÇÃO 3: white-space: pre-line preserva 
 e bullets (•, -)
   sem quebrar HTML rico — aplicado APENAS ao wrapper de texto puro.
   Para HTML do CKEditor usamos classe separada .cert-verso-html
   ──────────────────────────────────────────────────── */
.cert-verso-content {
  font-size: 12px; line-height: 1.6; color: #333;
  flex: 1; overflow: hidden;
}
/* Texto puro (legado): preserva quebras e bullets */
.cert-verso-plain {
  white-space: pre-line;   /* respeita 
 e espaços, não quebra layout HTML */
  font-size: 12px; line-height: 1.6; color: #333;
}
/* HTML rico (CKEditor): comportamento normal */
.cert-verso-html {
  white-space: normal;
  font-size: 12px; line-height: 1.6; color: #333;
}
.cert-verso-html h2 { font-size: 13px; font-weight: 700; color: #003d7c; margin: 10px 0 4px; border-bottom: 1px solid #e0eaf6; padding-bottom: 2px; }
.cert-verso-html h3 { font-size: 12px; font-weight: 700; color: #c8841a; text-transform: uppercase; letter-spacing: .4px; margin: 8px 0 4px; }
.cert-verso-html ul { padding-left: 22px; margin: 4px 0 8px 0; list-style-type: disc !important; }
.cert-verso-html ol { padding-left: 22px; margin: 4px 0 8px 0; list-style-type: decimal !important; }
.cert-verso-html ul li, .cert-verso-html ol li { margin-bottom: 3px; display: list-item !important; }
.cert-verso-html table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
.cert-verso-html table td,
.cert-verso-html table th { border: 1px solid #dde6f0; padding: 4px 8px; font-size: 11px; }
.cert-verso-html table th { background: #f0f5fb; font-weight: 700; }
.cert-verso-html p { margin: 0 0 6px; }
.cert-verso-html strong, .cert-verso-html b { font-weight: bold; }
.cert-verso-html em, .cert-verso-html i { font-style: italic; }

.cert-verso-footer {
  margin-top: 8px; padding-top: 8px; border-top: 1px solid #dde6f0;
  display: flex; justify-content: space-between; align-items: center;
  flex-wrap: wrap; gap: 6px; flex-shrink: 0;
}
.cert-verso-rodape  { font-size: 9px; color: #aaa; line-height: 1.5; }
.cert-verso-codigo  { font-family: monospace; font-size: 9px; color: #ccc; }

/* ══════════════════════════════════════════════════════
   IMPRESSÃO — A4 LANDSCAPE PERFEITO
   Cada .cert-page = 1 folha A4 completa.
   ══════════════════════════════════════════════════════ */
@media print {
  /* Na impressão: cancela a escala JS e deixa o @page controlar */
  .cert-page {
    transform: none !important;
    margin-bottom: 0 !important;
  }

  /* Ocultar tudo que não é certificado */
  .aluno-navbar,
  .aluno-footer,
  .cert-actions,
  .breadcrumb-nav { display: none !important; }

  html, body {
    margin: 0 !important; padding: 0 !important;
    background: #fff !important;
    -webkit-print-color-adjust: exact !important;
    print-color-adjust: exact !important;
    width: 100% !important; height: 100% !important;
  }

  .aluno-wrapper,
  .container-fluid,
  .cert-wrap {
    display: block !important;
    margin: 0 !important; padding: 0 !important;
    width: 100% !important;
  }

  /* Cada .cert-page ocupa 1 página A4 inteira */
  .cert-page {
    /* Dimensão exata A4 landscape */
    width:  297mm !important;
    height: 210mm !important;
    /* Sem visual de card */
    box-shadow: none !important;
    border-radius: 0 !important;
    overflow: hidden !important;
    margin: 0 !important;
    padding: 0 !important;
    position: relative !important;
    /* Cada página começa numa nova folha; a 1ª não cria folha em branco */
    break-before: page;
    page-break-before: always;
    /* Sem quebra interna */
    break-inside: avoid !important;
    page-break-inside: avoid !important;
    /* Garante que não vaze para a próxima folha */
    break-after: avoid;
    page-break-after: avoid;
  }
  /* Primeira página: sem quebra antes (evita folha em branco inicial) */
  .cert-page:first-child {
    break-before: auto !important;
    page-break-before: auto !important;
  }

  /* Borda interna: usa inset absoluto para preencher a página */
  .cert-inner {
    position: absolute !important;
    inset: 8mm 8mm 8mm 18mm !important;
    padding: 14px 28px 12px !important;
    margin: 0 !important;
    overflow: hidden !important;
  }

  /* Definição da página */
  @page {
    size: A4 landscape;
    margin: 0;
  }
}
</style>

<script>
/* ── Escala responsiva do certificado na tela ────────────────────
   Calcula quanto o card A4 (297mm) precisa encolher para caber
   na viewport com 32px de margem em cada lado.
   Na impressão o @media print cancela o transform via CSS.
   ────────────────────────────────────────────────────────────── */
(function () {
  function ajustarEscala() {
    /* Largura disponível = janela menos margens laterais */
    var disponivelPx = window.innerWidth - 64;

    /* Converte 297mm para px usando devicePixelRatio-aware dpi.
       96dpi é o padrão CSS: 1mm = 96/25.4 px */
    var mmToPx      = 96 / 25.4;
    var larguraCard = 297 * mmToPx;  /* ~1122px */

    var escala = disponivelPx < larguraCard
                 ? disponivelPx / larguraCard
                 : 1;   /* nunca amplia, só reduz */

    document.documentElement.style.setProperty('--cert-scale', escala.toFixed(4));
  }

  /* Aplica ao carregar e ao redimensionar */
  ajustarEscala();
  window.addEventListener('resize', ajustarEscala);
})();
</script>

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

  <!-- Imagem de fundo removida: o layout gerado pelo sistema e suficiente -->

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
        <?php if (!empty($nomeCert) && $nomeCert !== $cert['curso_nome']): ?>
        <div style="font-size:11px;color:#c8841a;font-weight:600;margin-top:2px"><?= e($nomeCert) ?></div>
        <?php endif; ?>
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

  <!-- Imagem de fundo do verso removida: layout gerado pelo sistema -->

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

    <!-- Conteúdo Programático (se houver) -->
    <?php if (trim(strip_tags($contProg))): ?>
    <div class="cert-verso-content" style="margin-bottom:10px">
      <?php
        // Detecta se é HTML rico (CKEditor) ou texto puro (legado)
        $isHtml = (bool) preg_match('/<[a-z][^>]*>/i', $contProg);
      ?>
      <?php if ($isHtml): ?>
        <!-- HTML rico do CKEditor: white-space:normal -->
        <div class="cert-verso-html">
          <h3 style="font-size:11px;font-weight:700;color:#c8841a;text-transform:uppercase;letter-spacing:.4px;margin:0 0 6px">Conteúdo Programático</h3>
          <?= $contProg ?>
        </div>
      <?php else: ?>
        <!-- Texto puro: white-space:pre-line preserva 
 e bullets (•,-) -->
        <h3 style="font-size:11px;font-weight:700;color:#c8841a;text-transform:uppercase;letter-spacing:.4px;margin:0 0 4px;white-space:normal">Conteúdo Programático</h3>
        <div class="cert-verso-plain"><?= htmlspecialchars($contProg, ENT_QUOTES) ?></div>
      <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- verso_conteudo removido conforme solicitação -->

    <!-- Instrutor(es) no verso -->
    <?php if ($instrutor): ?>
    <div style="margin-top:16px;padding-top:12px;border-top:1px solid #dde6f0">
      <strong style="font-size:12px;color:#003d7c">Instrutor(es):</strong>
      <span style="font-size:12px;color:#374151;margin-left:6px"><?= htmlspecialchars($instrutor, ENT_QUOTES) ?></span>
    </div>
    <?php endif; ?>

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