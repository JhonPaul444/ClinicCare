    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.9/dist/js/bootstrap.bundle.min.js" integrity="sha384-WyOxWvTyr1QaMPfiPJSv76iReVthorl5uWlFtZPyK0IE0hB0hGzo9y5Bp9mYanr8Bu" crossorigin="anonymous"></script>
    <?php $scriptAssetPath = __DIR__ . '/../assets/js/main.js'; $scriptVersion = is_file($scriptAssetPath) ? (string)filemtime($scriptAssetPath) : '1'; ?>
    <script src="<?php echo htmlspecialchars($appBasePath ?? '.', ENT_QUOTES, 'UTF-8'); ?>/assets/js/main.js?v=<?php echo htmlspecialchars($scriptVersion, ENT_QUOTES, 'UTF-8'); ?>" defer></script>
</body>
</html>
