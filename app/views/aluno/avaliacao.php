<?php $pageTitle = $avaliacao['titulo']; ?>
<div class="mb-4">
  <a href="<?= APP_URL ?>/aluno/cursos/<?= $curso['id'] ?>" class="btn btn-sm btn-outline-secondary mb-2">
    <i class="bi bi-arrow-left me-1"></i>Voltar ao Curso
  </a>
  <h1 style="font-size:20px;font-weight:800;margin:0;color:var(--primary)"><?= e($avaliacao['titulo']) ?></h1>
</div>

<?php if($ultima): ?>
<div class="alert alert-<?= $ultima['aprovado']?'success':'warning' ?> mb-4">
  <strong>Última tentativa:</strong> Nota <?= number_format($ultima['nota'],1) ?>%
  — <?= $ultima['aprovado']?'<strong>Aprovado ✓</strong>':'Reprovado ✗' ?>
  &nbsp;(<?= $tentativas ?>/<?= $avaliacao['tentativas'] ?> tentativas)
</div>
<?php endif; ?>

<?php if($tentativas >= $avaliacao['tentativas'] && $ultima): ?>
<div class="data-card p-4 text-center">
  <i class="bi bi-x-circle" style="font-size:48px;color:<?= $ultima['aprovado']?'var(--success)':'var(--danger)' ?>"></i>
  <p class="mt-3"><?= $ultima['aprovado']?'Você foi aprovado nesta avaliação!':'Número máximo de tentativas atingido.' ?></p>
  <?php if($ultima['aprovado'] && $matricula['status']==='concluida'): ?>
  <a href="<?= APP_URL ?>/aluno/cursos/<?= $curso['id'] ?>/certificado" class="btn btn-warning">
    <i class="bi bi-award me-1"></i>Emitir Certificado
  </a>
  <?php endif; ?>
</div>
<?php else: ?>
<div class="data-card">
  <div class="data-card-header">
    <h6 class="data-card-title"><?= count($perguntasCompletas) ?> perguntas</h6>
    <span class="badge bg-info text-dark"><?= $avaliacao['tentativas'] - $tentativas ?> tentativa(s) restante(s)</span>
  </div>
  <div class="p-4">
    <form method="POST">
      <?= csrfField() ?>
      <?php foreach($perguntasCompletas as $i=>$p): ?>
      <div class="mb-4">
        <p class="fw-semibold"><?= ($i+1) ?>. <?= e($p['enunciado']) ?>
          <small class="text-muted">(<?= $p['pontos'] ?> ponto<?= $p['pontos']!=1?'s':'' ?>)</small>
        </p>
        <?php foreach($p['alternativas'] as $alt): ?>
        <div class="form-check">
          <input class="form-check-input" type="radio" name="q_<?= $p['id'] ?>" value="<?= $alt['id'] ?>" required>
          <label class="form-check-label"><?= e($alt['texto']) ?></label>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endforeach; ?>
      <button type="submit" class="btn btn-primary btn-lg w-100">
        <i class="bi bi-send me-2"></i>Enviar Respostas
      </button>
    </form>
  </div>
</div>
<?php endif; ?>
