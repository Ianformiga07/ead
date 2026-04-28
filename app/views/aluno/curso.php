<?php $pageTitle = $curso['nome']; ?>
<div class="mb-3">
  <a href="<?= APP_URL ?>/aluno/dashboard" class="btn btn-sm btn-outline-secondary mb-2">
    <i class="bi bi-arrow-left me-1"></i>Meus Cursos
  </a>
  <h1 style="font-size:20px;font-weight:800;margin:0;color:var(--primary)"><?= e($curso['nome']) ?></h1>
  <div style="font-size:13px;color:var(--text-muted)">
    <i class="bi bi-clock me-1"></i><?= (int)$curso['carga_horaria'] ?>h
    &nbsp;·&nbsp;<?= ucfirst(e($curso['tipo'])) ?>
    &nbsp;·&nbsp;Progresso: <strong><?= $progresso ?>%</strong>
  </div>
</div>

<div class="progress mb-4" style="height:8px">
  <div class="progress-bar bg-success" style="width:<?= $progresso ?>%"></div>
</div>

<div class="row g-4">
  <!-- Aulas -->
  <div class="col-lg-8">
    <div class="data-card">
      <div class="data-card-header"><h6 class="data-card-title"><i class="bi bi-play-circle me-2"></i>Aulas</h6></div>
      <div class="p-3">
        <?php if($aulas): ?>
        <div class="list-group list-group-flush">
          <?php foreach($aulas as $i=>$a):
            $visto = in_array($a['id'],$assistidas);
          ?>
          <div class="list-group-item border-0 px-0 py-2">
            <div class="d-flex align-items-center gap-3">
              <div style="width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;background:<?= $visto?'var(--success)':'#e9ecef' ?>;color:<?= $visto?'white':'#6c757d' ?>">
                <?= $visto ? '<i class="bi bi-check2"></i>' : '<span style="font-size:12px;font-weight:700">'.($i+1).'</span>' ?>
              </div>
              <div class="flex-grow-1">
                <div style="font-weight:600;font-size:14px"><?= e($a['titulo']) ?></div>
                <?php if($a['descricao']): ?><div style="font-size:12px;color:var(--text-muted)"><?= e($a['descricao']) ?></div><?php endif; ?>
              </div>
              <?php
              $url = $a['url_video'] ?? '';
              $embed = !str_starts_with($url,'upload:') ? embedVideo($url) : '';
              ?>
              <?php if($embed): ?>
              <a href="<?= e($embed) ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-play-fill me-1"></i>Assistir
              </a>
              <?php elseif(str_starts_with($url,'upload:')): ?>
              <a href="<?= uploadUrl('videos/'.substr($url,7)) ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-play-fill me-1"></i>Assistir
              </a>
              <?php endif; ?>
              <?php if(!$visto): ?>
              <form method="POST" action="<?= APP_URL ?>/aluno/cursos/<?= $curso['id'] ?>/aula">
                <?= csrfField() ?>
                <input type="hidden" name="aula_id" value="<?= $a['id'] ?>">
                <button type="submit" class="btn btn-sm btn-success">
                  <i class="bi bi-check2"></i>
                </button>
              </form>
              <?php endif; ?>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="text-center text-muted py-4">Nenhuma aula disponível.</div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Sidebar -->
  <div class="col-lg-4">
    <!-- Materiais -->
    <?php if($materiais): ?>
    <div class="data-card mb-3">
      <div class="data-card-header"><h6 class="data-card-title"><i class="bi bi-file-earmark me-2"></i>Materiais</h6></div>
      <div class="p-3">
        <?php foreach($materiais as $m): ?>
        <a href="<?= uploadUrl('materiais/'.$m['arquivo']) ?>" target="_blank"
           class="d-flex align-items-center gap-2 py-2 text-decoration-none" style="border-bottom:1px solid var(--border)">
          <i class="bi bi-file-earmark-arrow-down text-primary"></i>
          <span style="font-size:13px;color:var(--text)"><?= e($m['titulo']) ?></span>
        </a>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <!-- Ações -->
    <div class="data-card">
      <div class="data-card-header"><h6 class="data-card-title">Ações</h6></div>
      <div class="p-3 d-grid gap-2">
        <?php if($curso['tem_avaliacao']): ?>
        <a href="<?= APP_URL ?>/aluno/cursos/<?= $curso['id'] ?>/avaliacao" class="btn btn-outline-primary">
          <i class="bi bi-clipboard-check me-1"></i>Avaliação
        </a>
        <?php endif; ?>
        <?php if($matricula['status']==='concluida'): ?>
        <a href="<?= APP_URL ?>/aluno/cursos/<?= $curso['id'] ?>/certificado" class="btn btn-warning">
          <i class="bi bi-award me-1"></i>Emitir Certificado
        </a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
