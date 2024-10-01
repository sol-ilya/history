// copyKey.js

document.addEventListener('DOMContentLoaded', function() {
    const copyButton = document.getElementById('copy_key');
    if (copyButton) {
        copyButton.addEventListener('click', function() {
            const keyInput = document.getElementById('api_key');
            keyInput.select();
            keyInput.setSelectionRange(0, 99999); // Для мобильных устройств

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
