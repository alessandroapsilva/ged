// Fix theme - Remove qualquer tema antigo e força light
(function() {
    'use strict';
    
    // Limpa tema gray antigo
    const theme = localStorage.getItem('theme');
    if (theme === 'gray' || theme === 'theme-gray') {
        console.log('Removendo tema antigo:', theme);
        localStorage.removeItem('theme');
        localStorage.setItem('theme', 'light');
    }
    
    // Se não há tema, define light
    if (!localStorage.getItem('theme')) {
        localStorage.setItem('theme', 'light');
    }
    
    function removeDarkClassesIfNeeded() {
        if (localStorage.getItem('theme') !== 'dark') {
            document.documentElement.classList.remove('dark-mode', 'theme-gray');
            if (document.body) {
                document.body.classList.remove('dark-mode', 'theme-gray');
            }
        }
    }

    // Remove classes de tema escuro; pode rodar antes do <body> existir
    removeDarkClassesIfNeeded();
    document.addEventListener('DOMContentLoaded', removeDarkClassesIfNeeded);
    
    console.log('Tema atual:', localStorage.getItem('theme'));
})();
