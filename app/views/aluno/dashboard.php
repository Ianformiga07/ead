<?php $pageTitle = 'Meus Cursos'; $me = currentUser(); ?>
<div class="mb-4">
  <h1 style="font-size:22px;font-weight:800;margin:0;color:var(--primary)">
    Olá, <?= e(explode(' ',$me['nome'])[0]) ?>!
  </h1>
  <p class="page-subtitle">Confira seus cursos e progresso</p>
</div>

<?php if($cursos): ?>
<div class="row g-3">
  <?php foreach($cursos as $c): ?>
  <div class="col-md-6 col-xl-4">
    <div class="data-card h-100 d-flex flex-column">
      <?php if(!empty($c['imagem'])): ?>
      <img src="<?= uploadUrl('cursos/'.$c['imagem']) ?>" alt="<?= e($c['nome']) ?>"
           style="width:100%;height:160px;object-fit:cover;border-radius:12px 12px 0 0">
      <?php else: ?>
      <div style="height:120px;background:linear-gradient(135deg,var(--primary),var(--primary-mid));border-radius:12px 12px 0 0;display:flex;align-items:center;justify-content:center">
        <i class="bi bi-journal-bookmark-fill" style="font-size:40px;color:rgba(255,255,255,.4)"></i>
      </div>
      <?php endif; ?>
      <div class="p-3 d-flex flex-column flex-grow-1">
        <div class="d-flex justify-content-between align-items-start mb-2">
          <h6 style="font-weight:700;font-size:14px;margin:0"><?= e($c['nome']) ?></h6>
          <?= badgeStatus($c['status_matricula']) ?>
        </div>
        <div style="font-size:12px;color:var(--text-muted)" class="mb-3">
          <i class="bi bi-clock me-1"></i><?= (int)$c['carga_horaria'] ?>h
          &nbsp;·&nbsp;<i class="bi bi-bookmark me-1"></i><?= ucfirst(e($c['tipo'])) ?>
        </div>
        <div class="mt-auto">
          <div class="d-flex justify-content-between mb-1" style="font-size:12px">
            <span>Progresso</span><span><?= $progresso[$c['id']] ?? 0 ?>%</span>
          </div>
          <div class="progress mb-3" style="height:6px">
            <div class="progress-bar" style="width:<?= $progresso[$c['id']] ?? 0 ?>%"></div>
          </div>
          <div class="d-flex gap-2">
            <a href="<?= APP_URL ?>/aluno/cursos/<?= $c['id'] ?>" class="btn btn-primary btn-sm flex-grow-1">
              <i class="bi bi-play-circle me-1"></i>Acessar
            </a>
            <?php if($c['status_matricula']==='concluida'): ?>
            <a href="<?= APP_URL ?>/aluno/cursos/<?= $c['id'] ?>/certificado" class="btn btn-outline-warning btn-sm">
              <i class="bi bi-award"></i>
            </a>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>
<?php else: ?>
<div class="data-card">
  <div class="empty-state py-5">
    <i class="bi bi-journal-x" style="font-size:48px;color:var(--text-muted);display:block;text-align:center;margin-bottom:12px"></i>
    <p class="text-center text-muted">Você não está matriculado em nenhum curso ainda.</p>
  </div>
</div>
<?php endif; ?>
