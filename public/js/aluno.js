// public/js/aluno.js
document.addEventListener("DOMContentLoaded", function () {
  // Mark lesson as done via AJAX
  const markDoneBtn = document.getElementById("markDoneBtn");
  if (markDoneBtn) {
    markDoneBtn.addEventListener("click", function () {
      const aulaId = this.dataset.aula;
      const cursoId = this.dataset.curso;
      const baseUrl = typeof appUrl !== "undefined" ? appUrl : "";
      const btn = this;
      btn.disabled = true;
      btn.innerHTML =
        '<span class="spinner-border spinner-border-sm me-2"></span>Salvando...';
      fetch(
        `${baseUrl}/aluno/marcar_aula.php?aula_id=${aulaId}&curso_id=${cursoId}`,
      )
        .then((r) => r.json())
        .then((data) => {
          if (data.ok) {
            btn.classList.remove("btn-outline-success");
            btn.classList.add("btn-success");
            btn.innerHTML =
              '<i class="bi bi-check-circle-fill me-2"></i>Aula concluída!';
            // Atualizar barra de progresso
            const bar = document.querySelector(".progress-bar-ead");
            if (bar) bar.style.width = data.progresso + "%";
            const pct = document.querySelector(".fw-bold.text-primary");
            if (pct) pct.textContent = data.progresso + "%";
            const info = document.querySelector(".text-muted.mt-1.d-block");
            // Recarregar para atualizar estado (progresso, avaliação, certificado)
            setTimeout(() => location.reload(), 800);
          } else {
            btn.disabled = false;
            btn.innerHTML =
              '<i class="bi bi-check-circle me-1"></i>Marcar como concluída';
            alert("Erro ao salvar. Tente novamente.");
          }
        })
        .catch(() => {
          btn.disabled = false;
          btn.innerHTML =
            '<i class="bi bi-check-circle me-1"></i>Marcar como concluída';
          alert("Erro de conexão. Tente novamente.");
        });
    });
  }

  // Quiz: select option
  document.querySelectorAll(".quiz-option").forEach((opt) => {
    opt.addEventListener("click", function () {
      const group = this.dataset.group;
      document
        .querySelectorAll(`.quiz-option[data-group="${group}"]`)
        .forEach((o) => o.classList.remove("selected"));
      this.classList.add("selected");
      // set hidden input
      const input = document.querySelector(`input[name="resp_${group}"]`);
      if (input) input.value = this.dataset.value;
    });
  });

  // Auto-hide alerts
  document.querySelectorAll(".alert.fade.show").forEach((a) => {
    setTimeout(() => {
      const inst = bootstrap.Alert.getOrCreateInstance(a);
      if (inst) inst.close();
    }, 5000);
  });
});
