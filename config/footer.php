    <?php if (!isset($special_page) || !$special_page) : ?>
    <footer class="bg-light text-center text-lg-start fixed-bottom">
        <div class="text-center p-3">
            &copy; <?php echo date('Y'); ?> Школьный портал
        </div>
    </footer>
    <?php endif; ?>

    <!-- Подключение Bootstrap JS и зависимостей -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <!-- Подключаем Popper.js, необходим для некоторых компонентов Bootstrap -->
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <!-- Подключаем Bootstrap JS -->
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script> 

    <script src="../js/togglePassword.js"></script>
    <script src="../js/copyKey.js"></script>
</html>
</body>