# Variáveis Disponíveis para Template JSON de Agendamento

## 📋 Como Usar

No campo **"Fetch Criação de Agendamento"** nas configurações do plugin, você pode usar variáveis que serão substituídas automaticamente pelos dados do agendamento.

**Formato:** `{{nome_da_variavel}}`

---

## 🔹 Variáveis do Responsável

| Variável | Descrição | Exemplo |
|----------|-----------|---------|
| `{{responsavel_nome}}` | Nome do responsável | "João" |
| `{{responsavel_sobrenome}}` | Sobrenome do responsável | "Silva" |
| `{{responsavel_email}}` | E-mail do responsável | "joao@email.com" |
| `{{responsavel_telefone}}` | Telefone do responsável | "27999999999" |
| `{{responsavel_serie_interesse}}` | Série de interesse (texto) | "1º Ano - Anos Iniciais" |
| `{{responsavel_serie_id}}` | ID da série (GUID) dinâmico | "efbfd569-5dbc-ea11-a812-000d3ac06348" |
| `{{responsavel_como_conheceu}}` | Como conheceu (número) | 191030009 |

---

## 🔹 Variáveis do Aluno

| Variável | Descrição | Exemplo |
|----------|-----------|---------|
| `{{aluno_nome}}` | Nome do aluno | "Maria" |
| `{{aluno_sobrenome}}` | Sobrenome do aluno | "Silva" |
| `{{aluno_serie_interesse}}` | Série de interesse (texto) | "3º Ano - Anos Iniciais" |
| `{{aluno_serie_id}}` | ID da série (GUID) dinâmico | "f3bfd569-5dbc-ea11-a812-000d3ac06348" |
| `{{aluno_ano_interesse}}` | Ano de interesse | 2025 |
| `{{aluno_escola_origem}}` | Escola de origem | "Escola Exemplo" |

---

## 🔹 Variáveis da Unidade

| Variável | Descrição | Exemplo |
|----------|-----------|---------|
| `{{unidade_id}}` | ID da unidade (GUID) | "ab4971d4-8b4f-eb11-a812-000d3ac1453b" |
| `{{unidade_nome}}` | Nome da unidade | "Vila Olímpia" |

---

## 🔹 Variáveis de Data/Hora

| Variável | Descrição | Formato | Exemplo |
|----------|-----------|---------|---------|
| `{{data_agendamento}}` | Data do agendamento | YYYY-MM-DD | "2025-11-20" |
| `{{hora_agendamento}}` | Hora do agendamento | HH:MM:SS | "14:00:00" |
| `{{scheduledstart}}` | Data/hora de início | ISO 8601 | "2025-11-20T14:00:00" |
| `{{scheduledend}}` | Data/hora de fim | ISO 8601 | "2025-11-20T15:00:00" |
| `{{estimatedarrivaltime}}` | Chegada estimada (-10min) | ISO 8601 | "2025-11-20T13:50:00" |
| `{{duracao_minutos}}` | Duração em minutos | Número | 60 |

---

## 🔹 Variáveis de Configuração

Estas variáveis vêm das configurações do plugin:

| Variável | Descrição | Valor Padrão |
|----------|-----------|--------------|
| `{{service_id}}` | ID do serviço de visita | "9afda331-8c4f-eb11-a812-000d3ac1453b" |
| `{{booking_status_id}}` | ID do status da reserva | "91c79750-acac-4da1-9e69-447b4bb2dfc9" |
| `{{resource_id}}` | ID do recurso/consultor | "473194a7-716c-eb11-a812-00224835d815" |
| `{{msdyn_status}}` | Status interno | 690970000 |
| `{{origem_positivo}}` | Origem do lead | 4 |

---

## 🔹 Variáveis Geradas Automaticamente

| Variável | Descrição | Exemplo |
|----------|-----------|---------|
| `{{lead_id}}` | ID do lead retornado pela API | "2149d2ed-1f84-40e0-9be0-6a9076952b7f" |
| `{{subject}}` | Título da visita | "Visita - Vila Olímpia" |
| `{{description}}` | Descrição da visita | "Visita agendada via site" |
| `{{requisito_name}}` | Nome do requisito | "Visita - Vila Olímpia - 20/11/2025" |

---

## 📝 Exemplo de Template JSON Completo

```json
{
    "responsavel": {
        "cad_tipointeressado": {
            "option": 0
        },
        "firstname": "{{responsavel_nome}}",
        "lastname": "{{responsavel_sobrenome}}",
        "emailaddress1": "{{responsavel_email}}",
        "crm_unidadeinteresse": {
            "id": "{{unidade_id}}"
        },
        "crm_servicoeducacionalinteresse": {
            "id": "{{responsavel_serie_id}}"
        },
        "col_comoconheceu": {
            "option": {{responsavel_como_conheceu}}
        },
        "pos_origem_positivo": {
            "option": {{origem_positivo}}
        },
        "mobilephone": "{{responsavel_telefone}}"
    },
    "dependentes": [
        {
            "cad_tipointeressado": {
                "option": 1
            },
            "firstname": "{{aluno_nome}}",
            "lastname": "{{aluno_sobrenome}}",
            "crm_unidadeinteresse": {
                "id": "{{unidade_id}}"
            },
            "crm_servicoeducacionalinteresse": {
                "id": "{{aluno_serie_id}}"
            },
            "col_anointeresse": {{aluno_ano_interesse}},
            "crmeduc_escoladeorigem": "{{aluno_escola_origem}}"
        }
    ],
    "atividadeServico": {
        "serviceid": {
            "id": "{{service_id}}"
        },
        "regardingobjectid": {
            "id": "{{lead_id}}"
        },
        "col_responsavel": {
            "id": "{{lead_id}}"
        },
        "pos_unidadecolegiopositivo": {
            "id": "{{unidade_id}}"
        },
        "subject": "{{subject}}",
        "scheduledstart": "{{scheduledstart}}",
        "scheduledend": "{{scheduledend}}",
        "description": "{{description}}"
    },
    "requisitoRecurso": {
        "msdyn_name": "{{requisito_name}}",
        "msdyn_fromdate": "{{scheduledstart}}",
        "msdyn_todate": "{{scheduledend}}",
        "msdyn_duration": {{duracao_minutos}},
        "msdyn_effort": 1.0,
        "msdyn_status": {
            "option": {{msdyn_status}}
        }
    },
    "reserva": {
        "resource": {
            "id": "{{resource_id}}"
        },
        "bookingstatus": {
            "id": "{{booking_status_id}}"
        },
        "msdyn_estimatedarrivaltime": "{{estimatedarrivaltime}}",
        "starttime": "{{scheduledstart}}",
        "endtime": "{{scheduledend}}",
        "duration": {{duracao_minutos}},
        "msdyn_effort": 1.0
    }
}
```

---

## 🔧 Como Configurar

1. Acesse: **Positivo CRM > Configurações**
2. Role até: **Fetch Criação de Agendamento**
3. Cole o JSON acima (ou personalize)
4. Salve as alterações
5. Crie um agendamento de teste
6. Clique em "Enviar para CRM"

O sistema substituirá automaticamente todas as variáveis `{{...}}` pelos valores reais!

---

## ⚠️ Notas Importantes

### Variáveis Obrigatórias

Certifique-se de incluir pelo menos:
- `{{responsavel_email}}` - Usado para buscar/criar o lead
- `{{unidade_id}}` - Unidade de interesse
- `{{scheduledstart}}` e `{{scheduledend}}` - Horários

### Tipos de Dados

- **Strings**: Use aspas duplas → `"{{variavel}}"`
- **Números**: Sem aspas → `{{variavel}}`
- **GUIDs**: Sempre com aspas → `"{{variavel}}"`

### Variáveis Não Encontradas

Se uma variável não for encontrada, será substituída por:
- Strings vazias: `""`
- Números: `0`
- GUIDs: `null`

---

**Versão:** 1.0.25  
**Data:** 18/11/2025
