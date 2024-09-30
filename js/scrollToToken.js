// scrollToToken.js

document.addEventListener('DOMContentLoaded', function() {
    // Проверяем наличие элемента с классом 'success_token'
    const successToken = document.querySelector('.success_token');
    if (successToken) {
        const tokenSection = document.getElementById('api-token-section');
        if (tokenSection) {
            tokenSection.scrollTop = 0;

            //document.documentElement.scrollTop = tokenSection.offsetTop;

            //tokenSection.scrollIntoView({ behavior: 'auto' , block: "start" });
        }
    }
});
