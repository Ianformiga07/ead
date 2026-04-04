// public/js/aluno.js
document.addEventListener("DOMContentLoaded", function () {

  // ── [CORREÇÃO 4] Auto-marcar aula ao finalizar vídeo ─────────────────
  // Flag para evitar duplo disparo (auto + manual ou evento duplicado)
  let _aulaMarcando = false;

  function marcarAulaFeita(aulaId, cursoId) {
    // Ignora se já está em progresso ou se a aula já foi marcada
    if (_aulaMarcando) return;
    const btn = document.getElementById("markDoneBtn");
    // Se botão não existe, aula já está concluída — não dispara
    if (!btn) return;
    _aulaMarcando = true;

    // Feedback visual imediato
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Salvando...';

    const baseUrl = typeof appUrl !== "undefined" ? appUrl : "";
    fetch(`${baseUrl}/aluno/marcar_aula.php?aula_id=${aulaId}&curso_id=${cursoId}`)
      .then((r) => r.json())
      .then((data) => {
        if (data.ok) {
          btn.classList.remove("btn-outline-success");
          btn.classList.add("btn-success");
          btn.innerHTML = '<i class="bi bi-check-circle-fill me-2"></i>Aula concluída!';
          const bar = document.querySelector(".progress-bar-ead");
          if (bar) bar.style.width = data.progresso + "%";
          const pct = document.querySelector(".fw-bold.text-primary");
          if (pct) pct.textContent = data.progresso + "%";
          // Recarregar para atualizar sidebar, avaliação, certificado
          setTimeout(() => location.reload(), 1200);
        } else {
          // Rollback visual em caso de erro
          btn.disabled = false;
          btn.innerHTML = '<i class="bi bi-check-circle me-1"></i>Marcar como concluída';
          _aulaMarcando = false;
        }
      })
      .catch(() => {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-check-circle me-1"></i>Marcar como concluída';
        _aulaMarcando = false;
      });
  }

  // [CORREÇÃO 4] Vídeo local HTML5: evento 'ended'
  const videoEl = document.querySelector("video");
  if (videoEl) {
    const btn = document.getElementById("markDoneBtn");
    if (btn) {
      const aulaId  = btn.dataset.aula;
      const cursoId = btn.dataset.curso;
      videoEl.addEventListener("ended", function () {
        marcarAulaFeita(aulaId, cursoId);
      });
    }
  }

  // [CORREÇÃO 4] YouTube / Vimeo embed: recebe postMessage do player
  window.addEventListener("message", function (event) {
    try {
      const data = typeof event.data === "string" ? JSON.parse(event.data) : event.data;
      if (!data) return;
      const btn = document.getElementById("markDoneBtn");
      if (!btn) return; // aula já concluída, ignora
      // YouTube: onStateChange info=0 significa vídeo terminou
      if (data.event === "onStateChange" && data.info === 0) {
        marcarAulaFeita(btn.dataset.aula, btn.dataset.curso);
      }
      // Vimeo: evento "finish"
      if (data.event === "finish") {
        marcarAulaFeita(btn.dataset.aula, btn.dataset.curso);
      }
    } catch (e) {}
  });

  // [CORREÇÃO 4] Botão manual reutiliza marcarAulaFeita() (mesma função do auto)
  const markDoneBtn = document.getElementById("markDoneBtn");
  if (markDoneBtn) {
    markDoneBtn.addEventListener("click", function () {
      marcarAulaFeita(this.dataset.aula, this.dataset.curso);
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
