<?php
require_once __DIR__ . '/../app/bootstrap.php';
authCheck('admin');

$certModel  = new CertificadoModel();
$cursoModel = new CursoModel();
$cursoId    = (int)($_GET['curso_id'] ?? 0);

/* UPLOAD MODELO */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfCheck();
    $cid = (int)($_POST['curso_id'] ?? 0);
    $d   = [];
    if (!empty($_FILES['frente']['name'])) {
        $f = uploadFile($_FILES['frente'], MODEL_PATH, ALLOWED_IMAGE);
        if ($f) $d['frente'] = $f;
    }
    if (!empty($_FILES['verso']['name'])) {
        $v = uploadFile($_FILES['verso'], MODEL_PATH, ALLOWED_IMAGE);
        if ($v) $d['verso'] = $v;
    }
    if ($d) { $certModel->salvarModelo($cid, $d); setFlash('success','Modelo salvo!'); }
    redirect(APP_URL . '/admin/certificados.php?curso_id=' . $cid);
}

$cursos = $cursoModel->cursosAtivos();
$certs  = $cursoId ? $certModel->doCurso($cursoId) : [];
$modelo = $cursoId ? $certModel->modelo($cursoId) : null;

$pageTitle = 'Certificados';
include __DIR__ . '/../app/views/layouts/admin_header.php';
?>

<div class="page-header">
  <h1>Certificados</h1>
  <p class="page-subtitle">Gerencie modelos e certificados emitidos</p>
</div>

<div class="row g-3">
  <!-- UPLOAD MODELO -->
  <div class="col-md-4">
    <div class="form-card">
      <h6 class="mb-3"><i class="bi bi-image me-2 text-primary"></i>Modelo de Certificado</h6>
      <form method="POST" enctype="multipart/form-data">
        <?= csrfField() ?>
        <div class="mb-3">
          <label class="form-label">Curso</label>
          <select name="curso_id" class="form-select" required onchange="this.form.submit()">
            <option value="">— Selecione —</option>
            <?php foreach ($cursos as $c): ?>
            <option value="<?= $c['id'] ?>" <?= $cursoId == $c['id'] ? 'selected' : '' ?>><?= e($c['nome']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <?php if ($cursoId): ?>
        <div class="mb-3">
          <label class="form-label">Imagem Frente</label>
          <?php if (!empty($modelo['frente'])): ?>
          <div class="mb-2"><img src="<?= APP_URL ?>/public/uploads/modelos/<?= e($modelo['frente']) ?>" class="img-fluid rounded" style="max-height:100px"></div>
          <?php endif; ?>
          <input type="file" name="frente" class="form-control" accept="image/*">
        </div>
        <div class="mb-3">
          <label class="form-label">Imagem Verso</label>
          <?php if (!empty($modelo['verso'])): ?>
          <div class="mb-2"><img src="<?= APP_URL ?>/public/uploads/modelos/<?= e($modelo['verso']) ?>" class="img-fluid rounded" style="max-height:100px"></div>
          <?php endif; ?>
          <input type="file" name="verso" class="form-control" accept="image/*">
        </div>
        <button type="submit" class="btn btn-primary w-100"><i class="bi bi-upload me-1"></i>Salvar Modelo</button>
        <?php endif; ?>
      </form>
    </div>
  </div>

  <!-- CERTIFICADOS EMITIDOS -->
  <div class="col-md-8">
    <div class="data-card">
      <div class="data-card-header">
        <h6 class="data-card-title">Certificados Emitidos</h6>
        <?php if ($cursoId): ?><span class="badge bg-primary"><?= count($certs) ?></span><?php endif; ?>
      </div>
      <?php if (!$cursoId): ?>
      <div class="empty-state"><i class="bi bi-award"></i>Selecione um curso para ver os certificados.</div>
      <?php elseif ($certs): ?>
      <div class="table-responsive">
        <table class="table table-ead">
          <thead><tr><th>Aluno</th><th>Código</th><th>Emitido em</th><th>Validar</th></tr></thead>
          <tbody>
          <?php foreach ($certs as $cert): ?>
          <tr>
            <td><?= e($cert['aluno_nome']) ?></td>
            <td><code><?= substr($cert['codigo'],0,12) ?>...</code></td>
            <td><?= dataBR($cert['emitido_em']) ?></td>
            <td>
              <a href="<?= APP_URL ?>/validar.php?codigo=<?= urlencode($cert['codigo']) ?>" target="_blank"
                 class="btn btn-sm btn-outline-success"><i class="bi bi-qr-code me-1"></i>Ver</a>
            </td>
          </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php else: ?>
      <div class="empty-state"><i class="bi bi-award"></i>Nenhum certificado emitido para este curso.</div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../app/views/layouts/admin_footer.php'; ?>
