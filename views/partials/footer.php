  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <!-- Chart.js -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
  <!-- App JS -->
  <script>
    const BASE_URL = '<?= BASE_URL ?>';
    const CSRF_TOKEN = '<?= csrf_token() ?>';
  </script>
  <script src="<?= BASE_URL ?>/public/js/app.js"></script>
  <?= $extraScripts ?? '' ?>
</body>
</html>
