<?php if (!isset($special_page) || !$special_page) : ?>
    <footer class="bg-light text-center text-lg-start fixed-bottom">
        <div class="text-center p-3">
            &copy; <?php echo date('Y'); ?> Школьный портал
        </div>
    </footer>
<?php endif; ?>

    <!-- Подключение Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script> 

    <!-- Подключение других скриптов -->
    <script src="/js/togglePassword.js"></script>
    <script src="/js/copyKey.js"></script>
</body>
</html>
