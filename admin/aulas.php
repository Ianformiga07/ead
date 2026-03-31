<?php
require_once __DIR__ . '/../app/bootstrap.php';
authCheck('admin');

$cursoId = (int)($_GET['curso_id'] ?? 0);
if (!$cursoId) { setFlash('error','Curso não informado.'); redirect(APP_URL.'/admin/cursos.php'); }

$cursoModel = new CursoModel();
$aulaModel  = new AulaModel();
$curso = $cursoModel->findById($cursoId);
if (!$curso) { setFlash('error','Curso não encontrado.'); redirect(APP_URL.'/admin/cursos.php'); }

$acao = $_GET['acao'] ?? 'listar';
$id   = (int)($_GET['id'] ?? 0);
$aula = $id ? $aulaModel->findById($id) : null;

/* SALVAR */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfCheck();
    $d = [
        'curso_id'  => $cursoId,
        'titulo'    => sanitize($_POST['titulo'] ?? ''),
        'descricao' => sanitize($_POST['descricao'] ?? ''),
        'url_video' => sanitize($_POST['url_video'] ?? ''),
        'ordem'     => (int)($_POST['ordem'] ?? 1),
        'status'    => (int)($_POST['status'] ?? 1),
    ];
    if ($id) { $aulaModel->atualizar($id, $d); setFlash('success','Aula atualizada!'); }
    else     { $aulaModel->criar($d);          setFlash('success','Aula criada!'); }
    redirect(APP_URL . "/admin/aulas.php?curso_id=$cursoId");
}

/* DELETAR */
if ($acao === 'deletar' && $id) {
    $aulaModel->deletar($id);
    setFlash('success','Aula removida.');
    redirect(APP_URL . "/admin/aulas.php?curso_id=$cursoId");
}

$aulas     = $aulaModel->porCurso($cursoId);
$pageTitle = 'Aulas — ' . $curso['nome'];
include __DIR__ . '/../app/views/layouts/admin_header.php';
?>

<div class="page-header">
  <div>
    <h1>Aulas do Curso</h1>
    <p class="page-subtitle"><?= e($curso['nome']) ?> &mdash; <?= count($aulas) ?> aula(s)</p>
  </div>
  <div class="d-flex gap-2">
    <a href="<?= APP_URL ?>/admin/cursos.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Voltar</a>
    <?php if ($acao === 'listar'): ?>
    <a href="?curso_id=<?= $cursoId ?>&acao=novo" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Nova Aula</a>
    <?php endif; ?>
  </div>
</div>

<?php if ($acao === 'novo' || $acao === 'editar'): ?>
<div class="form-card">
  <h5 class="mb-4"><?= $id ? 'Editar Aula' : 'Nova Aula' ?></h5>
  <form method="POST">
    <?= csrfField() ?>
    <div class="row g-3">
      <div class="col-md-9">
        <label class="form-label">Título *</label>
        <input type="text" name="titulo" class="form-control" required value="<?= e($aula['titulo'] ?? '') ?>">
      </div>
      <div class="col-md-2">
        <label class="form-label">Ordem</label>
        <input type="number" name="ordem" class="form-control" min="1" value="<?= e($aula['ordem'] ?? count($aulas)+1) ?>">
      </div>
      <div class="col-md-1">
        <label class="form-label">Status</label>
        <select name="status" class="form-select">
          <option value="1" <?= ($aula['status'] ?? 1) == 1 ? 'selected':'' ?>>Ativo</option>
          <option value="0" <?= ($aula['status'] ?? 1) == 0 ? 'selected':'' ?>>Inativo</option>
        </select>
      </div>
      <div class="col-12">
        <label class="form-label">Link do Vídeo (YouTube / Vimeo / URL direta)</label>
        <input type="url" name="url_video" class="form-control" placeholder="https://youtube.com/watch?v=..." value="<?= e($aula['url_video'] ?? '') ?>">
        <small class="text-muted">Cole o link completo do YouTube, Vimeo ou outro player.</small>
      </div>
      <div class="col-12">
        <label class="form-label">Descrição</label>
        <textarea name="descricao" class="form-control" rows="3"><?= e($aula['descricao'] ?? '') ?></textarea>
      </div>
    </div>
    <hr class="my-3">
    <div class="d-flex gap-2">
      <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Salvar</button>
      <a href="?curso_id=<?= $cursoId ?>" class="btn btn-outline-secondary">Cancelar</a>
    </div>
  </form>
</div>

<?php else: ?>
<div class="data-card">
  <div class="table-responsive">
    <table class="table table-ead">
      <thead><tr><th>#</th><th>Título</th><th>Vídeo</th><th>Status</th><th>Ações</th></tr></thead>
      <tbody>
      <?php if ($aulas): foreach ($aulas as $a): ?>
      <tr>
        <td><?= $a['ordem'] ?></td>
        <td><?= e($a['titulo']) ?></td>
        <td><?= $a['url_video'] ? '<span class="badge bg-success"><i class="bi bi-play-circle me-1"></i>Sim</span>' : '<span class="badge bg-secondary">Não</span>' ?></td>
        <td><span class="badge-status badge-<?= $a['status'] ? 'ativo':'inativo' ?>"><?= $a['status'] ? 'Ativo':'Inativo' ?></span></td>
        <td>
          <a href="?curso_id=<?= $cursoId ?>&acao=editar&id=<?= $a['id'] ?>" class="btn btn-icon btn-outline-primary btn-sm"><i class="bi bi-pencil"></i></a>
          <a href="?curso_id=<?= $cursoId ?>&acao=deletar&id=<?= $a['id'] ?>" class="btn btn-icon btn-outline-danger btn-sm"
             data-confirm="Excluir a aula '<?= e($a['titulo']) ?>'?"><i class="bi bi-trash"></i></a>
        </td>
      </tr>
      <?php endforeach; else: ?>
      <tr><td colspan="5"><div class="empty-state"><i class="bi bi-camera-video-off"></i>Nenhuma aula cadastrada.</div></td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<?php include __DIR__ . '/../app/views/layouts/admin_footer.php'; ?>
