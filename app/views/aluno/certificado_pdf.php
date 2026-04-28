<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Certificado — <?= e($curso['nome']) ?></title>
<style>
body{font-family:'Times New Roman',serif;margin:0;padding:40px;background:#fff;color:#1a1a1a}
.cert{border:3px double #003d7c;padding:40px;text-align:center;min-height:500px;display:flex;flex-direction:column;justify-content:center;align-items:center}
.cert-title{font-size:36px;font-weight:bold;color:#003d7c;letter-spacing:3px;margin-bottom:10px}
.cert-sub{font-size:14px;color:#666;margin-bottom:40px;letter-spacing:2px;text-transform:uppercase}
.cert-nome{font-size:28px;font-weight:bold;color:#1a1a1a;border-bottom:2px solid #003d7c;padding-bottom:4px;margin-bottom:20px}
.cert-texto{font-size:16px;max-width:600px;line-height:1.8;color:#333}
.cert-curso{font-size:20px;font-weight:bold;color:#003d7c;margin:10px 0}
.cert-ch{font-size:14px;color:#666;margin-bottom:40px}
.cert-codigo{font-size:11px;color:#999;margin-top:40px}
</style>
</head>
<body>
<?php if(!empty($modelo['frente'])): ?>
<img src="<?= UPLOAD_PATH ?>/modelos/<?= e($modelo['frente']) ?>" style="width:100%;max-height:400px;object-fit:cover;margin-bottom:20px">
<?php endif; ?>
<div class="cert">
  <div class="cert-title">CERTIFICADO</div>
  <div class="cert-sub">de Conclusão</div>
  <p style="font-size:16px;color:#333">Certificamos que</p>
  <div class="cert-nome"><?= e($usuario['nome']) ?></div>
  <?php if(!empty($usuario['crmv'])): ?><p style="font-size:14px;color:#666">CRMV: <?= e($usuario['crmv']) ?></p><?php endif; ?>
  <p style="font-size:16px;color:#333">concluiu com êxito o curso</p>
  <div class="cert-curso"><?= e($curso['nome']) ?></div>
  <div class="cert-ch"><?= (int)$curso['carga_horaria'] ?> horas · <?= ucfirst(e($curso['tipo'])) ?></div>
  <?php if(!empty($modelo['texto_frente'])): ?>
  <div class="cert-texto"><?= $modelo['texto_frente'] ?></div>
  <?php endif; ?>
  <?php if(!empty($modelo['instrutor'])): ?>
  <div style="margin-top:30px;border-top:1px solid #003d7c;padding-top:10px;font-size:14px">
    <?= e($modelo['instrutor']) ?>
  </div>
  <?php endif; ?>
  <div class="cert-codigo">
    Código: <?= e($cert['codigo']) ?><br>
    Emitido em: <?= dataBR($cert['emitido_em']) ?><br>
    Valide em: <?= APP_URL ?>/validar/<?= urlencode($cert['codigo']) ?>
  </div>
</div>
<?php if(!empty($modelo['ativar_verso']) && !empty($modelo['verso'])): ?>
<div style="page-break-before:always;margin-top:40px">
  <img src="<?= UPLOAD_PATH ?>/modelos/<?= e($modelo['verso']) ?>" style="width:100%">
  <?php if(!empty($modelo['conteudo_prog'])): ?>
  <div style="margin-top:20px;font-size:13px"><?= $modelo['conteudo_prog'] ?></div>
  <?php endif; ?>
</div>
<?php endif; ?>
</body>
</html>
