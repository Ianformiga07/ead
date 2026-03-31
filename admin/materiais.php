<?php
require_once __DIR__ . '/../app/bootstrap.php';
authCheck('admin');

$cursoId     = (int)($_GET['curso_id'] ?? 0);
if (!$cursoId) { setFlash('error','Curso não informado.'); redirect(APP_URL.'/admin/cursos.php'); }

$cursoModel  = new CursoModel();
$matModel    = new MaterialModel();
$curso = $cursoModel->findById($cursoId);
if (!$curso) { setFlash('error','Curso não encontrado.'); redirect(APP_URL.'/admin/cursos.php'); }

/* UPLOAD */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfCheck();
    if (!empty($_FILES['arquivo']['name'])) {
        $nome = uploadFile($_FILES['arquivo'], MAT_PATH, ALLOWED_MATERIAL);
        if ($nome) {
            $matModel->criar([
                'curso_id' => $cursoId,
                'titulo'   => sanitize($_POST['titulo'] ?? $_FILES['arquivo']['name']),
                'arquivo'  => $nome,
                'tipo'     => strtolower(pathinfo($_FILES['arquivo']['name'], PATHINFO_EXTENSION)),
                'tamanho'  => $_FILES['arquivo']['size'],
            ]);
            setFlash('success','Material enviado com sucesso!');
        } else {
            setFlash('error','Erro no upload. Verifique o tipo e tamanho do arquivo.');
        }
    }
    redirect(APP_URL . "/admin/materiais.php?curso_id=$cursoId");
}

/* DELETAR */
if (($_GET['acao'] ?? '') === 'deletar' && ($mid = (int)($_GET['id'] ?? 0))) {
    $mat = $matModel->findById($mid);
    if ($mat) {
        @unlink(MAT_PATH . '/' . $mat['arquivo']);
        $matModel->deletar($mid);
        setFlash('success','Material removido.');
    }
    redirect(APP_URL . "/admin/materiais.php?curso_id=$cursoId");
}

$materiais = $matModel->porCurso($cursoId);
$pageTitle  = 'Materiais — ' . $curso['nome'];
include __DIR__ . '/../app/views/layouts/admin_header.php';
?>

<div class="page-header">
  <div>
    <h1>Materiais Didáticos</h1>
    <p class="page-subtitle"><?= e($curso['nome']) ?></p>
  </div>
  <a href="<?= APP_URL ?>/admin/cursos.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Voltar</a>
</div>

<div class="row g-3">
  <!-- UPLOAD FORM -->
  <div class="col-md-4">
    <div class="form-card">
      <h6 class="mb-3"><i class="bi bi-cloud-upload me-2 text-primary"></i>Enviar Material</h6>
      <form method="POST" enctype="multipart/form-data">
        <?= csrfField() ?>
        <div class="mb-3">
          <label class="form-label">Título</label>
          <input type="text" name="titulo" class="form-control" placeholder="Nome do arquivo...">
        </div>
        <div class="mb-3">
          <label class="form-label">Arquivo *</label>
          <input type="file" name="arquivo" class="form-control" required accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.zip,.jpg,.png">
          <small class="text-muted">PDF, DOC, PPT, XLS, ZIP, imagens. Máx <?= MAX_UPLOAD_MB ?>MB.</small>
        </div>
        <button type="submit" class="btn btn-primary w-100"><i class="bi bi-upload me-1"></i>Enviar</button>
      </form>
    </div>
  </div>

  <!-- LISTA -->
  <div class="col-md-8">
    <div class="data-card">
      <div class="data-card-header">
        <h6 class="data-card-title">Materiais do Curso</h6>
        <span class="badge bg-primary"><?= count($materiais) ?> arquivo(s)</span>
      </div>
      <div class="table-responsive">
        <table class="table table-ead">
          <thead><tr><th>Título</th><th>Tipo</th><th>Tamanho</th><th>Ações</th></tr></thead>
          <tbody>
          <?php if ($materiais): foreach ($materiais as $m): ?>
          <tr>
            <td><?= e($m['titulo']) ?></td>
            <td><span class="badge bg-secondary text-uppercase"><?= e($m['tipo']) ?></span></td>
            <td><?= $m['tamanho'] ? round($m['tamanho']/1024) . ' KB' : '—' ?></td>
            <td>
              <a href="<?= APP_URL ?>/public/uploads/materiais/<?= e($m['arquivo']) ?>" target="_blank" class="btn btn-icon btn-outline-success btn-sm"><i class="bi bi-download"></i></a>
              <a href="?curso_id=<?= $cursoId ?>&acao=deletar&id=<?= $m['id'] ?>" class="btn btn-icon btn-outline-danger btn-sm"
                 data-confirm="Remover o material '<?= e($m['titulo']) ?>'?"><i class="bi bi-trash"></i></a>
            </td>
          </tr>
          <?php endforeach; else: ?>
          <tr><td colspan="4"><div class="empty-state"><i class="bi bi-folder-x"></i>Nenhum material enviado.</div></td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../app/views/layouts/admin_footer.php'; ?>
