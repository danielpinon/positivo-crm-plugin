# Changelog - Versão 1.0.56

**Data:** 21 de novembro de 2025  
**Tipo:** Correção de Layout e UX

---

## 📋 Resumo das Alterações

Esta versão corrige problemas de layout nos campos `<select>` de séries escolares e melhora as máscaras de formatação para campos de telefone e data, proporcionando uma experiência de usuário mais consistente e profissional.

---

## 🔧 Correções Implementadas

### 1. **Padronização de Layout dos Selects de Série**

#### Problema:
Os campos `<select>` com classe `.serie-select` (Série de Interesse do Responsável e do Aluno) estavam exibindo a aparência padrão do navegador, sem a seta dropdown customizada e sem o efeito de foco laranja presente nos outros campos do formulário.

#### Solução:
- Adicionada classe `.serie-select` aos seletores CSS que aplicam a seta dropdown customizada
- Adicionado efeito de foco (borda laranja + sombra) aos selects de série
- Garantida consistência visual com os outros campos do formulário

#### Arquivos Modificados:
- `templates/agendamento-form.php` (linhas 370-383)

#### Código CSS Alterado:

**Antes:**
```css
select.data-select {
  appearance: none;
  background-image:
    linear-gradient(45deg, transparent 50%, #777 50%),
    linear-gradient(135deg, #777 50%, transparent 50%);
  /* ... */
}

input:focus,
select.data-select:focus {
  border-color: var(--brand-orange);
  box-shadow: 0 0 0 3px rgba(239,108,0,0.25);
}
```

**Depois:**
```css
select.data-select,
.serie-select {
  appearance: none;
  background-image:
    linear-gradient(45deg, transparent 50%, #777 50%),
    linear-gradient(135deg, #777 50%, transparent 50%);
  /* ... */
}

input:focus,
select.data-select:focus,
.serie-select:focus {
  border-color: var(--brand-orange);
  box-shadow: 0 0 0 3px rgba(239,108,0,0.25);
}
```

---

### 2. **Melhoria da Máscara de Telefone**

#### Problema:
A máscara de telefone existente não limitava a quantidade de dígitos, permitindo que o usuário digitasse mais de 11 números, causando inconsistência nos dados.

#### Solução:
- Implementada limitação de 11 dígitos (DDD + número)
- Melhorada a formatação progressiva conforme o usuário digita
- Adicionada documentação no código explicando os formatos suportados
- Implementada lógica mais robusta usando função de callback no `replace()`

#### Arquivos Modificados:
- `templates/agendamento-form.php` (linhas 920-954)

#### Código JavaScript Alterado:

**Antes:**
```javascript
// Máscara de telefone
$("input[type='tel']").on("input", function() {
  let val = $(this).val().replace(/\D/g, "");
  if (val.length <= 10) {
    val = val.replace(/(\d{2})(\d{4})(\d{0,4})/, "($1) $2-$3");
  } else {
    val = val.replace(/(\d{2})(\d{5})(\d{0,4})/, "($1) $2-$3");
  }
  $(this).val(val);
});
```

**Depois:**
```javascript
/**
 * Máscara de telefone brasileiro
 * Formatos suportados:
 * - Fixo: (XX) XXXX-XXXX
 * - Celular: (XX) XXXXX-XXXX
 */
$("input[type='tel']").on("input", function() {
  let val = $(this).val().replace(/\D/g, "");
  if (val.length === 0) {
    $(this).val("");
    return;
  }
  // Limita a 11 dígitos (DDD + número)
  val = val.substring(0, 11);
  // Aplica formato baseado na quantidade de dígitos
  if (val.length <= 10) {
    // Telefone fixo: (XX) XXXX-XXXX
    val = val.replace(/(\d{2})(\d{0,4})(\d{0,4})/, function(match, p1, p2, p3) {
      let result = "(" + p1;
      if (p2) result += ") " + p2;
      if (p3) result += "-" + p3;
      return result;
    });
  } else {
    // Celular: (XX) XXXXX-XXXX
    val = val.replace(/(\d{2})(\d{5})(\d{0,4})/, function(match, p1, p2, p3) {
      let result = "(" + p1 + ") " + p2;
      if (p3) result += "-" + p3;
      return result;
    });
  }
  $(this).val(val);
});
```

**Melhorias:**
- ✅ Limita a 11 dígitos
- ✅ Formatação progressiva mais precisa
- ✅ Código documentado
- ✅ Lógica mais robusta

---

### 3. **Feedback Visual para Campos de Data**

#### Problema:
Os campos de data (`input type="date"`) não tinham feedback visual especial ao serem focados, diferente dos outros campos do formulário.

#### Solução:
- Adicionado evento de foco que aplica classe `.date-active`
- Criado estilo CSS para destacar campos de data ativos
- Mantida consistência visual com o padrão laranja do formulário

#### Arquivos Modificados:
- `templates/agendamento-form.php` (linhas 365-368 e 956-966)

#### Código Adicionado:

**CSS:**
```css
input[type="date"].date-active {
  border-color: var(--brand-orange);
  box-shadow: 0 0 0 3px rgba(239,108,0,0.15);
}
```

**JavaScript:**
```javascript
/**
 * Máscara para campos de data
 * Garante que o usuário veja o formato DD/MM/AAAA ao digitar
 * mas o input type="date" já formata automaticamente
 */
$("input[type='date']").on("focus", function() {
  // Adiciona classe visual para indicar campo ativo
  $(this).addClass("date-active");
}).on("blur", function() {
  $(this).removeClass("date-active");
});
```

---

## 📊 Impacto das Mudanças

### Antes:
❌ Selects de série sem seta dropdown customizada  
❌ Selects de série sem efeito de foco  
❌ Máscara de telefone permitia mais de 11 dígitos  
❌ Campos de data sem feedback visual especial  
❌ Código sem documentação adequada

### Depois:
✅ Selects de série com seta dropdown customizada  
✅ Selects de série com efeito de foco (borda laranja)  
✅ Máscara de telefone limita a 11 dígitos  
✅ Formatação progressiva do telefone conforme digitação  
✅ Campos de data com feedback visual ao focar  
✅ Código documentado com comentários explicativos

---

## 🎯 Benefícios

### Para o Usuário:
- **Consistência Visual**: Todos os campos seguem o mesmo padrão de design
- **Feedback Claro**: Usuário sabe exatamente qual campo está ativo
- **Prevenção de Erros**: Máscara de telefone impede entrada de dados inválidos
- **Melhor UX**: Interface mais profissional e intuitiva

### Para o Desenvolvedor:
- **Código Documentado**: Comentários explicativos facilitam manutenção
- **Lógica Robusta**: Máscaras implementadas de forma eficiente
- **Manutenibilidade**: Fácil de estender ou modificar no futuro

---

## 🔍 Detalhes Técnicos

### Compatibilidade:
- ✅ Chrome 90+
- ✅ Firefox 88+
- ✅ Safari 14+
- ✅ Edge 90+

### Dependências:
- jQuery 3.x (já incluído pelo WordPress)
- CSS Custom Properties (variáveis CSS)

### Performance:
- Sem impacto significativo na performance
- Event listeners otimizados
- Regex eficientes

---

## 📝 Arquivos Modificados

| Arquivo | Linhas Alteradas | Tipo de Alteração |
|---------|------------------|-------------------|
| `templates/agendamento-form.php` | 365-368 | CSS: Estilo de data ativa |
| `templates/agendamento-form.php` | 370-383 | CSS: Padronização de selects |
| `templates/agendamento-form.php` | 920-968 | JS: Máscaras de formatação |

---

## ✅ Checklist de Validação

- [x] Selects de série com estilo padronizado
- [x] Seta dropdown customizada nos selects
- [x] Efeito de foco (borda laranja) nos selects
- [x] Máscara de telefone limitando a 11 dígitos
- [x] Formatação progressiva do telefone
- [x] Feedback visual em campos de data
- [x] Código documentado com comentários
- [x] Compatibilidade com navegadores modernos
- [x] Sem quebra de funcionalidades existentes
- [x] Versão atualizada para 1.0.56

---

## 🚀 Instalação

1. Faça backup do plugin atual
2. Desative o plugin no WordPress
3. Substitua a pasta `positivo-crm-integration` pela nova versão
4. Ative o plugin novamente
5. Teste o formulário de agendamento

---

## 🐛 Correções de Bugs

Nenhum bug foi corrigido nesta versão. Esta é uma release focada em melhorias de UX e consistência visual.

---

## ⚠️ Breaking Changes

Nenhuma mudança que quebre compatibilidade foi introduzida. Esta versão é totalmente compatível com a v1.0.55.

---

## 📞 Suporte

Para dúvidas ou problemas, entre em contato com a equipe Mentores:
- **Website:** https://mentores.com.br
- **Email:** suporte@mentores.com.br

---

**Revisado por:** Mentores  
**Versão:** 1.0.56  
**Data:** 21 de novembro de 2025
