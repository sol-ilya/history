// copyToken.js

document.addEventListener('DOMContentLoaded', function() {
    const copyButton = document.getElementById('copy_token');
    if (copyButton) {
        copyButton.addEventListener('click', function() {
            const tokenInput = document.getElementById('api_token');
            tokenInput.select();
            tokenInput.setSelectionRange(0, 99999); // Для мобильных устройств

            // Копирование текста
            document.execCommand('copy');

            // Временное уведомление об успешном копировании
            copyButton.textContent = 'Скопировано!';
            copyButton.disabled = true;
            setTimeout(() => {
                copyButton.textContent = 'Скопировать';
                copyButton.disabled = false;
            }, 2000);
        });
    }
});
