document.addEventListener('DOMContentLoaded', function() {
    const copyButton = document.getElementById('copy_key');
    if (copyButton) {
        copyButton.addEventListener('click', function() {
            const keyInput = document.getElementById('api_key');

            // Копирование текста с помощью Clipboard API
            navigator.clipboard.writeText(keyInput.value).then(() => {
                // Временное уведомление об успешном копировании
                copyButton.textContent = 'Скопировано!';
                copyButton.disabled = true;
                setTimeout(() => {
                    copyButton.textContent = 'Скопировать';
                    copyButton.disabled = false;
                }, 2000);
            }).catch(err => {
                console.error('Ошибка при копировании:', err);
            });
        });
    }
});
