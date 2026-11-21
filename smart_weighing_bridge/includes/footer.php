      </div><!-- /.content -->
    </main><!-- /.main -->
  </div><!-- /.shell -->

  <script src="assets/js/live.js"></script>
  <script src="assets/js/app.js"></script>
  <?php if (!empty($pageScripts ?? [])): foreach ($pageScripts as $js): ?>
  <script><?= $js ?></script>
  <?php endforeach; endif; ?>
</body>
</html>
