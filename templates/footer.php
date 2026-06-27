<?php
// templates/footer.php (VERSÃO LIMPA, SEM TOASTS)
?>
        <footer class="main-footer">
            <div class="footer-left">
                <strong>&copy; <?= date('Y') ?> ENFAS GED.</strong> Todos os direitos reservados.
            </div>
            <div class="footer-links d-none d-sm-flex">
                <a href="sobre" id="footer-sobre-link">Sobre</a>
                <span class="mx-2">•</span>
                <a href="manual">Manual</a>
                <span class="mx-2">•</span>
                <a href="privacidade_publica">Privacidade</a>
            </div>
        </footer>
    </div><script src="<?= BASE_URL ?>/assets/plugins/jquery/jquery.min.js"></script>
    <script src="<?= BASE_URL ?>/assets/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="<?= BASE_URL ?>/assets/dist/js/adminlte.min.js"></script>
    <script src="<?= BASE_URL ?>/assets/dist/js/ged-modern.js"></script>
    <script src="<?= BASE_URL ?>/assets/dist/js/keyboard-shortcuts.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <script>
        // Registra o Service Worker para PWA
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('<?= BASE_URL ?>/service-worker.js')
                    .then(registration => {
                        console.log('Service Worker registrado com sucesso:', registration.scope);
                    })
                    .catch(error => {
                        console.log('Falha ao registrar Service Worker:', error);
                    });
            });
        }
    </script>

    <!-- Tema (vanilla JS, sem jQuery, executado após todos os scripts) -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const themeSwitch = document.getElementById('theme-switch');
            if (themeSwitch) {
                const body = document.body;
                const html = document.documentElement;
                const lampIcon = themeSwitch.querySelector('i');
                
                // Remove classe legacy theme-gray
                body.classList.remove('theme-gray');
                html.classList.remove('theme-gray');
                
                const applyInitialTheme = () => {
                    const savedTheme = localStorage.getItem('theme') || 'light';
                    if (savedTheme === 'dark') {
                        body.classList.add('dark-mode');
                        if(lampIcon) lampIcon.className = 'far fa-fw fa-lightbulb'; // Lâmpada vazia (escuro)
                    } else {
                        body.classList.remove('dark-mode');
                        if(lampIcon) lampIcon.className = 'fas fa-fw fa-lightbulb'; // Lâmpada ligada (claro)
                    }
                    body.classList.remove('theme-gray');
                    html.classList.remove('theme-gray');
                };
                
                themeSwitch.addEventListener('click', function(event) {
                    event.preventDefault();
                    body.classList.toggle('dark-mode');
                    body.classList.remove('theme-gray');
                    html.classList.remove('theme-gray');
                    
                    if (body.classList.contains('dark-mode')) {
                        localStorage.setItem('theme', 'dark');
                        if(lampIcon) lampIcon.className = 'far fa-fw fa-lightbulb';
                    } else {
                        localStorage.setItem('theme', 'light');
                        if(lampIcon) lampIcon.className = 'fas fa-fw fa-lightbulb';
                    }
                });
                
                applyInitialTheme();
            }

            // Modal Sobre
            const footerSobreLink = document.getElementById('footer-sobre-link');
            const modalSobre = document.getElementById('modalSobre');
            if (footerSobreLink && modalSobre) {
                footerSobreLink.addEventListener('click', function(e){
                    e.preventDefault();
                    // Tenta usar Bootstrap modal se jQuery existir
                    if (typeof $ !== 'undefined') {
                        $(modalSobre).modal('show');
                    }
                });
            }
        });
    </script>
</body>
</html>