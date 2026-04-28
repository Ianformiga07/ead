<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Validar Certificado — <?= APP_NAME ?></title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.3/font/bootstrap-icons.min.css">
<style>body{background:#f0f4f9;font-family:'Segoe UI',sans-serif}</style>
</head>
<body>
<div class="container py-5" style="max-width:600px">
  <div class="text-center mb-4">
    <div style="width:64px;height:64px;background:#003d7c;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 16px">
      <i class="bi bi-award-fill text-white fs-3"></i>
    </div>
    <h4 style="color:#003d7c;font-weight:800">Validação de Certificado</h4>
    <p class="text-muted"><?= APP_NAME ?></p>
  </div>

  <?php if($cert): ?>
  <div class="card border-0 shadow-sm">
    <div class="card-body p-4">
      <div class="text-center mb-4">
        <i class="bi bi-patch-check-fill text-success" style="font-size:48px"></i>
        <h5 class="mt-2 text-success fw-bold">Certificado Válido</h5>
      </div>
      <table class="table table-borderless mb-0">
        <tr><th class="text-muted fw-normal" style="width:40%">Aluno</th><td><strong><?= e($cert['aluno_nome']) ?></strong></td></tr>
        <tr><th class="text-muted fw-normal">CRMV</th><td><?= e($cert['aluno_crmv'] ?? '—') ?></td></tr>
        <tr><th class="text-muted fw-normal">Curso</th><td><?= e($cert['curso_nome']) ?></td></tr>
        <tr><th class="text-muted fw-normal">Carga Horária</th><td><?= (int)$cert['carga_horaria'] ?>h</td></tr>
        <tr><th class="text-muted fw-normal">Emitido em</th><td><?= dataBR($cert['emitido_em']) ?></td></tr>
        <tr><th class="text-muted fw-normal">Código</th><td><code style="font-size:11px"><?= e($cert['codigo']) ?></code></td></tr>
      </table>
    </div>
  </div>
  <?php elseif($code): ?>
  <div class="card border-0 shadow-sm">
    <div class="card-body p-4 text-center">
      <i class="bi bi-x-circle-fill text-danger" style="font-size:48px"></i>
      <h5 class="mt-2 text-danger fw-bold">Certificado não encontrado</h5>
      <p class="text-muted">O código <code><?= e($code) ?></code> não corresponde a nenhum certificado válido.</p>
    </div>
  </div>
  <?php else: ?>
  <div class="card border-0 shadow-sm">
    <div class="card-body p-4">
      <p class="text-muted text-center mb-3">Informe o código do certificado para validar.</p>
      <form method="GET" action="<?= APP_URL ?>/validar/">
        <div class="input-group">
          <input type="text" name="code" class="form-control" placeholder="Código do certificado">
          <button type="submit" class="btn btn-primary">Validar</button>
        </div>
      </form>
    </div>
  </div>
  <?php endif; ?>

  <div class="text-center mt-4">
    <a href="<?= APP_URL ?>" class="text-muted small"><i class="bi bi-arrow-left me-1"></i>Voltar</a>
  </div>
</div>
</body>
</html>
