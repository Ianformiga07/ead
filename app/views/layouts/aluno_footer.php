  </div><!-- /container -->
</div><!-- /aluno-wrapper -->

<footer class="aluno-footer">
  <div class="container text-center">
    <span class="text-muted small">© <?= date('Y') ?> <?= APP_NAME ?> — Todos os direitos reservados</span>
  </div>
</footer>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert2/11.10.5/sweetalert2.all.min.js"></script>
<script src="<?= APP_URL ?>/public/js/aluno.js"></script>
<?php if (!empty($extraJs)) echo $extraJs; ?>
</body>
</html>
