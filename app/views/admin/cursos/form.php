<?php $pageTitle = $curso ? 'Editar Curso' : 'Novo Curso'; ?>
<div class="mb-4">
  <h1 style="font-size:22px;font-weight:800;margin:0;color:var(--primary)">
    <i class="bi bi-journal-bookmark-fill me-2"></i><?= $pageTitle ?>
  </h1>
  <p class="page-subtitle"><a href="<?= APP_URL ?>/admin/cursos">Cursos</a> / <?= $pageTitle ?></p>
</div>

<div class="data-card">
  <div class="data-card-header"><h6 class="data-card-title">Dados do Curso</h6></div>
  <div class="p-4">
    <form method="POST" action="<?= $curso ? APP_URL.'/admin/cursos/'.$curso['id'].'/salvar' : APP_URL.'/admin/cursos/salvar' ?>" enctype="multipart/form-data">
      <?= csrfField() ?>
      <div class="row g-3">
        <div class="col-md-8">
          <label class="form-label fw-semibold">Nome do Curso *</label>
          <input type="text" name="nome" class="form-control" value="<?= e($curso['nome'] ?? '') ?>" required>
        </div>
        <div class="col-md-2">
          <label class="form-label fw-semibold">Tipo</label>
          <select name="tipo" class="form-select">
            <?php foreach(['ead'=>'EAD','presencial'=>'Presencial','hibrido'=>'Híbrido'] as $v=>$l): ?>
            <option value="<?= $v ?>" <?= ($curso['tipo'] ?? 'ead') === $v ? 'selected' : '' ?>><?= $l ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-2">
          <label class="form-label fw-semibold">C/H (horas)</label>
          <input type="number" name="carga_horaria" class="form-control" value="<?= (int)($curso['carga_horaria'] ?? 0) ?>" min="1">
        </div>
        <div class="col-12">
          <label class="form-label fw-semibold">Descrição</label>
          <textarea name="descricao" class="form-control" rows="3"><?= e($curso['descricao'] ?? '') ?></textarea>
        </div>
        <div class="col-md-3">
          <label class="form-label fw-semibold">Status</label>
          <select name="status" class="form-select">
            <option value="1" <?= ($curso['status'] ?? 1) == 1 ? 'selected' : '' ?>>Ativo</option>
            <option value="0" <?= ($curso['status'] ?? 1) == 0 ? 'selected' : '' ?>>Inativo</option>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label fw-semibold">Tem Avaliação?</label>
          <select name="tem_avaliacao" class="form-select">
            <option value="0" <?= ($curso['tem_avaliacao'] ?? 0) == 0 ? 'selected' : '' ?>>Não</option>
            <option value="1" <?= ($curso['tem_avaliacao'] ?? 0) == 1 ? 'selected' : '' ?>>Sim</option>
          </select>
        </div>
        <div class="col-md-2">
          <label class="form-label fw-semibold">Nota Mínima (%)</label>
          <input type="number" name="nota_minima" class="form-control" value="<?= (int)($curso['nota_minima'] ?? 60) ?>" min="0" max="100">
        </div>
        <div class="col-md-4">
          <label class="form-label fw-semibold">Imagem do Curso</label>
          <input type="file" name="imagem" class="form-control" accept="image/*">
          <?php if (!empty($curso['imagem'])): ?>
          <small class="text-muted">Atual: <?= e($curso['imagem']) ?></small>
          <?php endif; ?>
        </div>
      </div>
      <div class="d-flex gap-2 mt-4">
        <button type="submit" class="btn btn-primary"><i class="bi bi-check2 me-1"></i>Salvar</button>
        <a href="<?= APP_URL ?>/admin/cursos" class="btn btn-outline-secondary">Cancelar</a>
      </div>
    </form>
  </div>
</div>
