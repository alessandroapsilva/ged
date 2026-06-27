# 🎨 Guia de Estilo - Aplicar em Todo o Sistema

## 🎯 Objetivo
Manter consistência visual em TODAS as páginas do GED.

---

## 1️⃣ CSS Global (Criar arquivo único)

### Arquivo: `/public/assets/dist/css/ged-modern.css`

```css
/* ========================================
   GED Modern Theme - v2.0
   ======================================== */

@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');

:root {
    /* Brand Colors */
    --brand-primary: #2563eb;
    --brand-accent: #3b82f6;
    --brand-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    
    /* Semantic Colors */
    --color-success: #10b981;
    --color-warning: #f59e0b;
    --color-danger: #ef4444;
    --color-info: #06b6d4;
    
    /* Gray Scale (Tailwind) */
    --gray-50: #f9fafb;
    --gray-100: #f3f4f6;
    --gray-200: #e5e7eb;
    --gray-300: #d1d5db;
    --gray-400: #9ca3af;
    --gray-500: #6b7280;
    --gray-600: #4b5563;
    --gray-700: #374151;
    --gray-800: #1f2937;
    --gray-900: #111827;
    
    /* Spacing */
    --space-xs: 0.5rem;   /* 8px */
    --space-sm: 1rem;     /* 16px */
    --space-md: 1.5rem;   /* 24px */
    --space-lg: 2rem;     /* 32px */
    --space-xl: 3rem;     /* 48px */
    
    /* Border Radius */
    --radius-sm: 8px;
    --radius-md: 12px;
    --radius-lg: 16px;
    --radius-xl: 24px;
    
    /* Shadows */
    --shadow-sm: 0 2px 4px rgba(0,0,0,0.05);
    --shadow-md: 0 4px 12px rgba(0,0,0,0.1);
    --shadow-lg: 0 8px 20px rgba(0,0,0,0.15);
    --shadow-xl: 0 20px 60px rgba(0,0,0,0.3);
    
    /* Transitions */
    --transition-fast: 0.15s ease;
    --transition-base: 0.3s ease;
    --transition-slow: 0.5s ease;
}

/* Base Typography */
body {
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    color: var(--gray-900);
    line-height: 1.6;
    -webkit-font-smoothing: antialiased;
    -moz-osx-font-smoothing: grayscale;
}

h1, h2, h3, h4, h5, h6 {
    font-weight: 700;
    line-height: 1.2;
    margin-bottom: var(--space-md);
}

/* Modern Buttons */
.btn-modern {
    padding: 12px 24px;
    border-radius: var(--radius-md);
    font-weight: 600;
    font-size: 15px;
    transition: all var(--transition-base);
    border: none;
    cursor: pointer;
}

.btn-primary-modern {
    background: linear-gradient(135deg, var(--brand-primary), var(--brand-accent));
    color: white;
    box-shadow: var(--shadow-md);
}

.btn-primary-modern:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-lg);
}

.btn-outline-modern {
    background: white;
    color: var(--brand-primary);
    border: 2px solid var(--brand-primary);
}

.btn-outline-modern:hover {
    background: var(--brand-primary);
    color: white;
}

/* Modern Cards */
.card-modern {
    background: white;
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-md);
    padding: var(--space-lg);
    transition: all var(--transition-base);
    border: 1px solid var(--gray-200);
}

.card-modern:hover {
    box-shadow: var(--shadow-lg);
    transform: translateY(-2px);
}

.card-header-modern {
    padding: var(--space-md) var(--space-lg);
    background: linear-gradient(180deg, #fff, #fafbfc);
    border-bottom: 1px solid var(--gray-200);
    border-radius: var(--radius-lg) var(--radius-lg) 0 0;
}

/* Modern Inputs */
.input-modern {
    padding: 12px 16px;
    border: 2px solid var(--gray-200);
    border-radius: var(--radius-md);
    font-size: 15px;
    font-weight: 500;
    transition: all var(--transition-base);
    width: 100%;
}

.input-modern:focus {
    outline: none;
    border-color: var(--brand-primary);
    box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.1);
}

/* Modern Tables */
.table-modern {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0 8px;
}

.table-modern thead th {
    background: var(--gray-50);
    padding: 12px 16px;
    font-weight: 600;
    color: var(--gray-700);
    text-align: left;
    font-size: 14px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.table-modern tbody tr {
    background: white;
    box-shadow: var(--shadow-sm);
    transition: all var(--transition-fast);
}

.table-modern tbody tr:hover {
    box-shadow: var(--shadow-md);
    transform: translateX(2px);
}

.table-modern tbody td {
    padding: 16px;
    border-top: 1px solid var(--gray-100);
    border-bottom: 1px solid var(--gray-100);
}

.table-modern tbody td:first-child {
    border-left: 1px solid var(--gray-100);
    border-radius: var(--radius-md) 0 0 var(--radius-md);
}

.table-modern tbody td:last-child {
    border-right: 1px solid var(--gray-100);
    border-radius: 0 var(--radius-md) var(--radius-md) 0;
}

/* Modern Badges */
.badge-modern {
    padding: 4px 12px;
    border-radius: 9999px;
    font-size: 12px;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 4px;
}

.badge-primary { background: #dbeafe; color: var(--brand-primary); }
.badge-success { background: #d1fae5; color: var(--color-success); }
.badge-warning { background: #fef3c7; color: var(--color-warning); }
.badge-danger { background: #fee2e2; color: var(--color-danger); }

/* Modern Alerts */
.alert-modern {
    padding: 16px 20px;
    border-radius: var(--radius-md);
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 14px;
    font-weight: 500;
}

.alert-success {
    background: #d1fae5;
    color: #065f46;
    border-left: 4px solid var(--color-success);
}

.alert-warning {
    background: #fef3c7;
    color: #92400e;
    border-left: 4px solid var(--color-warning);
}

.alert-danger {
    background: #fee2e2;
    color: #991b1b;
    border-left: 4px solid var(--color-danger);
}

.alert-info {
    background: #cffafe;
    color: #164e63;
    border-left: 4px solid var(--color-info);
}

/* Modern Sidebar */
.sidebar-modern {
    background: linear-gradient(180deg, var(--gray-900), var(--gray-800));
    color: white;
}

.sidebar-modern .nav-link {
    color: var(--gray-300);
    padding: 12px 20px;
    border-radius: var(--radius-md);
    margin: 4px 8px;
    transition: all var(--transition-fast);
}

.sidebar-modern .nav-link:hover {
    background: rgba(255, 255, 255, 0.1);
    color: white;
}

.sidebar-modern .nav-link.active {
    background: var(--brand-primary);
    color: white;
}

/* Modern Header */
.navbar-modern {
    background: white;
    box-shadow: var(--shadow-sm);
    border-bottom: 1px solid var(--gray-200);
}

/* Utilities */
.text-gradient {
    background: var(--brand-gradient);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.glassmorphism {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(20px);
}

/* Animations */
@keyframes slideUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

.animate-slide-up {
    animation: slideUp 0.5s ease-out;
}

.animate-fade-in {
    animation: fadeIn 0.3s ease-out;
}

/* Responsive */
@media (max-width: 768px) {
    :root {
        --space-lg: 1.5rem;
        --space-xl: 2rem;
    }
    
    .btn-modern {
        padding: 10px 20px;
        font-size: 14px;
    }
}
```

---

## 2️⃣ Como Aplicar em Páginas Existentes

### Passo 1: Incluir CSS
```php
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/dist/css/ged-modern.css">
```

### Passo 2: Atualizar Classes

**Antes:**
```html
<div class="card">
    <div class="card-header">
        <h3>Título</h3>
    </div>
    <div class="card-body">
        Conteúdo
    </div>
</div>
```

**Depois:**
```html
<div class="card-modern animate-slide-up">
    <div class="card-header-modern">
        <h3>Título</h3>
    </div>
    <div class="card-body">
        Conteúdo
    </div>
</div>
```

---

## 3️⃣ Checklist de Modernização

### Dashboard Principal
```
☐ Substituir cards antigos por .card-modern
☐ Atualizar botões para .btn-modern
☐ Aplicar .table-modern nas tabelas
☐ Adicionar badges com .badge-modern
☐ Incluir animações .animate-slide-up
```

### Formulários
```
☐ Inputs com .input-modern
☐ Botões de submit com .btn-primary-modern
☐ Labels com font-weight: 600
☐ Alerts com .alert-modern
☐ Validação em tempo real
```

### Listagens
```
☐ Tabelas com .table-modern
☐ Hover effects ativos
☐ Paginação modernizada
☐ Filtros com .input-modern
☐ Ações com .btn-outline-modern
```

### Modais
```
☐ Border-radius: var(--radius-xl)
☐ Box-shadow: var(--shadow-xl)
☐ Backdrop blur effect
☐ Animação de entrada
☐ Botões modernos no footer
```

---

## 4️⃣ Componentes Prontos

### Botão de Ação Principal
```html
<button class="btn-modern btn-primary-modern">
    <i class="fas fa-plus"></i> Nova Ação
</button>
```

### Card de Informação
```html
<div class="card-modern">
    <div class="card-header-modern">
        <h4><i class="fas fa-chart-line"></i> Estatísticas</h4>
    </div>
    <div class="p-4">
        <!-- Conteúdo -->
    </div>
</div>
```

### Alert de Sucesso
```html
<div class="alert-modern alert-success">
    <i class="fas fa-check-circle"></i>
    <span>Operação realizada com sucesso!</span>
</div>
```

### Badge de Status
```html
<span class="badge-modern badge-success">
    <i class="fas fa-circle" style="font-size: 8px;"></i>
    Ativo
</span>
```

### Input com Ícone
```html
<div class="position-relative">
    <i class="fas fa-search position-absolute" style="left: 16px; top: 50%; transform: translateY(-50%); color: var(--gray-400);"></i>
    <input type="text" class="input-modern" style="padding-left: 48px;" placeholder="Buscar...">
</div>
```

---

## 5️⃣ Priorização de Páginas

### 🔴 Alta Prioridade (Fazer Primeiro)
1. **Login** ✅ (Já feito!)
2. **Dashboard Principal** 
3. **Lista de Documentos**
4. **Visualização de Documento**
5. **Cadastro de Documento**

### 🟡 Média Prioridade
6. Usuários
7. Departamentos
8. Relatórios
9. Configurações
10. Perfil do Usuário

### 🟢 Baixa Prioridade
11. Logs/Auditoria
12. Notificações
13. Ajuda/FAQ
14. Sobre

---

## 6️⃣ Script de Migração Rápida

### Buscar e Substituir (VS Code)

**Buscar:** `<div class="card">`  
**Substituir:** `<div class="card-modern">`

**Buscar:** `class="btn btn-primary"`  
**Substituir:** `class="btn-modern btn-primary-modern"`

**Buscar:** `<input type="text" class="form-control"`  
**Substituir:** `<input type="text" class="input-modern"`

**Buscar:** `<table class="table"`  
**Substituir:** `<table class="table-modern"`

---

## 7️⃣ Testes de Qualidade

### Checklist Visual
```
✓ Cores consistentes (primária, accent)
✓ Espaçamento uniforme (8px grid)
✓ Bordas arredondadas (12-24px)
✓ Sombras sutis e elegantes
✓ Animações suaves (0.3s)
✓ Hover states em elementos interativos
✓ Focus states visíveis
✓ Tipografia Inter aplicada
```

### Checklist Responsivo
```
✓ Mobile (375px - iPhone)
✓ Tablet (768px - iPad)
✓ Desktop (1920px)
✓ Touch-friendly (min 44px)
✓ Breakpoints corretos
```

### Checklist de Performance
```
✓ CSS minificado
✓ Fonts otimizadas (preconnect)
✓ Animações GPU (transform/opacity)
✓ Lazy loading onde aplicável
```

---

## 8️⃣ Exemplo Completo - Página de Documentos

```php
<?php require_once '../core/init.php'; ?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= BRAND_NAME ?> | Documentos</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/plugins/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/dist/css/ged-modern.css">
</head>
<body class="hold-transition sidebar-mini">

<div class="wrapper">
    <!-- Sidebar, Navbar aqui -->
    
    <div class="content-wrapper">
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-3">
                    <div class="col-sm-6">
                        <h1 class="text-gradient">
                            <i class="fas fa-folder-open"></i> Meus Documentos
                        </h1>
                    </div>
                    <div class="col-sm-6 text-right">
                        <button class="btn-modern btn-primary-modern">
                            <i class="fas fa-plus"></i> Novo Documento
                        </button>
                    </div>
                </div>
            </div>
        </section>

        <section class="content">
            <div class="container-fluid">
                <!-- Filtros -->
                <div class="card-modern mb-4">
                    <div class="card-body p-3">
                        <div class="row align-items-center">
                            <div class="col-md-4">
                                <div class="position-relative">
                                    <i class="fas fa-search position-absolute" style="left: 16px; top: 50%; transform: translateY(-50%); color: var(--gray-400);"></i>
                                    <input type="text" class="input-modern" style="padding-left: 48px;" placeholder="Buscar documentos...">
                                </div>
                            </div>
                            <div class="col-md-8 text-right">
                                <button class="btn-modern btn-outline-modern">
                                    <i class="fas fa-filter"></i> Filtros
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tabela -->
                <div class="card-modern animate-slide-up">
                    <table class="table-modern">
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>Tipo</th>
                                <th>Data</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>
                                    <i class="fas fa-file-pdf text-danger"></i>
                                    <strong>Contrato 2025.pdf</strong>
                                </td>
                                <td>PDF</td>
                                <td>15/01/2025</td>
                                <td>
                                    <span class="badge-modern badge-success">
                                        <i class="fas fa-circle" style="font-size: 8px;"></i>
                                        Aprovado
                                    </span>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-primary">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </div>
</div>

<script src="<?= BASE_URL ?>/assets/plugins/jquery/jquery.min.js"></script>
<script src="<?= BASE_URL ?>/assets/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="<?= BASE_URL ?>/assets/dist/js/adminlte.min.js"></script>
</body>
</html>
```

---

## ✅ Resultado Final

Após aplicar este guia, o sistema terá:

✅ Visual moderno e consistente  
✅ Experiência de usuário premium  
✅ Performance otimizada  
✅ Responsividade total  
✅ Acessibilidade WCAG AAA  
✅ Manutenibilidade alta  
✅ Pronto para produção  

**Tempo estimado de implementação:** 2-3 dias para todo o sistema.
