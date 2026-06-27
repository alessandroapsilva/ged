/**
 * GED Modern - Sistema de Notificações e Componentes JavaScript
 * @version 2.0
 * @author GED Team
 */

(function() {
    'use strict';

    // Sistema de Toast Notifications
    class ToastManager {
        constructor() {
            this.container = null;
            this.init();
        }

        init() {
            // Cria container se não existir
            if (!document.querySelector('.toast-container')) {
                this.container = document.createElement('div');
                this.container.className = 'toast-container';
                document.body.appendChild(this.container);
            } else {
                this.container = document.querySelector('.toast-container');
            }
        }

        show(message, type = 'info', title = null, duration = 5000) {
            const toast = document.createElement('div');
            toast.className = `toast-notification ${type}`;
            
            const icons = {
                success: 'fas fa-check-circle',
                error: 'fas fa-exclamation-circle',
                warning: 'fas fa-exclamation-triangle',
                info: 'fas fa-info-circle'
            };

            const colors = {
                success: '#10b981',
                error: '#ef4444',
                warning: '#f59e0b',
                info: '#3b82f6'
            };

            toast.innerHTML = `
                <div class="toast-icon" style="color: ${colors[type] || colors.info}">
                    <i class="${icons[type] || icons.info}"></i>
                </div>
                <div class="toast-content">
                    ${title ? `<div class="toast-title">${title}</div>` : ''}
                    <div class="toast-message">${message}</div>
                </div>
                <button class="toast-close" aria-label="Fechar">
                    <i class="fas fa-times"></i>
                </button>
            `;

            // Adiciona evento de fechar
            toast.querySelector('.toast-close').addEventListener('click', () => {
                this.remove(toast);
            });

            this.container.appendChild(toast);

            // Auto-remove após duração
            if (duration > 0) {
                setTimeout(() => {
                    this.remove(toast);
                }, duration);
            }

            return toast;
        }

        remove(toast) {
            toast.style.animation = 'slideInDown 0.2s reverse';
            setTimeout(() => {
                if (toast.parentNode) {
                    toast.parentNode.removeChild(toast);
                }
            }, 200);
        }

        success(message, title = 'Sucesso!') {
            return this.show(message, 'success', title);
        }

        error(message, title = 'Erro!') {
            return this.show(message, 'error', title);
        }

        warning(message, title = 'Atenção!') {
            return this.show(message, 'warning', title);
        }

        info(message, title = 'Informação') {
            return this.show(message, 'info', title);
        }
    }

    // Sistema de Loading
    class LoadingManager {
        constructor() {
            this.overlay = null;
        }

        show(message = 'Carregando...') {
            if (!this.overlay) {
                this.overlay = document.createElement('div');
                this.overlay.style.cssText = `
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    background: rgba(0, 0, 0, 0.5);
                    backdrop-filter: blur(5px);
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    z-index: 99999;
                `;
                this.overlay.innerHTML = `
                    <div style="
                        background: var(--bg-primary);
                        padding: 2rem;
                        border-radius: 1rem;
                        box-shadow: var(--shadow-xl);
                        text-align: center;
                    ">
                        <div class="spinner" style="
                            width: 40px;
                            height: 40px;
                            border-width: 4px;
                            margin: 0 auto 1rem;
                        "></div>
                        <div style="color: var(--text-primary); font-weight: 500;">
                            ${message}
                        </div>
                    </div>
                `;
            }
            document.body.appendChild(this.overlay);
        }

        hide() {
            if (this.overlay && this.overlay.parentNode) {
                this.overlay.parentNode.removeChild(this.overlay);
            }
        }
    }

    // Sistema de Busca Instantânea
    class InstantSearch {
        constructor(inputSelector, resultsSelector, searchUrl) {
            this.input = document.querySelector(inputSelector);
            this.resultsContainer = document.querySelector(resultsSelector);
            this.searchUrl = searchUrl;
            this.debounceTimer = null;
            this.minChars = 2;
            
            if (this.input) {
                this.init();
            }
        }

        init() {
            this.input.addEventListener('input', (e) => {
                this.handleInput(e.target.value);
            });

            // Fecha resultados ao clicar fora
            document.addEventListener('click', (e) => {
                if (!this.input.contains(e.target) && !this.resultsContainer.contains(e.target)) {
                    this.hideResults();
                }
            });
        }

        handleInput(query) {
            clearTimeout(this.debounceTimer);
            
            if (query.length < this.minChars) {
                this.hideResults();
                return;
            }

            this.debounceTimer = setTimeout(() => {
                this.search(query);
            }, 300);
        }

        async search(query) {
            try {
                this.showLoading();
                
                const response = await fetch(`${this.searchUrl}?q=${encodeURIComponent(query)}&instant=1`);
                const data = await response.json();
                
                this.displayResults(data);
            } catch (error) {
                console.error('Erro na busca:', error);
                this.hideResults();
            }
        }

        showLoading() {
            if (this.resultsContainer) {
                this.resultsContainer.innerHTML = `
                    <div class="search-result-item">
                        <div class="skeleton" style="height: 20px; margin-bottom: 8px;"></div>
                        <div class="skeleton" style="height: 16px; width: 60%;"></div>
                    </div>
                `;
                this.resultsContainer.style.display = 'block';
            }
        }

        displayResults(results) {
            if (!this.resultsContainer) return;

            if (!results || results.length === 0) {
                this.resultsContainer.innerHTML = `
                    <div class="search-result-item" style="text-align: center; color: var(--text-secondary);">
                        <i class="fas fa-search" style="font-size: 2rem; margin-bottom: 0.5rem; opacity: 0.5;"></i>
                        <p>Nenhum resultado encontrado</p>
                    </div>
                `;
                this.resultsContainer.style.display = 'block';
                return;
            }

            this.resultsContainer.innerHTML = results.map(item => `
                <a href="${item.url}" class="search-result-item" style="display: block; text-decoration: none; color: inherit;">
                    <div style="display: flex; align-items: start; gap: 0.75rem;">
                        <i class="${item.icon || 'fas fa-file'}" style="color: var(--primary-color); margin-top: 0.25rem;"></i>
                        <div style="flex: 1;">
                            <div style="font-weight: 600; color: var(--text-primary); margin-bottom: 0.25rem;">
                                ${this.highlight(item.title, this.input.value)}
                            </div>
                            ${item.description ? `
                                <div style="font-size: 0.875rem; color: var(--text-secondary);">
                                    ${this.highlight(item.description, this.input.value)}
                                </div>
                            ` : ''}
                            ${item.badge ? `
                                <span class="badge-modern ${item.badgeType || 'primary'}" style="margin-top: 0.5rem;">
                                    ${item.badge}
                                </span>
                            ` : ''}
                        </div>
                    </div>
                </a>
            `).join('');
            
            this.resultsContainer.style.display = 'block';
        }

        highlight(text, query) {
            const regex = new RegExp(`(${query})`, 'gi');
            return text.replace(regex, '<mark style="background: #fef08a; padding: 0 2px; border-radius: 2px;">$1</mark>');
        }

        hideResults() {
            if (this.resultsContainer) {
                this.resultsContainer.style.display = 'none';
            }
        }
    }

    // Sistema de Confirmação Modal
    class ConfirmModal {
        static async show(options = {}) {
            return new Promise((resolve) => {
                const defaults = {
                    title: 'Confirmar ação?',
                    message: 'Tem certeza que deseja continuar?',
                    confirmText: 'Confirmar',
                    cancelText: 'Cancelar',
                    type: 'warning'
                };

                const config = { ...defaults, ...options };
                
                const modal = document.createElement('div');
                modal.style.cssText = `
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    background: rgba(0, 0, 0, 0.5);
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    z-index: 99999;
                    animation: fadeIn 0.2s;
                `;

                modal.innerHTML = `
                    <div class="modern-card" style="max-width: 500px; margin: 1rem; animation: slideInUp 0.3s;">
                        <div class="modern-card-header">
                            <h3 class="modern-card-title">${config.title}</h3>
                        </div>
                        <div style="margin: 1.5rem 0;">
                            <p style="color: var(--text-secondary);">${config.message}</p>
                        </div>
                        <div style="display: flex; gap: 0.75rem; justify-content: flex-end;">
                            <button class="btn-modern" data-action="cancel" style="background: var(--bg-tertiary); color: var(--text-primary);">
                                ${config.cancelText}
                            </button>
                            <button class="btn-modern btn-modern-primary" data-action="confirm">
                                ${config.confirmText}
                            </button>
                        </div>
                    </div>
                `;

                document.body.appendChild(modal);

                const closeModal = (result) => {
                    modal.style.animation = 'fadeIn 0.2s reverse';
                    setTimeout(() => {
                        document.body.removeChild(modal);
                        resolve(result);
                    }, 200);
                };

                modal.querySelector('[data-action="confirm"]').addEventListener('click', () => closeModal(true));
                modal.querySelector('[data-action="cancel"]').addEventListener('click', () => closeModal(false));
                modal.addEventListener('click', (e) => {
                    if (e.target === modal) closeModal(false);
                });
            });
        }
    }

    // Utilitários de Formulário
    class FormUtils {
        static serialize(form) {
            const formData = new FormData(form);
            const data = {};
            for (let [key, value] of formData.entries()) {
                data[key] = value;
            }
            return data;
        }

        static async submit(form, url, options = {}) {
            const loading = new LoadingManager();
            const toast = new ToastManager();

            try {
                loading.show(options.loadingMessage || 'Enviando...');
                
                const formData = new FormData(form);
                const response = await fetch(url, {
                    method: options.method || 'POST',
                    body: formData
                });

                const result = await response.json();
                
                loading.hide();

                if (result.success) {
                    toast.success(result.message || 'Operação realizada com sucesso!');
                    if (options.onSuccess) options.onSuccess(result);
                } else {
                    toast.error(result.message || 'Ocorreu um erro ao processar a solicitação');
                    if (options.onError) options.onError(result);
                }

                return result;
            } catch (error) {
                loading.hide();
                toast.error('Erro de conexão. Tente novamente.');
                console.error('Erro no formulário:', error);
                return { success: false, error };
            }
        }
    }

    // Exporta para window
    window.GED = {
        Toast: new ToastManager(),
        Loading: new LoadingManager(),
        InstantSearch,
        ConfirmModal,
        FormUtils
    };

    // Auto-inicialização
    document.addEventListener('DOMContentLoaded', () => {
        // Inicializa busca instantânea se existir
        const searchInput = document.querySelector('[data-instant-search]');
        if (searchInput) {
            const resultsId = searchInput.getAttribute('data-results');
            const searchUrl = searchInput.getAttribute('data-url') || 'buscar.php';
            new InstantSearch(`[data-instant-search]`, `#${resultsId}`, searchUrl);
        }

        // Substitui alertas padrão por toasts
        window.showMessage = (message, type = 'info') => {
            window.GED.Toast.show(message, type);
        };

        // Melhora confirmações de exclusão
        document.querySelectorAll('[data-confirm]').forEach(element => {
            element.addEventListener('click', async (e) => {
                e.preventDefault();
                const message = element.getAttribute('data-confirm');
                const confirmed = await ConfirmModal.show({
                    message: message,
                    type: 'warning',
                    confirmText: 'Sim, excluir',
                    cancelText: 'Cancelar'
                });
                
                if (confirmed) {
                    if (element.tagName === 'A') {
                        window.location.href = element.href;
                    } else if (element.tagName === 'BUTTON' && element.form) {
                        element.form.submit();
                    }
                }
            });
        });

        // Auto-submissão de formulários AJAX
        document.querySelectorAll('form[data-ajax]').forEach(form => {
            form.addEventListener('submit', async (e) => {
                e.preventDefault();
                const url = form.getAttribute('action') || form.getAttribute('data-ajax');
                await FormUtils.submit(form, url, {
                    onSuccess: (result) => {
                        if (result.redirect) {
                            setTimeout(() => window.location.href = result.redirect, 1000);
                        }
                    }
                });
            });
        });
    });
})();
