<?php
// templates/footer.php (VERSÃO LIMPA, SEM TOASTS)
?>
        <footer class="main-footer">
            <strong>&copy; <?= date('Y') ?> ENFAS GED.</strong> Todos os direitos reservados.
            <div class="float-right d-none d-sm-inline-block">
                <a href="sobre" id="footer-sobre-link">Sobre</a> &middot; <a href="manual">Manual</a> &middot; <a href="privacidade">Privacidade</a>
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

        // Lógica do tema (continua a mesma)
        $(function () {
            const themeSwitch = document.getElementById('theme-switch');
            if (themeSwitch) {
                const body = document.body;
                const lampIcon = themeSwitch.querySelector('i');
                const applyInitialTheme = () => {
                    const savedTheme = localStorage.getItem('theme');
                    if (savedTheme === 'dark') {
                        body.classList.add('dark-mode');
                        if(lampIcon) lampIcon.className = 'far fa-fw fa-lightbulb';
                    } else {
                        body.classList.remove('dark-mode');
                        if(lampIcon) lampIcon.className = 'fas fa-fw fa-lightbulb';
                    }
                    // Injeta CSS com cores do branding (se definidas em PHP)
                    try {
                        const styleId = 'branding-css-vars';
                        const current = document.getElementById(styleId);
                        if (!current) {
                            const s = document.createElement('style');
                            s.id = styleId;
                            s.textContent = `:root{--brand-primary: <?= defined('BRAND_PRIMARY_COLOR') ? BRAND_PRIMARY_COLOR : '#007bff' ?>; --brand-accent: <?= defined('BRAND_ACCENT_COLOR') ? BRAND_ACCENT_COLOR : '#28a745' ?>;}`;
                            document.head.appendChild(s);
                        }
                    } catch(e) {}
                };
                themeSwitch.addEventListener('click', function(event) {
                    event.preventDefault();
                    body.classList.toggle('dark-mode');
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
        });
        // Abre o modal Sobre quando clicar no link do rodapé (mantém fallback para a página)
        $(function(){
            $('#footer-sobre-link').on('click', function(e){
                if (typeof $ !== 'undefined' && $('#modalSobre').length) {
                    e.preventDefault();
                    $('#modalSobre').modal('show');
                }
            });
        });
    </script>
</body>
</html>