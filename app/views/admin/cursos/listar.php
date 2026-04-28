<?php $pageTitle = 'Cursos'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h1 style="font-size:22px;font-weight:800;margin:0;color:var(--primary)">
      <i class="bi bi-journal-bookmark-fill me-2"></i>Cursos
    </h1>
    <p class="page-subtitle">Gerencie os cursos da plataforma</p>
  </div>
  <a href="<?= APP_URL ?>/admin/cursos/novo" class="btn btn-primary">
    <i class="bi bi-plus-circle me-1"></i>Novo Curso
  </a>
</div>

<!-- Filtros -->
<div class="data-card mb-4">
  <div class="p-3">
    <form method="GET" class="row g-2 align-items-end">
      <div class="col-md-6">
        <input type="text" name="busca" class="form-control" placeholder="Buscar por nome ou descrição..."
               value="<?= e($busca) ?>">
      </div>
      <div class="col-md-3">
        <select name="tipo" class="form-select">
          <option value="">Todos os tipos</option>
          <option value="ead"        <?= $tipo === 'ead'        ? 'selected' : '' ?>>EAD</option>
          <option value="presencial" <?= $tipo === 'presencial' ? 'selected' : '' ?>>Presencial</option>
          <option value="hibrido"    <?= $tipo === 'hibrido'    ? 'selected' : '' ?>>Híbrido</option>
        </select>
      </div>
      <div class="col-md-3 d-flex gap-2">
        <button type="submit" class="btn btn-primary flex-grow-1">
          <i class="bi bi-search me-1"></i>Filtrar
        </button>
        <?php if ($busca || $tipo): ?>
        <a href="<?= APP_URL ?>/admin/cursos" class="btn btn-outline-secondary">
          <i class="bi bi-x"></i>
        </a>
        <?php endif; ?>
      </div>
    </form>
  </div>
</div>

<!-- Tabela -->
<div class="data-card">
  <div class="data-card-header">
    <h6 class="data-card-title">
      <?= number_format($pag['total']) ?> curso<?= $pag['total'] !== 1 ? 's' : '' ?> encontrado<?= $pag['total'] !== 1 ? 's' : '' ?>
    </h6>
  </div>
  <div class="table-responsive">
    <table class="table table-ead">
      <thead>
        <tr>
          <th>Curso</th>
          <th>Tipo</th>
          <th>C/H</th>
          <th>Avaliação</th>
          <th>Status</th>
          <th class="text-end">Ações</th>
        </tr>
      </thead>
      <tbody>
      <?php if ($cursos): foreach ($cursos as $c): ?>
      <tr>
        <td>
          <div style="font-weight:600;font-size:14px"><?= e($c['nome']) ?></div>
          <?php if ($c['descricao']): ?>
          <div style="font-size:12px;color:var(--text-muted)"><?= e(mb_substr(strip_tags($c['descricao']), 0, 80)) ?>...</div>
          <?php endif; ?>
        </td>
        <td><span class="badge bg-info text-dark"><?= ucfirst(e($c['tipo'])) ?></span></td>
        <td><?= (int)$c['carga_horaria'] ?>h</td>
        <td>
          <?php if ($c['tem_avaliacao']): ?>
            <span class="badge bg-success"><i class="bi bi-check2"></i> Sim (≥<?= (int)$c['nota_minima'] ?>%)</span>
          <?php else: ?>
            <span class="badge bg-secondary">Não</span>
          <?php endif; ?>
        </td>
        <td><?= $c['status'] ? '<span class="badge bg-success">Ativo</span>' : '<span class="badge bg-danger">Inativo</span>' ?></td>
        <td class="text-end">
          <a href="<?= APP_URL ?>/admin/cursos/<?= $c['id'] ?>" class="btn btn-sm btn-outline-primary">
            <i class="bi bi-pencil me-1"></i>Editar
          </a>
          <form method="POST" action="<?= APP_URL ?>/admin/cursos/<?= $c['id'] ?>/deletar"
                class="d-inline" onsubmit="return confirm('Excluir este curso?')">
            <?= csrfField() ?>
            <button type="submit" class="btn btn-sm btn-outline-danger">
              <i class="bi bi-trash"></i>
            </button>
          </form>
        </td>
      </tr>
      <?php endforeach; else: ?>
      <tr>
        <td colspan="6" class="text-center text-muted py-5">
          <i class="bi bi-journal-x" style="font-size:32px;display:block;margin-bottom:8px"></i>
          Nenhum curso encontrado.
        </td>
      </tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- Paginação -->
  <?php if ($pag['pages'] > 1): ?>
  <div class="d-flex justify-content-center py-3">
    <nav>
      <ul class="pagination pagination-sm mb-0">
        <?php if ($pag['prev']): ?>
        <li class="page-item">
          <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $pag['prev']])) ?>">
            <i class="bi bi-chevron-left"></i>
          </a>
        </li>
        <?php endif; ?>
        <?php for ($i = max(1, $pag['current']-2); $i <= min($pag['pages'], $pag['current']+2); $i++): ?>
        <li class="page-item <?= $i === $pag['current'] ? 'active' : '' ?>">
          <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
        </li>
        <?php endfor; ?>
        <?php if ($pag['next']): ?>
        <li class="page-item">
          <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $pag['next']])) ?>">
            <i class="bi bi-chevron-right"></i>
          </a>
        </li>
        <?php endif; ?>
      </ul>
    </nav>
  </div>
  <?php endif; ?>
</div>
