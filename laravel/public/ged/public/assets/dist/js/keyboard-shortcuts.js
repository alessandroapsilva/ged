/**
 * GED Keyboard Shortcuts - Sistema de Atalhos de Teclado
 * Versão 1.0
 */

(function() {
    'use strict';

    class KeyboardShortcuts {
        constructor() {
            this.shortcuts = new Map();
            this.init();
        }

        init() {
            // Registra atalhos padrão
            this.registerDefaultShortcuts();
            
            // Listener global
            document.addEventListener('keydown', this.handleKeyDown.bind(this));
            
            // Mostra dica inicial
            this.showInitialHint();
        }

        registerDefaultShortcuts() {
            // Navegação
            this.register('ctrl+k', () => this.focusSearch(), 'Focar no campo de busca');
            this.register('ctrl+/', () => this.showShortcutsModal(), 'Mostrar todos os atalhos');
            this.register('g h', () => this.navigateTo('painel_produtividade.php'), 'Ir para Home/Dashboard');
            this.register('g d', () => this.navigateTo('documentos.php'), 'Ir para Documentos');
            this.register('g u', () => this.navigateTo('usuarios_listar.php'), 'Ir para Usuários');
            
            // Ações
            this.register('ctrl+n', () => this.newDocument(), 'Novo Documento');
            this.register('ctrl+shift+n', () => this.newFolder(), 'Nova Pasta');
            this.register('ctrl+s', (e) => this.saveForm(e), 'Salvar formulário atual');
            this.register('escape', () => this.closeModals(), 'Fechar modais/diálogos');
            
            // Tema
            this.register('ctrl+shift+t', () => this.toggleTheme(), 'Alternar tema claro/escuro');
            
            // Refresh
            this.register('ctrl+r', (e) => this.refreshPage(e), 'Atualizar página');
        }

        register(combination, callback, description = '') {
            this.shortcuts.set(combination.toLowerCase(), {
                callback,
                description
            });
        }

        handleKeyDown(e) {
            // Ignora se está em input/textarea (exceto para alguns atalhos específicos)
            const target = e.target;
            const isInput = target.tagName === 'INPUT' || target.tagName === 'TEXTAREA' || target.isContentEditable;
            
            // Cria string da combinação de teclas
            let combination = '';
            if (e.ctrlKey) combination += 'ctrl+';
            if (e.shiftKey) combination += 'shift+';
            if (e.altKey) combination += 'alt+';
            
            const key = e.key.toLowerCase();
            combination += key;
            
            // Verifica se o atalho existe
            const shortcut = this.shortcuts.get(combination);
            
            if (shortcut) {
                // Atalhos permitidos mesmo em inputs
                const allowedInInputs = ['ctrl+s', 'ctrl+k', 'escape', 'ctrl+/'];
                
                if (!isInput || allowedInInputs.includes(combination)) {
                    e.preventDefault();
                    shortcut.callback(e);
                    this.showFeedback(combination);
                }
            }
        }

        focusSearch() {
            const searchInput = document.querySelector('[data-instant-search]') || 
                              document.querySelector('input[name="q"]') ||
                              document.querySelector('input[type="search"]');
            
            if (searchInput) {
                searchInput.focus();
                searchInput.select();
            }
        }

        showShortcutsModal() {
            const shortcuts = Array.from(this.shortcuts.entries());
            
            const modal = document.createElement('div');
            modal.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.7);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 99999;
                animation: fadeIn 0.2s;
            `;
            
            modal.innerHTML = `
                <div class="modern-card" style="max-width: 600px; max-height: 80vh; overflow-y: auto; margin: 1rem;">
                    <div class="modern-card-header">
                        <h3 class="modern-card-title">⌨️ Atalhos de Teclado</h3>
                        <button class="toast-close" onclick="this.closest('[style*=fixed]').remove()">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div style="padding: 1rem;">
                        <p style="color: var(--text-secondary); margin-bottom: 1.5rem;">
                            Use estes atalhos para navegar mais rapidamente pelo sistema
                        </p>
                        <div style="display: grid; gap: 0.75rem;">
                            ${shortcuts.map(([combo, data]) => `
                                <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.75rem; background: var(--bg-secondary); border-radius: 8px;">
                                    <span style="color: var(--text-secondary); font-size: 0.875rem;">
                                        ${data.description}
                                    </span>
                                    <kbd style="
                                        background: var(--bg-primary);
                                        padding: 0.25rem 0.75rem;
                                        border-radius: 4px;
                                        font-size: 0.75rem;
                                        font-family: monospace;
                                        border: 1px solid var(--border-color);
                                        box-shadow: 0 2px 0 var(--border-color);
                                    ">
                                        ${combo.toUpperCase().replace(/\+/g, ' + ')}
                                    </kbd>
                                </div>
                            `).join('')}
                        </div>
                        <div style="margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid var(--border-color); text-align: center;">
                            <small style="color: var(--text-tertiary);">
                                💡 Pressione <kbd>Ctrl + /</kbd> para ver este painel novamente
                            </small>
                        </div>
                    </div>
                </div>
            `;
            
            document.body.appendChild(modal);
            
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    modal.remove();
                }
            });
        }

        navigateTo(url) {
            window.location.href = url;
        }

        newDocument() {
            const newDocUrl = 'documentos_adicionar.php';
            if (window.location.pathname.includes(newDocUrl)) {
                window.GED.Toast.info('Você já está na página de novo documento');
            } else {
                this.navigateTo(newDocUrl);
            }
        }

        newFolder() {
            // Trigger do modal de nova pasta se existir
            const newFolderBtn = document.querySelector('[data-toggle="modal"][data-target="#modalNovaPasta"]');
            if (newFolderBtn) {
                newFolderBtn.click();
            } else {
                window.GED.Toast.info('Função de nova pasta não disponível nesta página');
            }
        }

        saveForm(e) {
            // Procura o formulário na página
            const forms = document.querySelectorAll('form');
            if (forms.length === 1) {
                forms[0].requestSubmit();
            } else if (forms.length > 1) {
                // Se houver múltiplos formulários, procura o que está visível
                const visibleForm = Array.from(forms).find(form => {
                    return form.offsetParent !== null; // Está visível
                });
                if (visibleForm) {
                    visibleForm.requestSubmit();
                }
            }
        }

        closeModals() {
            // Fecha modais do Bootstrap
            $('.modal').modal('hide');
            
            // Fecha dropdowns
            $('.dropdown-menu').removeClass('show');
            
            // Fecha resultados de busca
            const searchResults = document.getElementById('instant-search-results');
            if (searchResults) {
                searchResults.style.display = 'none';
            }
            
            // Remove overlays customizados
            document.querySelectorAll('[style*="position: fixed"]').forEach(el => {
                if (el.style.zIndex > 9000) {
                    el.remove();
                }
            });
        }

        toggleTheme() {
            const themeSwitch = document.getElementById('theme-switch');
            if (themeSwitch) {
                themeSwitch.click();
            }
        }

        refreshPage(e) {
            // Permite o comportamento padrão do Ctrl+R
            // mas mostra uma notificação
            setTimeout(() => {
                window.GED.Toast.info('Atualizando página...');
            }, 100);
        }

        showFeedback(combination) {
            // Mostra feedback visual discreto quando um atalho é usado
            const feedback = document.createElement('div');
            feedback.style.cssText = `
                position: fixed;
                bottom: 20px;
                right: 20px;
                background: var(--bg-primary);
                border: 1px solid var(--border-color);
                padding: 0.5rem 1rem;
                border-radius: 8px;
                box-shadow: var(--shadow-lg);
                font-size: 0.75rem;
                z-index: 9999;
                animation: slideInUp 0.3s;
                pointer-events: none;
            `;
            feedback.innerHTML = `
                <kbd style="
                    background: var(--bg-secondary);
                    padding: 0.25rem 0.5rem;
                    border-radius: 4px;
                    font-family: monospace;
                ">
                    ${combination.toUpperCase()}
                </kbd>
            `;
            
            document.body.appendChild(feedback);
            
            setTimeout(() => {
                feedback.style.animation = 'slideInUp 0.3s reverse';
                setTimeout(() => feedback.remove(), 300);
            }, 1000);
        }

        showInitialHint() {
            // Mostra dica inicial apenas uma vez
            if (!localStorage.getItem('ged_shortcuts_hint_shown')) {
                setTimeout(() => {
                    window.GED.Toast.info(
                        'Pressione Ctrl + / para ver todos os atalhos disponíveis',
                        '⌨️ Dica de Teclado'
                    );
                    localStorage.setItem('ged_shortcuts_hint_shown', 'true');
                }, 3000);
            }
        }
    }

    // Exporta para window.GED
    if (!window.GED) window.GED = {};
    window.GED.Shortcuts = new KeyboardShortcuts();

    // Log de inicialização
    console.log('🎹 Atalhos de teclado ativados! Pressione Ctrl+/ para ver todos.');
})();
