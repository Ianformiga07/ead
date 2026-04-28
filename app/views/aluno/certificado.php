<?php $pageTitle = 'Certificado'; ?>
<div class="mb-4">
  <a href="<?= APP_URL ?>/aluno/cursos/<?= $curso['id'] ?>" class="btn btn-sm btn-outline-secondary mb-2">
    <i class="bi bi-arrow-left me-1"></i>Voltar ao Curso
  </a>
  <h1 style="font-size:20px;font-weight:800;margin:0;color:var(--primary)">Certificado de Conclusão</h1>
</div>

<div class="data-card">
  <div class="p-4 text-center">
    <i class="bi bi-award-fill" style="font-size:64px;color:var(--accent)"></i>
    <h3 class="mt-3 mb-1"><?= e($usuario['nome']) ?></h3>
    <p class="text-muted mb-1">concluiu com êxito o curso</p>
    <h4 class="text-primary"><?= e($curso['nome']) ?></h4>
    <p class="text-muted"><?= (int)$curso['carga_horaria'] ?> horas · <?= ucfirst(e($curso['tipo'])) ?></p>
    <hr>
    <p class="small text-muted mb-1">Código de validação:</p>
    <code class="fs-6"><?= e($cert['codigo']) ?></code>
    <br><br>
    <p class="small text-muted">Valide em: <a href="<?= APP_URL ?>/validar/<?= urlencode($cert['codigo']) ?>" target="_blank"><?= APP_URL ?>/validar/<?= urlencode($cert['codigo']) ?></a></p>
    <div class="d-flex gap-2 justify-content-center mt-4">
      <a href="<?= APP_URL ?>/aluno/certificado/<?= $cert['id'] ?>/pdf" target="_blank" class="btn btn-primary">
        <i class="bi bi-file-earmark-pdf me-1"></i>Baixar PDF
      </a>
      <a href="<?= APP_URL ?>/validar/<?= urlencode($cert['codigo']) ?>" target="_blank" class="btn btn-outline-success">
        <i class="bi bi-patch-check me-1"></i>Validar Online
      </a>
    </div>
  </div>
</div>
