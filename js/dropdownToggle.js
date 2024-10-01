document.addEventListener('DOMContentLoaded', function() {
    const userIcon = document.querySelector('.user-icon');
    const dropdown = document.querySelector('.dropdown');

    if (userIcon && dropdown) {
        userIcon.addEventListener('click', function(event) {
            event.stopPropagation();
            dropdown.classList.toggle('active');
        });

        // Закрытие меню при клике вне его
        document.addEventListener('click', function() {
            dropdown.classList.remove('active');
        });
    }
});
