/**
 * Script de administração do Positivo CRM.
 *
 * Este script adiciona vários testes de integração ao painel de configurações do plugin.
 * Cada botão executa uma requisição AJAX distinta para validar as funcionalidades
 * (geração de token, consulta de unidades, busca de responsável, busca de alunos
 * e criação de agendamento). Os resultados são exibidos em elementos <pre>.
 */

jQuery(function($){
    /**
     * Função auxiliar para executar uma requisição AJAX e exibir o resultado.
     *
     * @param {string} resultSelector Seletor do elemento <pre> para exibir o resultado.
     * @param {object} data Objeto de dados a enviar via POST (deve incluir action e nonce).
     */
    function runTest(resultSelector, data) {
        var $result = $(resultSelector);
        $result.text('Testando...');
        $.post(PositivoCRMAjax.ajaxurl, data)
        .done(function(resp) {
            if (resp && resp.success) {
                var d = resp.data;
                if (typeof d === 'object') {
                    try {
                        $result.text(JSON.stringify(d, null, 2));
                    } catch (e) {
                        $result.text(String(d));
                    }
                } else {
                    $result.text(String(d));
                }
            } else {
                var msg = resp && resp.data && resp.data.message ? resp.data.message : 'Erro desconhecido';
                $result.text('Erro: ' + msg);
            }
        })
        .fail(function(xhr) {
            var status = xhr.status || '';
            var statusText = xhr.statusText || '';
            $result.text('Falha de rede: ' + status + ' ' + statusText);
        });
    }

    // Testar Token
    $('#positivo-crm-test-token').on('click', function(){
        runTest('#positivo-crm-test-token-result', {
            action: 'positivo_crm_test_token',
            nonce: PositivoCRMAjax.nonce
        });
    });

    // Testar Unidades
    $('#positivo-crm-test-unidades').on('click', function(){
        runTest('#positivo-crm-test-unidades-result', {
            action: 'positivo_crm_test_units',
            nonce: PositivoCRMAjax.nonce
        });
    });

    // Testar Responsável
    $('#positivo-crm-test-responsavel').on('click', function(){
        var nome = $('#positivo-crm-test-responsavel-name').val() || '';
        if (!nome) {
            $('#positivo-crm-test-responsavel-result').text('Informe um nome para testar.');
            return;
        }
        runTest('#positivo-crm-test-responsavel-result', {
            action: 'positivo_crm_test_responsavel',
            nonce: PositivoCRMAjax.nonce,
            name: nome
        });
    });

    // Testar Alunos
    $('#positivo-crm-test-alunos').on('click', function(){
        var id = $('#positivo-crm-test-alunos-id').val() || '';
        if (!id) {
            $('#positivo-crm-test-alunos-result').text('Informe o ID do responsável para testar.');
            return;
        }
        runTest('#positivo-crm-test-alunos-result', {
            action: 'positivo_crm_test_alunos',
            nonce: PositivoCRMAjax.nonce,
            responsavel_id: id
        });
    });

    // Testar Séries Escolar
    $('#positivo-crm-test-series').on('click', function(){
        runTest('#positivo-crm-test-series-result', {
            action: 'positivo_crm_test_series',
            nonce: PositivoCRMAjax.nonce
        });
    });

    // Testar Criação de Agendamento
    $('#positivo-crm-test-agendamento').on('click', function(){
        var resp = $('#positivo-crm-test-agendamento-responsavel').val() || '';
        var aluno = $('#positivo-crm-test-agendamento-aluno').val() || '';
        var dataAg = $('#positivo-crm-test-agendamento-data').val() || '';
        if (!resp || !aluno || !dataAg) {
            $('#positivo-crm-test-agendamento-result').text('Preencha todos os campos para testar o agendamento.');
            return;
        }
        // Construir form_data da mesma forma que o front-end envia
        var formData = $.param({
            responsavel_nome: resp,
            aluno_nome: aluno,
            data_agendamento: dataAg
        });
        runTest('#positivo-crm-test-agendamento-result', {
            action: 'positivo_crm_submit_agendamento',
            nonce: PositivoCRMAjax.nonce,
            form_data: formData
        });
    });
});