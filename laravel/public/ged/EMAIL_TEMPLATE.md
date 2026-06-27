# 🎨 Template de E-mail ENFAS GED - Estilo eDok

## ✨ Características do Novo Design

### Visual
- **Gradient Azul:** Header com gradiente moderno (#2563eb → #3b82f6)
- **Logo Centralizada:** Logo ENFAS GED em branco no topo
- **Background Escuro:** Fundo gradiente escuro para destacar o card branco
- **Bordas Arredondadas:** Card principal com border-radius de 16px
- **Sombras Profundas:** Box-shadow para dar profundidade
- **Tipografia Moderna:** Fonte Inter do Google Fonts

### Estrutura
```
┌─────────────────────────────────────┐
│  Fundo Gradiente Escuro (#1d3441)   │
│  ┌───────────────────────────────┐  │
│  │ Header Azul Gradiente         │  │
│  │ [Logo ENFAS GED em Branco]    │  │
│  ├───────────────────────────────┤  │
│  │ Título do E-mail (H1)         │  │
│  │                               │  │
│  │ Conteúdo Principal            │  │
│  │ (Com formatação nl2br)        │  │
│  │                               │  │
│  │ [Botão de Ação - Opcional]    │  │
│  ├───────────────────────────────┤  │
│  │ Rodapé com Links              │  │
│  │ • Acessar Sistema             │  │
│  │ • Política de Privacidade     │  │
│  │ • Suporte                     │  │
│  └───────────────────────────────┘  │
└─────────────────────────────────────┘
```

## 🎨 Paleta de Cores

| Cor | Hex | Uso |
|-----|-----|-----|
| Azul Primary | `#2563eb` | Gradient header, botões |
| Azul Accent | `#3b82f6` | Gradient header (fim) |
| Azul Dark | `#1d4ed8` | Borda header, hover botões |
| Cinza Escuro BG | `#1d3441` | Background wrapper (início) |
| Cinza Escuro BG 2 | `#2b3f4c` | Background wrapper (fim) |
| Branco | `#ffffff` | Card principal |
| Cinza Claro BG | `#f8fafc` | Rodapé |
| Texto Principal | `#0f172a` | Títulos |
| Texto Secundário | `#475569` | Corpo do texto |
| Texto Rodapé | `#64748b` | Texto footer |

## 📝 Como Usar nos Templates

### Template Simples (Texto)
Os templates cadastrados no banco continuam funcionando normalmente. O sistema aplica **automaticamente** o novo design quando o template é apenas texto:

```sql
-- Exemplo: Template de alerta de vencimento
INSERT INTO email_templates (slug, nome, assunto, corpo) VALUES
('alerta_vencimento', 'Alerta de Vencimento', 
 'Documento {{documento.titulo}} vence em {{dias}} dia(s)',
 'Olá {{nome}},

O documento "{{documento.titulo}}" vence em {{dias}} dia(s).
Data de vencimento: {{documento.vencimento}}.

Acesse o documento: {{documento.link}}');
```

**Resultado:** O sistema pega esse texto simples e aplica automaticamente:
- Header azul gradiente com logo
- Card branco com padding
- Tipografia Inter
- Rodapé com links
- Responsividade mobile

### Template com Botão de Ação
Para adicionar um botão estilizado, basta incluir um link no corpo:

```
Olá {{nome}},

Para redefinir sua senha, clique no botão abaixo:

<a href="{{link}}" style="display:inline-block;background:linear-gradient(135deg,#2563eb 0%,#3b82f6 100%);color:#fff!important;text-decoration:none;padding:14px 32px;border-radius:8px;font-weight:600;font-size:15px;margin:24px 0;box-shadow:0 4px 12px rgba(37,99,235,0.3)">Redefinir Senha</a>

Este link expira em {{expiracao}}.
```

### Template HTML Completo (Avançado)
Se você precisar de um layout totalmente customizado, pode criar HTML completo começando com `<!doctype html>` ou `<html`. Nesse caso, o sistema **não aplica** o template padrão.

## 🔧 Personalização

### Logo
Edite em `core/email.php` (linha ~135):
```php
$logoUrl = 'https://ged.enfas.com.br/assets/dist/img/logo_enfasged.svg';
```

### Nome da Marca
Defina no `config.php`:
```php
define('BRAND_NAME', 'ENFAS GED');
```

### Cores do Gradient
Edite o CSS inline em `core/email.php`:
```php
// Header gradient
background: linear-gradient(135deg, #2563eb 0%, #3b82f6 100%);

// Background wrapper
background: linear-gradient(135deg, #1d3441 0%, #2b3f4c 100%);
```

### Links do Rodapé
Edite em `core/email.php` (linhas ~200-210):
```html
<a href="https://ged.enfas.com.br">Acessar Sistema</a> · 
<a href="https://ged.enfas.com.br/politica-privacidade">Política de Privacidade</a> · 
<a href="mailto:suporte@enfas.com.br">Suporte</a>
```

## 📱 Responsividade

O template se adapta automaticamente a diferentes tamanhos de tela:

### Desktop (> 600px)
- Container: 600px de largura
- Logo: 180px
- Padding: 36px/28px
- Font-size título: 22px

### Mobile (≤ 600px)
- Container: 100% da largura
- Logo: 150px
- Padding: 28px/20px
- Font-size título: 20px

## 🧪 Testar o Novo Template

### 1. Pré-visualização Web
Acesse: **http://localhost/ged/public/email_preview.php**

Mostra todos os templates renderizados com o novo design.

### 2. Enviar E-mail de Teste
1. Vá em: **Administração → Templates de E-mail**
2. Clique no ícone **📧** (Testar) ao lado do template
3. Digite seu e-mail e envie

### 3. Verificar no Banco
```sql
-- Ver último e-mail enviado
SELECT * FROM emails_log ORDER BY created_at DESC LIMIT 1;
```

## ✅ Templates Atualizados Automaticamente

Todos esses templates já usam o novo design:
- ✅ `recuperar_senha` - Recuperação de senha
- ✅ `usuario_criado` - Boas-vindas
- ✅ `alerta_vencimento` - Alerta de vencimento
- ✅ `compartilhar_link` - Compartilhamento
- ✅ `senha_alterada` - Confirmação de troca
- ✅ `notificacao_upload` - Novo documento
- ✅ `documento_assinado` - Assinatura concluída
- ✅ `lembrete_assinatura` - Lembrete pendência
- ✅ `convite_usuario` - Convite para sistema

## 🚀 Próximos Passos

1. **Teste todos os templates** via `email_preview.php`
2. **Envie e-mails de teste** para validar em diferentes clientes (Gmail, Outlook, etc.)
3. **Ajuste cores/logo** se necessário
4. **Monitore feedback** dos usuários

## 📚 Referências

- **Fonte Inter:** https://fonts.google.com/specimen/Inter
- **Gradientes:** https://uigradients.com/
- **Email HTML:** https://www.campaignmonitor.com/css/
- **Template eDok:** Inspiração no design moderno e clean

---

**Desenvolvido para ENFAS GED** | Última atualização: 8 de novembro de 2025
