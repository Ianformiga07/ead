<?php
/**
 * admin/certificados.php — CRMV EAD
 * Configuração completa de certificados:
 *   - Imagens de frente/verso
 *   - Texto customizado da frente
 *   - Verso HTML rico via CKEditor (conteúdo programático + instrutores)
 *   - Lista de certificados emitidos
 */
require_once __DIR__ . '/../app/bootstrap.php';
authCheck('admin');

$certModel  = new CertificadoModel();
$cursoModel = new CursoModel();
$cursoId    = (int)($_GET['curso_id'] ?? 0);

/* ── SALVAR CONFIGURAÇÃO DO CERTIFICADO ─────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfCheck();
    $cid = (int)($_POST['curso_id'] ?? 0);

    if (!$cid) {
        setFlash('error', 'Selecione um curso.');
        redirect(APP_URL . '/admin/certificados.php');
    }

    $d = [];

    // Upload: imagem frente
    if (!empty($_FILES['frente']['name'])) {
        $f = uploadFile($_FILES['frente'], MODEL_PATH, ALLOWED_IMAGE);
        if ($f) $d['frente'] = $f;
        else    setFlash('error', 'Erro no upload da imagem da frente.');
    }

    // Upload: imagem verso
    if (!empty($_FILES['verso']['name'])) {
        $v = uploadFile($_FILES['verso'], MODEL_PATH, ALLOWED_IMAGE);
        if ($v) $d['verso'] = $v;
        else    setFlash('error', 'Erro no upload da imagem do verso.');
    }

    // Texto da frente (customizável)
    $d['texto_frente'] = strip_tags($_POST['texto_frente'] ?? '', '<br><b><strong><em><i>');

    // Verso HTML rico do CKEditor — permite HTML seguro
    $d['verso_conteudo'] = $_POST['verso_conteudo'] ?? '';

    $certModel->salvarModelo($cid, $d);
    setFlash('success', 'Configuração do certificado salva com sucesso!');
    redirect(APP_URL . '/admin/certificados.php?curso_id=' . $cid);
}

/* ── DADOS ──────────────────────────────────────── */
$cursos = $cursoModel->cursosAtivos();
$certs  = $cursoId ? $certModel->doCurso($cursoId) : [];
$modelo = $cursoId ? $certModel->modelo($cursoId) : null;
$curso  = $cursoId ? $cursoModel->findById($cursoId) : null;

$pageTitle = 'Configurar Certificados';
include __DIR__ . '/../app/views/layouts/admin_header.php';
?>

<div class="page-header">
  <div>
    <h1><i class="bi bi-award-fill me-2 text-warning"></i>Configuração de Certificados</h1>
    <p class="page-subtitle">Personalize o modelo, frente e verso de cada curso</p>
  </div>
</div>

<!-- Seleção de curso (via GET, recarrega a página) -->
<div class="data-card mb-4">
  <div class="data-card-header">
    <h6 class="data-card-title"><i class="bi bi-journal-bookmark me-2"></i>Selecionar Curso</h6>
  </div>
  <div class="p-3">
    <form method="GET" class="d-flex gap-2 align-items-center flex-wrap">
      <select name="curso_id" class="form-select" style="max-width:420px"
              onchange="this.form.submit()">
        <option value="">— Selecione um curso —</option>
        <?php foreach ($cursos as $c): ?>
        <option value="<?= $c['id'] ?>" <?= $cursoId == $c['id'] ? 'selected' : '' ?>>
          <?= e($c['nome']) ?>
        </option>
        <?php endforeach; ?>
      </select>
      <?php if ($cursoId): ?>
      <span class="badge-status badge-<?= $curso['tipo'] ?? 'ead' ?>"><?= strtoupper($curso['tipo'] ?? 'ead') ?></span>
      <?php endif; ?>
    </form>
  </div>
</div>

<?php if (!$cursoId): ?>
<!-- Estado vazio: nenhum curso selecionado -->
<div class="empty-state">
  <i class="bi bi-award"></i>
  <p>Selecione um curso acima para configurar o certificado.</p>
</div>

<?php else: ?>
<!-- ════════════════════════════════════════════════
     FORMULÁRIO DE CONFIGURAÇÃO
     ════════════════════════════════════════════════ -->
<form method="POST" enctype="multipart/form-data" id="formCertificado">
  <?= csrfField() ?>
  <input type="hidden" name="curso_id" value="<?= $cursoId ?>">

  <div class="row g-4">

    <!-- ── COLUNA ESQUERDA: Frente ─────────────── -->
    <div class="col-lg-6">
      <div class="form-card h-100">
        <div class="d-flex align-items-center gap-2 mb-4 pb-3" style="border-bottom:2px solid var(--primary-light)">
          <div style="width:36px;height:36px;background:var(--primary-light);color:var(--primary);border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:18px">
            <i class="bi bi-file-earmark-text"></i>
          </div>
          <div>
            <h6 class="mb-0" style="color:var(--primary)">Frente do Certificado</h6>
            <small class="text-muted">Imagem de fundo e texto principal</small>
          </div>
        </div>

        <!-- Imagem de fundo da frente -->
        <div class="mb-4">
          <label class="form-label">
            <i class="bi bi-image me-1"></i>Imagem de Fundo (frente)
          </label>
          <?php if (!empty($modelo['frente'])): ?>
          <div class="mb-2 p-2 border rounded" style="background:#f8fafd">
            <div class="d-flex align-items-center gap-3">
              <img src="<?= APP_URL ?>/public/uploads/modelos/<?= e($modelo['frente']) ?>"
                   class="rounded" style="height:64px;object-fit:cover;width:100px">
              <div>
                <div style="font-size:12px;font-weight:600">Imagem atual</div>
                <div style="font-size:11px;color:var(--text-muted)"><?= e($modelo['frente']) ?></div>
              </div>
            </div>
          </div>
          <?php endif; ?>
          <input type="file" name="frente" class="form-control" accept="image/*">
          <small class="text-muted">JPG, PNG, WebP. Recomendado: 2480×1748px (A4 paisagem)</small>
        </div>

        <!-- Texto customizável da frente -->
        <div class="mb-3">
          <label class="form-label">
            <i class="bi bi-pencil-square me-1"></i>Texto da Frente (opcional)
          </label>
          <textarea name="texto_frente" class="form-control" rows="3"
                    placeholder="Ex: O CRMV-TO certifica que..."
                    style="font-size:13px"><?= e($modelo['texto_frente'] ?? '') ?></textarea>
          <small class="text-muted">
            Deixe em branco para usar o texto padrão institucional.<br>
            Variáveis disponíveis: <code>[NOME]</code> <code>[CURSO]</code> <code>[CARGA_HORARIA]</code> <code>[DATA]</code>
          </small>
        </div>

        <!-- Preview da frente -->
        <?php if (!empty($modelo['frente'])): ?>
        <a href="<?= APP_URL ?>/public/uploads/modelos/<?= e($modelo['frente']) ?>" target="_blank"
           class="btn btn-sm btn-outline-primary w-100">
          <i class="bi bi-eye me-1"></i>Visualizar imagem da frente
        </a>
        <?php endif; ?>
      </div>
    </div>

    <!-- ── COLUNA DIREITA: Verso ────────────────── -->
    <div class="col-lg-6">
      <div class="form-card h-100">
        <div class="d-flex align-items-center gap-2 mb-4 pb-3" style="border-bottom:2px solid var(--primary-light)">
          <div style="width:36px;height:36px;background:#fef3c7;color:#b45309;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:18px">
            <i class="bi bi-file-earmark-richtext"></i>
          </div>
          <div>
            <h6 class="mb-0" style="color:#b45309">Verso do Certificado</h6>
            <small class="text-muted">Conteúdo programático, instrutores e informações complementares</small>
          </div>
        </div>

        <!-- Imagem de fundo do verso -->
        <div class="mb-4">
          <label class="form-label">
            <i class="bi bi-image me-1"></i>Imagem de Fundo (verso)
          </label>
          <?php if (!empty($modelo['verso'])): ?>
          <div class="mb-2 p-2 border rounded" style="background:#f8fafd">
            <div class="d-flex align-items-center gap-3">
              <img src="<?= APP_URL ?>/public/uploads/modelos/<?= e($modelo['verso']) ?>"
                   class="rounded" style="height:64px;object-fit:cover;width:100px">
              <div>
                <div style="font-size:12px;font-weight:600">Imagem atual</div>
                <div style="font-size:11px;color:var(--text-muted)"><?= e($modelo['verso']) ?></div>
              </div>
            </div>
          </div>
          <?php endif; ?>
          <input type="file" name="verso" class="form-control" accept="image/*">
          <small class="text-muted">Opcional. Mesmo tamanho recomendado da frente.</small>
        </div>

        <!-- Conteúdo Rico do Verso (CKEditor) -->
        <div class="mb-3">
          <label class="form-label">
            <i class="bi bi-list-check me-1"></i>Conteúdo do Verso
            <span class="badge bg-warning text-dark ms-1" style="font-size:10px">Editor Rico</span>
          </label>
          <div class="alert-crmv mb-2" style="font-size:12px">
            <i class="bi bi-info-circle me-1"></i>
            Use o editor abaixo para formatar o <strong>conteúdo programático</strong>,
            <strong>instrutores</strong> e demais informações do verso do certificado.
          </div>
          <!-- CKEditor será inicializado aqui -->
          <textarea name="verso_conteudo" id="versoEditor"
                    rows="12"><?= $modelo['verso_conteudo'] ?? '' ?></textarea>
        </div>
      </div>
    </div>

  </div><!-- /row -->

  <!-- Botão salvar fixo -->
  <div class="mt-4 d-flex gap-2">
    <button type="submit" class="btn btn-primary px-5">
      <i class="bi bi-check-lg me-2"></i>Salvar Configuração do Certificado
    </button>
    <a href="?curso_id=<?= $cursoId ?>" class="btn btn-outline-secondary">Cancelar</a>
    <?php if (!empty($certs)): ?>
    <a href="#certificados-emitidos" class="btn btn-outline-warning ms-auto">
      <i class="bi bi-award me-1"></i><?= count($certs) ?> certificado(s) emitido(s)
    </a>
    <?php endif; ?>
  </div>
</form>

<!-- ════════════════════════════════════════════════
     CERTIFICADOS EMITIDOS
     ════════════════════════════════════════════════ -->
<div class="data-card mt-4" id="certificados-emitidos">
  <div class="data-card-header">
    <h6 class="data-card-title">
      <i class="bi bi-award me-2"></i>Certificados Emitidos — <?= e($curso['nome'] ?? '') ?>
    </h6>
    <span class="badge bg-primary"><?= count($certs) ?></span>
  </div>
  <?php if ($certs): ?>
  <div class="table-responsive">
    <table class="table table-ead">
      <thead>
        <tr>
          <th>Aluno</th>
          <th>Código de Verificação</th>
          <th>Emitido em</th>
          <th>Ações</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($certs as $cert): ?>
      <tr>
        <td><strong><?= e($cert['aluno_nome']) ?></strong></td>
        <td>
          <code style="font-size:11px"><?= substr($cert['codigo'], 0, 16) ?>...</code>
        </td>
        <td><?= dataBR($cert['emitido_em']) ?></td>
        <td class="d-flex gap-1">
          <a href="<?= APP_URL ?>/validar.php?codigo=<?= urlencode($cert['codigo']) ?>"
             target="_blank" class="btn btn-sm btn-outline-success">
            <i class="bi bi-patch-check me-1"></i>Validar
          </a>
        </td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php else: ?>
  <div class="empty-state"><i class="bi bi-award"></i><p>Nenhum certificado emitido para este curso ainda.</p></div>
  <?php endif; ?>
</div>
<?php endif; ?>

<!-- ════════════════════════════════════════════════
     CKEditor 5 (CDN clássico, sem build)
     ════════════════════════════════════════════════ -->
<?php if ($cursoId): ?>
<script src="https://cdn.ckeditor.com/ckeditor5/41.4.2/classic/ckeditor.js"></script>
<script>
ClassicEditor
  .create(document.querySelector('#versoEditor'), {
    toolbar: {
      items: [
        'heading', '|',
        'bold', 'italic', 'underline', '|',
        'bulletedList', 'numberedList', '|',
        'outdent', 'indent', '|',
        'blockQuote', 'insertTable', '|',
        'undo', 'redo'
      ]
    },
    heading: {
      options: [
        { model: 'paragraph',  title: 'Parágrafo',  class: 'ck-heading_paragraph' },
        { model: 'heading2',   view: 'h2', title: 'Título',    class: 'ck-heading_heading2' },
        { model: 'heading3',   view: 'h3', title: 'Subtítulo', class: 'ck-heading_heading3' },
      ]
    },
    placeholder: 'Digite aqui o conteúdo programático, instrutores e demais informações que aparecerão no verso do certificado...',
    language: 'pt-br',
  })
  .then(editor => {
    // Garante que o conteúdo atualizado é submetido no form
    document.getElementById('formCertificado').addEventListener('submit', () => {
      document.querySelector('#versoEditor').value = editor.getData();
    });
    window._versoEditor = editor;
  })
  .catch(err => {
    console.warn('CKEditor não carregou:', err);
    // Fallback: textarea funciona normalmente sem o editor
  });
</script>
<?php endif; ?>

<?php include __DIR__ . '/../app/views/layouts/admin_footer.php'; ?>
