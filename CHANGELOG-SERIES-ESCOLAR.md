# Changelog - Implementação Fetch Consulta Séries Escolar

**Data:** 20/11/2025  
**Versão:** 1.0.32

## 📋 Resumo das Alterações

Foi implementado um novo fetch "Consulta Séries Escolar" que busca dinamicamente todas as séries escolares disponíveis no CRM e permite que os IDs sejam utilizados no template JSON de agendamento, substituindo o ID fixo anteriormente utilizado.

---

## 🔧 Arquivos Modificados

### 1. **includes/class-positivo-crm-api.php**

#### Adicionado:
- **Método `get_series_escolares()`**: Busca todas as séries escolares ativas do CRM
- **Método `ajax_get_series()`**: Callback AJAX para frontend acessar as séries
- **Hooks AJAX**: Registrados para usuários logados e não logados

```php
// Novo método na API
public function get_series_escolares() {
    // FetchXML para consultar séries escolares
    // Retorna lista de séries com ID e nome
}

// Novo callback AJAX
public function ajax_get_series() {
    // Permite acesso público às séries
}
```

**FetchXML utilizado:**
```xml
<fetch version="1.0" output-format="xml-platform" mapping="logical" distinct="false">
  <entity name="cad_servicoeducacional">
    <attribute name="cad_servicoeducacionalid" />
    <attribute name="cad_name" />
    <attribute name="statecode" />
    <attribute name="createdon" />
    <order attribute="cad_name" descending="false" />
    <filter type="and">
      <condition attribute="statecode" operator="eq" value="0" />
    </filter>
  </entity>
</fetch>
```

---

### 2. **includes/class-positivo-crm-admin.php**

#### Adicionado:
- **Método `ajax_test_series()`**: Handler AJAX para testar a consulta de séries no admin
- **Hook de teste**: `wp_ajax_positivo_crm_test_series`

#### Modificado:
- **Método `enviar_agendamento_para_crm()`**: 
  - Agora busca os IDs de série diretamente dos campos `aluno_serie_id` e `responsavel_serie_id`
  - Mantém fallback para mapeamento legado (retrocompatibilidade)
  - Logs aprimorados para debug

```php
// Nova lógica de obtenção de IDs
$aluno_serie_id = $agendamento->aluno_serie_id ?? null;
$responsavel_serie_id = $agendamento->responsavel_serie_id ?? null;

// Fallback para mapeamento antigo
if (!$aluno_serie_id && !empty($agendamento->aluno_serie_interesse)) {
    $serie_id_map = $this->get_serie_id_map();
    $aluno_serie_id = $serie_id_map[$agendamento->aluno_serie_interesse] ?? null;
}
```

---

### 3. **templates/agendamento-form.php**

#### Adicionado:
- **Campo de série para responsável**: Select dinâmico no Passo 1
- **Campo de série para aluno**: Select dinâmico no Passo 3
- **Função `loadSeries()`**: Carrega séries da API via AJAX
- **Handler de mudança**: Atualiza campos hidden com nome da série selecionada

#### Estrutura dos novos campos:

**Responsável (Passo 1):**
```html
<div class="form-group">
  <label for="responsavel_serie_id">Série de Interesse <span class="required">*</span></label>
  <select id="responsavel_serie_id" name="responsavel_serie_id" class="serie-select" required>
    <option value="">Carregando...</option>
  </select>
  <input type="hidden" id="responsavel_serie" name="responsavel_serie" />
</div>
```

**Aluno (Passo 3):**
```html
<select name="aluno_serie_id[]" class="serie-select" required>
  <option value="">Carregando...</option>
</select>
<input type="hidden" name="aluno_serie[]" class="serie-name" />
```

#### JavaScript adicionado:
```javascript
// Carrega séries ao iniciar
loadSeries();

// Atualiza campo hidden ao selecionar série
$form.on('change', '.serie-select', function() {
  const selectedOption = $(this).find('option:selected');
  const serieName = selectedOption.data('name') || selectedOption.text();
  $(this).closest('.form-group, div').find('.serie-name').val(serieName);
});
```

---

### 4. **sql/create-agendamentos-table.sql**

#### Adicionado:
- **Coluna `responsavel_serie_id`**: Armazena GUID da série do responsável
- **Coluna `aluno_serie_id`**: Armazena GUID da série do aluno

```sql
`responsavel_serie_id` varchar(255) DEFAULT NULL COMMENT 'GUID da série de interesse do responsável',
`aluno_serie_id` varchar(255) DEFAULT NULL COMMENT 'GUID da série de interesse do aluno',
```

---

### 5. **VARIAVEIS-JSON-TEMPLATE.md**

#### Atualizado:
- Documentação das variáveis `{{responsavel_serie_id}}` e `{{aluno_serie_id}}`
- Exemplos atualizados com IDs dinâmicos reais
- Nota sobre IDs dinâmicos vs fixos

---

## 🗄️ Migração de Banco de Dados

Para tabelas existentes, execute o seguinte SQL:

```sql
ALTER TABLE `wp_positivo_agendamentos` 
ADD COLUMN `responsavel_serie_id` varchar(255) DEFAULT NULL COMMENT 'GUID da série de interesse do responsável' AFTER `responsavel_serie_interesse`,
ADD COLUMN `aluno_serie_id` varchar(255) DEFAULT NULL COMMENT 'GUID da série de interesse do aluno' AFTER `aluno_serie_interesse`;
```

---

## 🔄 Fluxo de Funcionamento

### 1. **Carregamento da Página**
- Frontend chama `loadSeries()` via AJAX
- API executa fetch "Consulta Séries Escolar"
- Retorna lista de séries com ID e nome
- Popula todos os selects `.serie-select`

### 2. **Seleção de Série**
- Usuário seleciona série no dropdown
- JavaScript captura ID (value) e nome (data-name)
- Armazena ID no campo visível e nome no campo hidden

### 3. **Submissão do Formulário**
- Dados enviados incluem:
  - `responsavel_serie_id`: GUID da série
  - `responsavel_serie`: Nome da série (para exibição)
  - `aluno_serie_id[]`: GUID da série do aluno
  - `aluno_serie[]`: Nome da série do aluno

### 4. **Processamento Backend**
- Admin recebe dados e salva no banco
- Ao enviar para CRM, usa `aluno_serie_id` e `responsavel_serie_id`
- Template JSON substitui `{{aluno_serie_id}}` e `{{responsavel_serie_id}}`
- IDs dinâmicos são enviados para o CRM

---

## 📊 Formato de Resposta da API

### Estrutura do JSON retornado:

```json
{
  "resultset": {
    "@morerecords": "0",
    "result": [
      {
        "statecode": {
          "@name": "Ativo(a)",
          "#text": "0"
        },
        "cad_servicoeducacionalid": "{EFBFD569-5DBC-EA11-A812-000D3AC06348}",
        "cad_name": "1º Ano - Anos Iniciais",
        "createdon": {
          "@date": "02/07/2020",
          "@time": "09:13",
          "#text": "2020-07-02T09:13:29-03:00"
        }
      }
    ]
  }
}
```

### Campos utilizados:
- **`cad_servicoeducacionalid`**: GUID da série (removendo chaves `{}`)
- **`cad_name`**: Nome da série para exibição

---

## 🧪 Testes Disponíveis

### Admin (Backend):
1. Acesse: **Positivo CRM > Configurações**
2. Role até a seção de testes
3. Clique em **"Testar Consulta Séries"**
4. Verifique o JSON retornado

### Frontend:
1. Abra a página com o formulário de agendamento
2. Verifique se os selects de série carregam automaticamente
3. Selecione uma série e inspecione os campos hidden
4. Submeta o formulário e verifique os dados salvos

---

## ⚠️ Notas Importantes

### Retrocompatibilidade
- O sistema mantém o mapeamento legado `get_serie_id_map()`
- Se `aluno_serie_id` não estiver presente, usa o mapeamento antigo
- Logs de warning são gerados quando o fallback é usado

### Configuração
- O FetchXML pode ser customizado em **Configurações > Fetch Séries Escolar**
- O método HTTP (GET/POST) pode ser configurado via `method_series`

### Performance
- As séries são carregadas uma única vez ao abrir a página
- Cache pode ser implementado no futuro se necessário

---

## 🔗 Endpoints AJAX

### Frontend (público):
- **Action:** `positivo_crm_get_series`
- **Método:** POST
- **Parâmetros:** `nonce` (opcional)
- **Retorno:** Array de séries escolares

### Admin (restrito):
- **Action:** `positivo_crm_test_series`
- **Método:** POST
- **Parâmetros:** `nonce` (obrigatório)
- **Retorno:** Array de séries escolares

---

## 📝 Exemplo de Template JSON Atualizado

```json
{
  "responsavel": {
    "crm_servicoeducacionalinteresse": {
      "id": "{{responsavel_serie_id}}"
    }
  },
  "dependentes": [
    {
      "crm_servicoeducacionalinteresse": {
        "id": "{{aluno_serie_id}}"
      }
    }
  ]
}
```

**Antes:** ID fixo `edbfd569-5dbc-ea11-a812-000d3ac06348`  
**Depois:** ID dinâmico baseado na seleção do usuário

---

## ✅ Checklist de Implementação

- [x] Criar método `get_series_escolares()` na API
- [x] Adicionar callback AJAX `ajax_get_series()`
- [x] Registrar hooks AJAX (logado e público)
- [x] Adicionar handler de teste no admin
- [x] Criar campos de select no formulário
- [x] Implementar função `loadSeries()` no JavaScript
- [x] Adicionar handler de mudança de select
- [x] Modificar lógica de envio para usar IDs dinâmicos
- [x] Adicionar colunas no banco de dados
- [x] Atualizar documentação de variáveis
- [x] Criar script de migração SQL
- [x] Testar fluxo completo

---

## 🚀 Próximos Passos (Opcional)

1. **Cache de Séries**: Implementar cache local para reduzir chamadas à API
2. **Validação**: Adicionar validação de GUID no backend
3. **UI/UX**: Adicionar loading spinner nos selects
4. **Filtros**: Permitir filtrar séries por segmento (Infantil, Fundamental, Médio)
5. **Sincronização**: Criar rotina para atualizar séries periodicamente

---

**Versão do Plugin:** 1.0.32
**Data:** 20/11/2025
