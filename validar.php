<?php
// validar.php — Página pública de validação de certificado
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/app/helpers/functions.php';
require_once __DIR__ . '/app/models/Model.php';
require_once __DIR__ . '/app/models/CertificadoModel.php';

$codigo = sanitize($_GET['codigo'] ?? '');
$cert   = null;
$valido = false;

if ($codigo) {
    $certModel = new CertificadoModel();
    $cert      = $certModel->buscarPorCodigo($codigo);
    $valido    = !empty($cert);
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Validação de Certificado — <?= APP_NAME ?></title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.3/font/bootstrap-icons.min.css">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
  :root { --primary: #4f46e5; }
  body {
    font-family: 'Plus Jakarta Sans', sans-serif;
    background: linear-gradient(135deg, #1e1b4b 0%, #312e81 60%, #4338ca 100%);
    min-height: 100vh;
    display: flex; flex-direction: column;
    align-items: center; justify-content: center;
    padding: 20px;
  }
  .val-card {
    width: 100%; max-width: 560px;
    background: #fff; border-radius: 20px;
    padding: 40px; box-shadow: 0 30px 70px rgba(0,0,0,.3);
  }
  .val-logo {
    text-align: center; margin-bottom: 28px;
  }
  .val-logo .icon {
    width: 60px; height: 60px;
    background: var(--primary); border-radius: 16px;
    display: inline-flex; align-items: center; justify-content: center;
    font-size: 28px; color: #fff; margin-bottom: 12px;
  }
  .val-logo h1 { font-size: 18px; font-weight: 700; color: #1e1b4b; margin: 0; }
  .val-logo p  { font-size: 13px; color: #64748b; margin: 4px 0 0; }

  .result-valid {
    background: linear-gradient(135deg, #ecfdf5, #d1fae5);
    border: 2px solid #10b981; border-radius: 16px; padding: 28px;
  }
  .result-invalid {
    background: linear-gradient(135deg, #fef2f2, #fee2e2);
    border: 2px solid #ef4444; border-radius: 16px; padding: 28px;
  }
  .result-icon { font-size: 52px; display: block; margin-bottom: 12px; }
  .result-valid   .result-icon { color: #10b981; }
  .result-invalid .result-icon { color: #ef4444; }
  .result-title { font-size: 20px; font-weight: 700; margin-bottom: 4px; }
  .result-valid   .result-title { color: #065f46; }
  .result-invalid .result-title { color: #991b1b; }

  .info-row { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid rgba(0,0,0,.06); }
  .info-row:last-child { border: none; }
  .info-label { font-size: 12px; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: .5px; }
  .info-value { font-size: 14px; font-weight: 600; color: #1e1b4b; text-align: right; }

  .search-form input {
    border: 1.5px solid #e2e8f0; border-radius: 10px 0 0 10px;
    padding: 10px 14px; font-size: 14px; flex: 1;
  }
  .search-form input:focus { outline: none; border-color: var(--primary); }
  .search-form button {
    background: var(--primary); color: #fff;
    border: none; border-radius: 0 10px 10px 0;
    padding: 10px 20px; font-weight: 600; cursor: pointer;
  }
  .search-form button:hover { background: #3730a3; }

  .cert-code { font-family: monospace; font-size: 11px; word-break: break-all; color: #9ca3af; }
  footer { color: rgba(255,255,255,.5); font-size: 12px; margin-top: 24px; text-align: center; }
</style>
</head>
<body>

<div class="val-card">
  <div class="val-logo">
    <div class="icon"><i class="bi bi-patch-check-fill"></i></div>
    <h1>Validação de Certificado</h1>
    <p><?= APP_NAME ?> — Sistema oficial de verificação</p>
  </div>

  <!-- RESULTADO -->
  <?php if ($codigo): ?>
    <?php if ($valido && $cert): ?>
    <div class="result-valid mb-4">
      <div class="text-center mb-3">
        <i class="bi bi-patch-check-fill result-icon"></i>
        <div class="result-title">Certificado Válido</div>
        <small style="color:#065f46">Este certificado é autêntico e foi emitido pela plataforma.</small>
      </div>
      <div class="mt-3">
        <div class="info-row">
          <span class="info-label">Aluno</span>
          <span class="info-value"><?= e($cert['aluno_nome']) ?></span>
        </div>
        <div class="info-row">
          <span class="info-label">Curso</span>
          <span class="info-value"><?= e($cert['curso_nome']) ?></span>
        </div>
        <div class="info-row">
          <span class="info-label">Carga Horária</span>
          <span class="info-value"><?= $cert['carga_horaria'] ?>h</span>
        </div>
        <?php if (!empty($cert['instrutores'])): ?>
        <div class="info-row">
          <span class="info-label">Instrutor(es)</span>
          <span class="info-value"><?= e($cert['instrutores']) ?></span>
        </div>
        <?php endif; ?>
        <div class="info-row">
          <span class="info-label">Data de Emissão</span>
          <span class="info-value"><?= dataBR($cert['emitido_em']) ?></span>
        </div>
        <div class="info-row">
          <span class="info-label">Status</span>
          <span class="info-value" style="color:#10b981">✓ Válido</span>
        </div>
      </div>
      <div class="mt-3 text-center">
        <small class="cert-code">Código: <?= e($cert['codigo']) ?></small>
      </div>
    </div>

    <?php else: ?>
    <div class="result-invalid mb-4">
      <div class="text-center">
        <i class="bi bi-x-circle-fill result-icon"></i>
        <div class="result-title">Certificado Inválido</div>
        <small style="color:#991b1b">O código informado não foi encontrado na base de dados.</small>
      </div>
    </div>
    <?php endif; ?>
  <?php endif; ?>

  <!-- FORMULÁRIO DE BUSCA -->
  <div>
    <p class="text-muted mb-2" style="font-size:13px">
      <i class="bi bi-search me-1"></i>Digite o código do certificado para verificar:
    </p>
    <form method="GET" action="" class="search-form d-flex">
      <input type="text" name="codigo" placeholder="Cole o código do certificado aqui..."
             value="<?= e($codigo) ?>" required style="border-right:none">
      <button type="submit"><i class="bi bi-search me-1"></i>Verificar</button>
    </form>
    <small class="text-muted mt-2 d-block">
      <i class="bi bi-info-circle me-1"></i>
      O código está impresso no certificado ou pode ser obtido via QR Code.
    </small>
  </div>

  <!-- COMO FUNCIONA -->
  <div class="mt-4 pt-3 border-top">
    <p class="text-muted mb-2" style="font-size:12px;font-weight:600;letter-spacing:.5px;text-transform:uppercase">Como verificar</p>
    <div class="d-flex flex-column gap-2">
      <div class="d-flex gap-2 align-items-start">
        <span style="background:#e0e7ff;color:#4f46e5;border-radius:6px;padding:2px 8px;font-size:11px;font-weight:700;flex-shrink:0">1</span>
        <small class="text-muted">Localize o código único impresso no certificado (formato alfanumérico longo)</small>
      </div>
      <div class="d-flex gap-2 align-items-start">
        <span style="background:#e0e7ff;color:#4f46e5;border-radius:6px;padding:2px 8px;font-size:11px;font-weight:700;flex-shrink:0">2</span>
        <small class="text-muted">Cole o código no campo acima e clique em "Verificar"</small>
      </div>
      <div class="d-flex gap-2 align-items-start">
        <span style="background:#e0e7ff;color:#4f46e5;border-radius:6px;padding:2px 8px;font-size:11px;font-weight:700;flex-shrink:0">3</span>
        <small class="text-muted">Ou escaneie o QR Code impresso no verso do certificado com a câmera do celular</small>
      </div>
    </div>
  </div>
</div>

<footer>&copy; <?= date('Y') ?> <?= APP_NAME ?> — Todos os direitos reservados</footer>
</body>
</html>
