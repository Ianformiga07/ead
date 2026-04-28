<?php $pageTitle = 'Matrículas'; ?>
<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h1 style="font-size:22px;font-weight:800;margin:0;color:var(--primary)">
      <i class="bi bi-person-check-fill me-2"></i>Matrículas
    </h1>
    <p class="page-subtitle">Gerencie as matrículas dos alunos</p>
  </div>
  <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalMatricula">
    <i class="bi bi-plus-circle me-1"></i>Nova Matrícula
  </button>
</div>
<div class="data-card mb-4">
  <div class="p-3">
    <form method="GET" class="row g-2">
      <div class="col-md-6"><input type="text" name="busca" class="form-control" placeholder="Buscar por aluno ou curso..." value="<?= e($busca) ?>"></div>
      <div class="col-md-3">
        <select name="status" class="form-select">
          <option value="">Todos os status</option>
          <?php foreach(['ativa','concluida','cancelada'] as $s): ?>
          <option value="<?= $s ?>" <?= $status===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-3 d-flex gap-2">
        <button type="submit" class="btn btn-primary flex-grow-1"><i class="bi bi-search me-1"></i>Filtrar</button>
        <?php if($busca||$status): ?><a href="<?= APP_URL ?>/admin/matriculas" class="btn btn-outline-secondary"><i class="bi bi-x"></i></a><?php endif; ?>
      </div>
    </form>
  </div>
</div>
<div class="data-card">
  <div class="data-card-header">
    <h6 class="data-card-title"><?= number_format($pag['total']) ?> matrícula(s)</h6>
  </div>
  <div class="table-responsive">
    <table class="table table-ead">
      <thead><tr><th>Aluno</th><th>Curso</th><th>Data</th><th>Progresso</th><th>Status</th><th class="text-end">Ações</th></tr></thead>
      <tbody>
      <?php if($matriculas): foreach($matriculas as $m): ?>
      <tr>
        <td>
          <strong><?= e($m['aluno_nome']) ?></strong><br>
          <small class="text-muted"><?= e($m['crmv'] ?? '') ?></small>
        </td>
        <td><?= e($m['curso_nome']) ?><br><small class="text-muted"><?= ucfirst(e($m['curso_tipo'])) ?></small></td>
        <td><?= dataBR($m['matriculado_em']) ?></td>
        <td>
          <div class="progress" style="height:6px;width:80px">
            <div class="progress-bar" style="width:<?= (int)$m['progresso'] ?>%"></div>
          </div>
          <small class="text-muted"><?= (int)$m['progresso'] ?>%</small>
        </td>
        <td><?= badgeStatus($m['status']) ?></td>
        <td class="text-end">
          <?php if($m['status']==='ativa'): ?>
          <form method="POST" action="<?= APP_URL ?>/admin/matriculas/<?= $m['id'] ?>/cancelar" class="d-inline" onsubmit="return confirm('Cancelar matrícula?')">
            <?= csrfField() ?>
            <button class="btn btn-sm btn-outline-danger">Cancelar</button>
          </form>
          <?php else: ?>
          <span class="text-muted small"><?= dataBR($m['concluido_em']) ?></span>
          <?php endif; ?>
        </td>
      </tr>
      <?php endforeach; else: ?>
      <tr><td colspan="6" class="text-center text-muted py-5">Nenhuma matrícula encontrada.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
  <?php if($pag['pages']>1): ?>
  <div class="d-flex justify-content-center py-3">
    <nav><ul class="pagination pagination-sm mb-0">
      <?php if($pag['prev']): ?><li class="page-item"><a class="page-link" href="?<?= http_build_query(array_merge($_GET,['page'=>$pag['prev']])) ?>"><i class="bi bi-chevron-left"></i></a></li><?php endif; ?>
      <?php for($i=max(1,$pag['current']-2);$i<=min($pag['pages'],$pag['current']+2);$i++): ?>
      <li class="page-item <?= $i===$pag['current']?'active':'' ?>"><a class="page-link" href="?<?= http_build_query(array_merge($_GET,['page'=>$i])) ?>"><?= $i ?></a></li>
      <?php endfor; ?>
      <?php if($pag['next']): ?><li class="page-item"><a class="page-link" href="?<?= http_build_query(array_merge($_GET,['page'=>$pag['next']])) ?>"><i class="bi bi-chevron-right"></i></a></li><?php endif; ?>
    </ul></nav>
  </div>
  <?php endif; ?>
</div>

<!-- Modal Nova Matrícula -->
<div class="modal fade" id="modalMatricula" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Nova Matrícula</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="<?= APP_URL ?>/admin/matriculas/salvar">
        <?= csrfField() ?>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label fw-semibold">Buscar Aluno</label>
            <input type="text" id="buscaAluno" class="form-control" placeholder="Digite nome ou CRMV...">
            <select name="aluno_id" id="selectAluno" class="form-select mt-2" required size="4" style="display:none"></select>
            <input type="hidden" name="aluno_id" id="alunoIdHidden">
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Curso</label>
            <select name="curso_id" class="form-select" required>
              <option value="">Selecione...</option>
              <?php foreach($cursos as $c): ?>
              <option value="<?= $c['id'] ?>"><?= e($c['nome']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary">Matricular</button>
        </div>
      </form>
    </div>
  </div>
</div>
<script>
var buscaTimeout;
document.getElementById('buscaAluno').addEventListener('input',function(){
  clearTimeout(buscaTimeout);
  var q=this.value;
  if(q.length<2){document.getElementById('selectAluno').style.display='none';return;}
  buscaTimeout=setTimeout(function(){
    fetch('<?= APP_URL ?>/admin/buscar-aluno?q='+encodeURIComponent(q))
      .then(r=>r.json()).then(data=>{
        var sel=document.getElementById('selectAluno');
        sel.innerHTML='';
        data.forEach(function(a){
          var o=document.createElement('option');
          o.value=a.id;
          o.textContent=a.nome+(a.crmv?' ('+a.crmv+')':'');
          sel.appendChild(o);
        });
        sel.style.display=data.length?'block':'none';
        sel.addEventListener('change',function(){
          document.getElementById('alunoIdHidden').value=this.value;
        });
      });
  },300);
});
</script>
