// public/js/aluno.js
document.addEventListener('DOMContentLoaded', function () {
  // Mark lesson as done via AJAX
  const markDoneBtn = document.getElementById('markDoneBtn');
  if (markDoneBtn) {
    markDoneBtn.addEventListener('click', function () {
      const aulaId  = this.dataset.aula;
      const cursoId = this.dataset.curso;
      fetch(`${appUrl}/aluno/marcar_aula.php?aula_id=${aulaId}&curso_id=${cursoId}`)
        .then(r => r.json())
        .then(data => {
          if (data.ok) {
            this.classList.remove('btn-outline-success');
            this.classList.add('btn-success');
            this.innerHTML = '<i class="bi bi-check-circle-fill me-2"></i>Aula concluída!';
            this.disabled = true;
            // update progress bar if exists
            const pb = document.getElementById('progressBar');
            if (pb) { pb.style.width = data.progresso + '%'; pb.textContent = data.progresso + '%'; }
          }
        });
    });
  }

  // Quiz: select option
  document.querySelectorAll('.quiz-option').forEach(opt => {
    opt.addEventListener('click', function () {
      const group = this.dataset.group;
      document.querySelectorAll(`.quiz-option[data-group="${group}"]`).forEach(o => o.classList.remove('selected'));
      this.classList.add('selected');
      // set hidden input
      const input = document.querySelector(`input[name="resp_${group}"]`);
      if (input) input.value = this.dataset.value;
    });
  });

  // Auto-hide alerts
  document.querySelectorAll('.alert.fade.show').forEach(a => {
    setTimeout(() => {
      const inst = bootstrap.Alert.getOrCreateInstance(a);
      if (inst) inst.close();
    }, 5000);
  });
});
