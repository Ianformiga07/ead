</div><!-- /container-fluid -->
</div><!-- /aluno-wrapper -->

<footer class="aluno-footer">
  <div class="container-fluid px-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
      <strong style="color:var(--primary)"><?= APP_NAME ?></strong>
      — Conselho Regional de Medicina Veterinária do Tocantins
    </div>
    <div>CRMV-TO &copy; <?= date('Y') ?> · Todos os direitos reservados</div>
  </div>
</footer>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/js/bootstrap.bundle.min.js"></script>
<script src="<?= APP_URL ?>/public/js/aluno.js"></script>
<script>
// ── Confirmação SweetAlert ao clicar em "Sair" ────────────────
document.querySelectorAll('a[href*="logout.php"]').forEach(function(link) {
  link.addEventListener('click', function(e) {
    e.preventDefault();
    var href = this.href;
    Swal.fire({
      title: 'Deseja sair?',
      text: 'Sua sessão será encerrada.',
      icon: 'question',
      showCancelButton: true,
      confirmButtonColor: '#003d7c',
      cancelButtonColor: '#6c757d',
      confirmButtonText: 'Sim, sair',
      cancelButtonText: 'Cancelar',
      reverseButtons: true
    }).then(function(result) {
      if (result.isConfirmed) {
        window.location.href = href;
      }
    });
  });
});

// ── Aviso de sessão prestes a expirar ────────────────────────
(function() {
  var TIMEOUT_MS   = <?= SESSION_TIMEOUT ?> * 1000;
  var WARN_BEFORE  = 60 * 1000;
  var warningShown = false;
  var timer;

  function resetTimer() {
    clearTimeout(timer);
    warningShown = false;
    timer = setTimeout(showWarning, TIMEOUT_MS - WARN_BEFORE);
  }

  function showWarning() {
    if (warningShown) return;
    warningShown = true;
    var timerInterval;
    Swal.fire({
      title: 'Sessão expirando!',
      html: 'Sua sessão expirará em <strong id="swal-countdown">' + Math.floor(WARN_BEFORE / 1000) + '</strong> segundos por inatividade.',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#003d7c',
      cancelButtonColor: '#dc3545',
      confirmButtonText: 'Continuar conectado',
      cancelButtonText: 'Sair agora',
      allowOutsideClick: false,
      timer: WARN_BEFORE,
      timerProgressBar: true,
      didOpen: function() {
        timerInterval = setInterval(function() {
          var cnt = document.getElementById('swal-countdown');
          if (cnt) cnt.textContent = Math.ceil(Swal.getTimerLeft() / 1000);
        }, 500);
      },
      willClose: function() { clearInterval(timerInterval); }
    }).then(function(result) {
      if (result.isConfirmed) {
        fetch(window.location.href, { method: 'HEAD', credentials: 'same-origin' })
          .then(function() { resetTimer(); });
      } else {
        window.location.href = '<?= APP_URL ?>/logout.php';
      }
    });
  }

  ['mousemove','keydown','click','scroll','touchstart'].forEach(function(ev) {
    document.addEventListener(ev, resetTimer, { passive: true });
  });

  resetTimer();
})();
</script>
<?php if (!empty($extraJs)) echo $extraJs; ?>
</body>
</html>