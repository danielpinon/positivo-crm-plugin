<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * Classe responsável pelas funcionalidades administrativas do plugin.
 *
 * Esta classe registra páginas de administração, gerencia a criação das tabelas
 * necessárias, exibe formulários de configuração, lista agendamentos e horários,
 * e oferece uma meta box para adicionar o formulário de agendamento às páginas
 * diretamente no editor do WordPress.
 */
class Positivo_CRM_Admin
{
    private function require_admin_permissions_ajax()
    {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Você não tem permissão para executar esta ação.', 'positivo-crm')], 403);
        }
    }

    private function require_public_ajax_nonce()
    {
        check_ajax_referer('positivo-crm-nonce', 'nonce');
    }

    private function enforce_public_rate_limit($action, $limit = 20, $window = 300)
    {
        $ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : 'unknown';
        $key = 'positivo_crm_rl_' . md5($action . '|' . $ip);
        $attempts = (int) get_transient($key);

        if ($attempts >= $limit) {
            wp_send_json_error(['message' => __('Muitas tentativas. Aguarde alguns minutos e tente novamente.', 'positivo-crm')], 429);
        }

        set_transient($key, $attempts + 1, $window);
    }

    private function sanitize_responsavel_public_data($responsavel)
    {
        if (!is_array($responsavel)) {
            return null;
        }

        return [
            'leadid' => sanitize_text_field($responsavel['leadid'] ?? ''),
            'fullname' => sanitize_text_field($responsavel['fullname'] ?? ''),
            'emailaddress1' => sanitize_email($responsavel['emailaddress1'] ?? ''),
        ];
    }

    private function sanitize_alunos_public_data($alunos)
    {
        if (!is_array($alunos)) {
            return [];
        }

        return array_values(array_map(function ($aluno) {
            return [
                'leadid' => sanitize_text_field($aluno['leadid'] ?? $aluno['studentid'] ?? $aluno['id'] ?? ''),
                'fullname' => sanitize_text_field($aluno['fullname'] ?? $aluno['nome'] ?? $aluno['aluno_nome'] ?? ''),
                'aluno_serie' => sanitize_text_field($aluno['aluno_serie'] ?? $aluno['serie'] ?? $aluno['col_turnointeresse'] ?? ''),
                'aluno_ano' => sanitize_text_field($aluno['aluno_ano'] ?? $aluno['ano'] ?? $aluno['col_anointeresse'] ?? ''),
                'aluno_escola' => sanitize_text_field($aluno['aluno_escola'] ?? $aluno['escola'] ?? $aluno['school'] ?? $aluno['cad_inscricaoatual'] ?? ''),
                'aluno_nascimento' => sanitize_text_field($aluno['aluno_nascimento'] ?? $aluno['cad_datanascimento'] ?? $aluno['nascimento'] ?? ''),
            ];
        }, $alunos));
    }

    /**
     * Construtor: registra os hooks necessários.
     */
    public function __construct()
    {
        // Adiciona menus no admin
        add_action('admin_menu', array($this, 'register_admin_menu'));
        // Registra configurações e campos
        add_action('admin_init', array($this, 'register_settings'));
        // Meta boxes no editor de páginas
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post', array($this, 'save_page_meta'), 10, 2);
        // Insere o formulário no conteúdo quando apropriado
        add_filter('the_content', array($this, 'append_agendamento_to_content'));

        // Enfileira os scripts e estilos do admin quando necessário
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));

        // AJAX para testar a API / FetchXML
        add_action('wp_ajax_positivo_crm_test_fetch', array($this, 'ajax_test_units'));

        // Novos endpoints de teste
        add_action('wp_ajax_positivo_crm_test_token', array($this, 'ajax_test_token'));
        add_action('wp_ajax_positivo_crm_test_units', array($this, 'ajax_test_units'));
        add_action('wp_ajax_positivo_crm_test_responsavel', array($this, 'ajax_test_responsavel'));
        add_action('wp_ajax_positivo_crm_test_alunos', array($this, 'ajax_test_alunos'));
        add_action('wp_ajax_positivo_crm_test_series', array($this, 'ajax_test_series'));

        // Endpoints para o frontend do formulário
        add_action('wp_ajax_positivo_crm_search_responsavel_frontend', array($this, 'ajax_search_responsavel_frontend'));
        add_action('wp_ajax_nopriv_positivo_crm_search_responsavel_frontend', array($this, 'ajax_search_responsavel_frontend'));
        add_action('wp_ajax_positivo_crm_get_responsavel_e_alunos', array($this, 'ajax_get_responsavel_e_alunos'));
        add_action('wp_ajax_nopriv_positivo_crm_get_responsavel_e_alunos', array($this, 'ajax_get_responsavel_e_alunos'));
        add_action('wp_ajax_positivo_crm_get_students', array($this, 'ajax_get_students'));
        add_action('wp_ajax_nopriv_positivo_crm_get_students', array($this, 'ajax_get_students'));
        add_action('wp_ajax_positivo_crm_get_next_available_dates', [$this, 'ajax_get_next_available_dates']);
        add_action('wp_ajax_nopriv_positivo_crm_get_next_available_dates', [$this, 'ajax_get_next_available_dates']);
        add_action('wp_ajax_positivo_crm_get_times', array($this, 'ajax_get_times'));
        add_action('wp_ajax_nopriv_positivo_crm_get_times', array($this, 'ajax_get_times'));

        // Endpoint para Criar Agendamento
        add_action('wp_ajax_nopriv_positivo_crm_submit_agendamento_public', [$this, 'positivo_crm_submit_agendamento_public']);
        add_action('wp_ajax_positivo_crm_submit_agendamento_public', [$this, 'positivo_crm_submit_agendamento_public']);

        // Endpint para Buscar Colegios
        add_action('wp_ajax_nopriv_positivo_crm_search_eschool_public', [$this, 'positivo_crm_search_eschool_public']);
        add_action('wp_ajax_positivo_crm_search_eschool_public', [$this, 'positivo_crm_search_eschool_public']);

        // Ajustes da tabela
        add_action('plugins_loaded', [$this, 'ensure_duracao_visita_column']);
    }

    /**
     * Summary of positivo_crm_submit_agendamento_public
     * Cria agenda e envia para o CRM
     * @return void
     */
    public function positivo_crm_submit_agendamento_public()
    {
        global $wpdb;

        Positivo_CRM_Logger::info("Recebido submit_agendamento_public", [
            'request_keys' => array_keys($_POST)
        ]);

        // ============================================================
        // 1) VALIDAR NONCE
        // ============================================================
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'positivo-crm-nonce')) {
            wp_send_json_error(['message' => 'Falha de autenticação. Atualize a página.']);
        }

        // ============================================================
        // 2) DECODIFICAR form_data EM DUPLO (obrigatório)
        // ============================================================
        $form_raw = $_POST['form_data'] ?? '';

        $form_raw = urldecode($form_raw);
        $form_raw = urldecode($form_raw);

        parse_str($form_raw, $form);

        // ============================
        // CAPTURA DE UTMs
        // ============================
        $utms = [
            'utm_source' => sanitize_text_field($form['utm_source'] ?? $_COOKIE['utm_source'] ?? ''),
            'utm_medium' => sanitize_text_field($form['utm_medium'] ?? $_COOKIE['utm_medium'] ?? ''),
            'utm_campaign' => sanitize_text_field($form['utm_campaign'] ?? $_COOKIE['utm_campaign'] ?? ''),
            'utm_term' => sanitize_text_field($form['utm_term'] ?? $_COOKIE['utm_term'] ?? ''),
            'utm_content' => sanitize_text_field($form['utm_content'] ?? $_COOKIE['utm_content'] ?? ''),
        ];

        /**
         * CAPTURA DE RECURSO E SERVIÇO
         */
        $servico = sanitize_text_field($form['servico'] ?? $_COOKIE['servico'] ?? '');
        $recurso = sanitize_text_field($form['recurso'] ?? $_COOKIE['recurso'] ?? '');


        Positivo_CRM_Logger::info("FORM DECODIFICADO", [
            'fields' => array_keys($form),
            'alunos_count' => is_array($form['aluno_nome'] ?? null) ? count($form['aluno_nome']) : 0
        ]);


        // Se quiser testar:
        // wp_send_json_success(['debug' => $form]);

        // ============================================================
        // 3) MONTAR DADOS DO AGENDAMENTO
        // ============================================================

        $table = $wpdb->prefix . 'positivo_agendamentos';

        // ============================================================
        // RESOLUÇÃO DA UNIDADE (ID + NOME)
        // ============================================================

        // 🔹 ID bruto vindo do form (pode vir com { })
        $unidade_id_raw = $form['crm_unidadeinteresse'] ?? '';

        // 🔹 Limpa { }
        $unidade_id = $unidade_id_raw;
        $form['crm_unidadeinteresse'] = trim(str_replace(['{', '}'], '', sanitize_text_field($unidade_id_raw))); // Atualiza no form

        // 🔹 Nome da unidade (tentativa 1: vindo do form)
        // 🔹 Fallback: buscar no CRM se não veio no form
        if (!empty($unidade_id)) {
            try {
                $api = new Positivo_CRM_API();
                $response = $api->get_unidades();
                if (
                    is_array($response)
                    && isset($response['result'])
                    && is_array($response['result'])
                ) {
                    foreach ($response['result'] as $unidade) {
                        // Possíveis campos de ID no retorno
                        $crm_id_raw =
                            $unidade['cad_categoriaid'] ?? '';
                        if ($crm_id_raw === $unidade_id) {
                            // Possíveis campos de nome
                            $unidade_nome = sanitize_text_field(
                                $unidade['cad_name']
                                ?? ''
                            );
                            break;
                        }
                    }
                }

            } catch (Exception $e) {

                Positivo_CRM_Logger::error('Erro ao buscar unidade no CRM', [
                    'exception' => $e->getMessage(),
                    'unidade_id' => $unidade_id
                ]);

                $unidade_nome = "";
            }
        }


        // ============================
        // RESOLVER SÉRIE (ID + NOME) DE TODOS OS FILHOS
        // ============================

        $series_resolvidas = [];

        $map_series_raw = $unidade['pos_mapeamentoseries'] ?? '';
        $map_series = json_decode($map_series_raw, true);

        $series_map = [];

        if (
            is_array($map_series)
            && isset($map_series[$unidade['cad_name']]['Series'])
        ) {
            $series_map = $map_series[$unidade['cad_name']]['Series'];
        }

        // Percorre todos os filhos vindos do form
        foreach (($form['aluno_serie_id'] ?? []) as $index => $serie_id_raw) {

            $serie_id = trim(str_replace(['{', '}'], '', $serie_id_raw));
            $serie_nome = '';

            // Tenta resolver o nome pelo mapa
            foreach ($series_map as $nome => $id) {
                if (strcasecmp($id, $serie_id) === 0) {
                    $serie_nome = $nome;
                    break;
                }
            }

            if (!$serie_nome) {
                Positivo_CRM_Logger::warning('Série não encontrada para o aluno', [
                    'aluno_index' => $index,
                    'serie_id' => $serie_id,
                    'unidade' => $unidade['cad_name'] ?? ''
                ]);
            }

            $series_resolvidas[] = [
                'aluno_index' => $index,
                'serie_id' => $serie_id,
                'serie_nome' => $serie_nome
            ];
        }
        // ============================
        // DEFINIR SÉRIE DO ALUNO (RESOLVIDA)
        // ============================

        $aluno_serie_id_final = '';
        $aluno_serie_nome_final = '';

        if (!empty($series_resolvidas[0])) {
            $aluno_serie_id_final = [];
            $aluno_serie_nome_final = [];
            foreach ($series_resolvidas as $key => $serie_resolvida) {
                array_push($aluno_serie_id_final, $serie_resolvida['serie_id']);
                array_push($aluno_serie_nome_final, $serie_resolvida['serie_nome']);
            }
        }


        /**
         * Quantidade de tempo de cada unidade e dia da semana
         */
        $dias_map = [
            'monday' => 'segunda',
            'tuesday' => 'terca',
            'wednesday' => 'quarta',
            'thursday' => 'quinta',
            'friday' => 'sexta',
            'saturday' => 'sabado',
            'sunday' => 'domingo',
        ];
        $date = new DateTime($form['agendamento_data']);
        $date_str = $date->format('Y-m-d');
        $weekday_en = strtolower($date->format('l'));
        $dia_semana = $dias_map[$weekday_en] ?? '';
        global $wpdb;
        $table_horarios = $wpdb->prefix . 'positivo_unidade_horarios';
        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT hora_inicio, hora_fim, duracao_visita_minutos FROM {$table_horarios}
                WHERE unidade = %s AND dia_semana = %s",
                $unidade_id,
                $dia_semana
            )
        );
        $duracao = isset($rows[0]->duracao_visita_minutos)
            ? (int) $rows[0]->duracao_visita_minutos
            : 120; // fallback padrão

        // 🔹 Segurança final
        $unidade_nome = $unidade_nome ?: '';

        // 🔹 LOG DE DEBUG (opcional, mas recomendado)
        Positivo_CRM_Logger::info('Unidade resolvida', [
            'unidade_id' => $unidade_id,
            'unidade_nome' => $unidade_nome
        ]);

        $alunos = [];
        $countAlunos = count($form['aluno_nome'] ?? []);
        for ($i = 0; $i < $countAlunos; $i++) {
            $alunos[] = [
                'nome' => sanitize_text_field($form['aluno_nome'][$i] ?? ''),
                'escola_origem' => sanitize_text_field($form['aluno_escola'][$i] ?? ''),
                'ano_interesse' => intval($form['aluno_ano'][$i] ?? 0),
                'serie_id' => $aluno_serie_id_final[$i] ?? '',
                'serie_interesse' => $aluno_serie_nome_final[$i] ?? '',
            ];
        }

        $dados = [
            // Responsável
            'responsavel_nome' => sanitize_text_field($form['responsavel_nome'] ?? ''),
            'responsavel_email' => sanitize_email($form['responsavel_email'] ?? ''),
            'responsavel_telefone' => sanitize_text_field($form['responsavel_telefone'] ?? ''),
            'responsavel_serie_id' => sanitize_text_field($form['responsavel_serie_id'] ?? ''),
            'responsavel_serie_interesse' => sanitize_text_field($form['responsavel_serie'] ?? ''),

            // Aluno (apenas primeiro aluno)
            'aluno_nome' => sanitize_text_field($form['aluno_nome'][0] ?? ''),
            'aluno_escola_origem' => sanitize_text_field($form['aluno_escola'][0] ?? ''),
            'aluno_ano_interesse' => intval($form['aluno_ano'][0] ?? 0),
            'aluno_serie_id' => $aluno_serie_id_final,
            'aluno_serie_interesse' => $aluno_serie_nome_final,

            // Unidade
            'unidade_id' => sanitize_text_field($form['crm_unidadeinteresse'] ?? ''),
            'unidade_nome' => $unidade_nome,

            // Agendamento
            'data_agendamento' => sanitize_text_field($form['agendamento_data'] ?? ''),
            'hora_agendamento' => sanitize_text_field($form['agendamento_hora'] ?? ''),

            'servico' => $servico,
            'recurso' => $recurso,

            // Meta
            'duracao_minutos' => $duracao,
            'status' => 'pendente',
            'created_by' => 0,
            'enviado_crm' => 0,
        ];

        $temp = $dados;
        $temp['alunos'] = $alunos;
        $temp['utms'] = $utms;

        Positivo_CRM_Logger::info("DADOS PARA INSERIR", [
            'dados' => $temp
        ]);

        // ============================================================
        // 4) SALVAR NO BANCO
        // ============================================================
        $wpdb->insert($table, $dados);

        $agendamento_id = $wpdb->insert_id;

        if (!$agendamento_id) {
            Positivo_CRM_Logger::error("Erro ao salvar agendamento", [
                'dados' => $dados
            ]);
            wp_send_json_error(['message' => 'Erro ao salvar agendamento no site.']);
        }

        Positivo_CRM_Logger::info("Agendamento salvo", [
            'agendamento_id' => $agendamento_id
        ]);

        // ============================================================
        // 5) ENVIAR PARA O CRM
        // ============================================================
        // Prepara Alunos
        $dados['alunos'] = $alunos;
        $dados['utms'] = $utms;
        $crm = $this->enviar_agendamento_para_crm_json($dados);

        // $crm = $this->enviar_agendamento_para_crm($agendamento_id);

        if (is_wp_error($crm)) {
            Positivo_CRM_Logger::error("Erro CRM", [
                'erro' => $crm->get_error_message()
            ]);

            wp_send_json_error([
                'message' => 'Erro ao comunicar com o CRM: ' . $crm->get_error_message(),
                'agendamento_id' => $agendamento_id
            ]);
        }

        // ============================================================
        // 6) RETORNO FINAL AO FRONT
        // ============================================================
        wp_send_json_success([
            'message' => 'Agendamento realizado e enviado com sucesso!',
            'agendamento_id' => $agendamento_id,
            'agendamento' => $dados,
            'crm_response' => $crm
        ]);
    }
    public function positivo_crm_search_eschool_public()
    {
        global $wpdb;

        $this->require_public_ajax_nonce();

        $descricao = sanitize_text_field($_POST['descricao'] ?? '');

        if (empty($descricao)) {
            wp_send_json_error([
                'message' => 'Descrição não informada'
            ]);
        }

        /**
         * ============================
         * 1️⃣ BUSCA NO BANCO LOCAL
         * ============================
         */
        $table = 'rh_instituicao_ensino'; // ajuste se NÃO usar prefixo

        $results = $wpdb->get_results(
            $wpdb->prepare(
                "
            SELECT 
                id,
                descricao,
                grau,
                status,
                endereco,
                latitude,
                longitude,
                cep
            FROM {$table}
            WHERE descricao LIKE %s
            LIMIT 30
            ",
                '%' . $wpdb->esc_like($descricao) . '%'
            ),
            ARRAY_A
        );

        /**
         * ============================
         * 2️⃣ NORMALIZAÇÃO DO RETORNO
         * ============================
         */
        $data = array_map(function ($item) {
            return [
                'id' => (int) $item['id'],
                'descricao' => $item['descricao'],
                'grau' => $item['grau'] ?? null,
                'status' => $item['status'] ?? null,
                'endereco' => $item['endereco'] ?? '',
                'latitude' => $item['latitude'] ?? '',
                'longitude' => $item['longitude'] ?? '',
                'cep' => $item['cep'] ?? null,
            ];
        }, $results);

        /**
         * ============================
         * 3️⃣ RETORNO (MESMO FORMATO)
         * ============================
         */
        wp_send_json_success([
            'data' => $data
        ]);
    }


    /**
     * Enfileira scripts e estilos do admin.
     * Apenas carrega os assets na página de configurações do plugin.
     *
     * @param string $hook A identificação da página do admin.
     */
    public function enqueue_admin_assets($hook)
    {
        // Carrega nas páginas do plugin
        if (isset($_GET['page']) && ('positivo_crm' === $_GET['page'] || 'positivo_crm_agendamentos' === $_GET['page'])) {
            // Enfileira jQuery
            wp_enqueue_script('jquery');
            // Enfileira nosso script de admin
            // Utiliza a constante POSITIVO_CRM_VERSION para quebrar o cache quando o
            // script for atualizado. Caso a constante não exista, utiliza o timestamp
            // atual para forçar a atualização do arquivo no navegador.
            $script_version = defined('POSITIVO_CRM_VERSION') ? POSITIVO_CRM_VERSION : time();
            wp_enqueue_script(
                'positivo-crm-admin',
                POSITIVO_CRM_URL . 'assets/js/positivo-crm-admin.js',
                array('jquery'),
                $script_version,
                true
            );
            // Passa a URL do AJAX e um nonce para segurança
            wp_localize_script('positivo-crm-admin', 'PositivoCRMAjax', array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('positivo-crm-nonce'),
            ));
        }

        // Para a página de agendamentos, apenas enfileira jQuery
        if (isset($_GET['page']) && 'positivo_crm_agendamentos' === $_GET['page']) {
            wp_enqueue_script('jquery');
        }
    }

    /**
     * Callback AJAX para testar a obtenção do token de acesso.
     *
     * Faz a chamada para Positivo_CRM_API::get_token() e retorna o resultado.
     */
    public function ajax_test_token()
    {
        check_ajax_referer('positivo-crm-nonce', 'nonce');
        $this->require_admin_permissions_ajax();
        if (!class_exists('Positivo_CRM_API')) {
            wp_send_json_error(array('message' => __('Classe da API não encontrada.', 'positivo-crm')));
        }
        $api = new Positivo_CRM_API();
        $response = $api->get_token();
        if (is_wp_error($response)) {
            wp_send_json_error(array(
                'message' => $response->get_error_message(),
                'code' => $response->get_error_code(),
                'data' => $response->get_error_data(),
            ));
        }
        wp_send_json_success($response);
    }

    /**
     * Callback AJAX para testar a consulta de unidades via LocalizacaoAvancada.
     *
     * Reutiliza Positivo_CRM_API::get_unidades().
     */
    public function ajax_test_units()
    {
        check_ajax_referer('positivo-crm-nonce', 'nonce');
        $this->require_admin_permissions_ajax();
        if (!class_exists('Positivo_CRM_API')) {
            wp_send_json_error(array('message' => __('Classe da API não encontrada.', 'positivo-crm')));
        }
        $api = new Positivo_CRM_API();
        $response = $api->get_unidades();
        if (is_wp_error($response)) {
            wp_send_json_error(array(
                'message' => $response->get_error_message(),
                'code' => $response->get_error_code(),
                'data' => $response->get_error_data(),
            ));
        }
        wp_send_json_success($response);
    }

    /**
     * Callback AJAX para testar a consulta de responsável.
     *
     * Espera um parâmetro POST 'name' com o nome a ser buscado.
     */
    public function ajax_test_responsavel()
    {
        check_ajax_referer('positivo-crm-nonce', 'nonce');
        $this->require_admin_permissions_ajax();
        $name = isset($_POST['name']) ? sanitize_text_field(wp_unslash($_POST['name'])) : '';
        if (empty($name)) {
            wp_send_json_error(array('message' => __('Nome do responsável não fornecido.', 'positivo-crm')));
        }
        if (!class_exists('Positivo_CRM_API')) {
            wp_send_json_error(array('message' => __('Classe da API não encontrada.', 'positivo-crm')));
        }
        $api = new Positivo_CRM_API();
        $response = $api->search_responsavel($name);
        if (is_wp_error($response)) {
            wp_send_json_error(array(
                'message' => $response->get_error_message(),
                'code' => $response->get_error_code(),
                'data' => $response->get_error_data(),
            ));
        }
        wp_send_json_success($response);
    }

    /**
     * Callback AJAX para testar a consulta de alunos por ID do responsável.
     *
     * Espera um parâmetro POST 'responsavel_id' com o ID do responsável.
     */
    public function ajax_test_alunos()
    {
        check_ajax_referer('positivo-crm-nonce', 'nonce');
        $this->require_admin_permissions_ajax();
        $id = isset($_POST['responsavel_id']) ? sanitize_text_field(wp_unslash($_POST['responsavel_id'])) : '';
        if (empty($id)) {
            wp_send_json_error(array('message' => __('ID do responsável não fornecido.', 'positivo-crm')));
        }
        if (!class_exists('Positivo_CRM_API')) {
            wp_send_json_error(array('message' => __('Classe da API não encontrada.', 'positivo-crm')));
        }
        $api = new Positivo_CRM_API();
        // Verifica se a função search_aluno_by_responsavel existe
        if (!method_exists($api, 'search_aluno_by_responsavel')) {
            wp_send_json_error(array('message' => __('Método search_aluno_by_responsavel não implementado.', 'positivo-crm')));
        }
        $response = $api->search_aluno_by_responsavel($id);
        if (is_wp_error($response)) {
            wp_send_json_error(array(
                'message' => $response->get_error_message(),
                'code' => $response->get_error_code(),
                'data' => $response->get_error_data(),
            ));
        }
        wp_send_json_success($response);
    }

    /**
     * Callback AJAX para testar a consulta de séries escolares.
     */
    public function ajax_test_series()
    {
        check_ajax_referer('positivo-crm-nonce', 'nonce');
        $this->require_admin_permissions_ajax();
        if (!class_exists('Positivo_CRM_API')) {
            wp_send_json_error(array('message' => __('Classe da API não encontrada.', 'positivo-crm')));
        }
        $api = new Positivo_CRM_API();
        $response = $api->get_series_escolares();
        if (is_wp_error($response)) {
            wp_send_json_error(array(
                'message' => $response->get_error_message(),
                'code' => $response->get_error_code(),
                'data' => $response->get_error_data(),
            ));
        }
        wp_send_json_success($response);
    }

    /**
     * Endpoint AJAX para pesquisar um responsável no frontend.
     *
     * Recebe um parâmetro 'fullname' e utiliza o método search_responsavel
     * da API. Retorna os dados brutos da API em caso de sucesso. Em caso
     * de erro, retorna via wp_send_json_error com a mensagem.
     */
    public function ajax_search_responsavel_frontend()
    {
        Positivo_CRM_Logger::debug('AJAX callback: ajax_search_responsavel_frontend', array('request_keys' => array_keys($_POST)));
        $this->require_public_ajax_nonce();
        $this->enforce_public_rate_limit('search_responsavel_frontend', 10, 300);
        $fullname = isset($_POST['fullname']) ? sanitize_text_field(wp_unslash($_POST['fullname'])) : '';
        if (empty($fullname)) {
            wp_send_json_error(array('message' => __('Nome do responsável não fornecido.', 'positivo-crm')));
        }
        if (!class_exists('Positivo_CRM_API')) {
            wp_send_json_error(array('message' => __('Classe da API não encontrada.', 'positivo-crm')));
        }
        $api = new Positivo_CRM_API();
        $response = $api->search_responsavel($fullname);
        if (is_wp_error($response)) {
            wp_send_json_error(array(
                'message' => $response->get_error_message(),
                'code' => $response->get_error_code(),
                'data' => $response->get_error_data(),
            ));
        }
        wp_send_json_success([
            'result' => $this->sanitize_responsavel_public_data($response['result'] ?? null),
        ]);
    }

    /**
     * Endpoint AJAX unificado:
     * Busca o responsável pelo nome + todos os alunos associados
     */
    public function ajax_get_responsavel_e_alunos()
    {
        Positivo_CRM_Logger::debug('AJAX callback: ajax_get_responsavel_e_alunos', [
            'request_keys' => array_keys($_POST)
        ]);

        $this->require_public_ajax_nonce();
        $this->enforce_public_rate_limit('get_responsavel_e_alunos', 10, 300);

        $fullname = isset($_POST['fullname']) ? sanitize_text_field(wp_unslash($_POST['fullname'])) : '';

        if (empty($fullname)) {
            wp_send_json_error(['message' => 'Nome do responsável não informado.']);
        }

        $api = new Positivo_CRM_API();

        /*
        |--------------------------------------------------------------------------
        | 1) BUSCA RESPONSÁVEL
        |--------------------------------------------------------------------------
        */
        $responsavel = $api->search_responsavel($fullname);

        if (is_wp_error($responsavel)) {
            wp_send_json_error([
                'message' => $responsavel->get_error_message(),
                'code' => $responsavel->get_error_code()
            ]);
        }

        /**
         * ➜ No seu retorno real, o responsável vem em:
         *   data.result   (OBJETO)
         */
        if (isset($responsavel['result']) && is_array($responsavel['result'])) {
            $respItem = $responsavel['result'];
        } else {
            wp_send_json_success([
                'responsavel' => null,
                'alunos' => []
            ]);
        }

        // Remove chaves { } do GUID
        $leadId = isset($respItem['leadid']) ? str_replace(['{', '}'], '', $respItem['leadid']) : null;

        if (!$leadId) {
            wp_send_json_success([
                'responsavel' => null,
                'alunos' => []
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | 2) BUSCA ALUNOS DO RESPONSÁVEL
        |--------------------------------------------------------------------------
        */
        if (!method_exists($api, 'search_aluno_by_responsavel')) {
            wp_send_json_error(['message' => 'Método search_aluno_by_responsavel não implementado']);
        }

        $alunos = $api->search_aluno_by_responsavel($leadId);

        if (is_wp_error($alunos)) {
            wp_send_json_error([
                'message' => $alunos->get_error_message(),
                'code' => $alunos->get_error_code()
            ]);
        }

        /**
         * ➜ Alunos normalmente vêm em:
         *   data.result  (array)
         */
        if (isset($alunos['result']) && is_array($alunos['result'])) {
            $alunosList = $alunos['result'];
        } elseif (isset($alunos['value']) && is_array($alunos['value'])) {
            $alunosList = $alunos['value'];
        } else {
            $alunosList = [];
        }

        /*
        |--------------------------------------------------------------------------
        | 3) RETORNO FINAL
        |--------------------------------------------------------------------------
        */
        wp_send_json_success([
            'responsavel' => $this->sanitize_responsavel_public_data($respItem),
            'alunos' => $this->sanitize_alunos_public_data($alunosList)
        ]);
    }



    /**
     * Endpoint AJAX para obter alunos de um responsável a partir de seu leadID.
     *
     * Espera o parâmetro 'responsavel_id'. Retorna os alunos associados ou
     * mensagem de erro se houver falha.
     */
    public function ajax_get_students()
    {
        Positivo_CRM_Logger::debug('AJAX callback: ajax_get_students', array('request_keys' => array_keys($_POST)));
        $this->require_public_ajax_nonce();
        $this->enforce_public_rate_limit('get_students', 10, 300);
        $lead_id = isset($_POST['responsavel_id']) ? sanitize_text_field(wp_unslash($_POST['responsavel_id'])) : '';
        if (empty($lead_id)) {
            wp_send_json_error(array('message' => __('ID do responsável não fornecido.', 'positivo-crm')));
        }
        if (!class_exists('Positivo_CRM_API')) {
            wp_send_json_error(array('message' => __('Classe da API não encontrada.', 'positivo-crm')));
        }
        $api = new Positivo_CRM_API();
        if (!method_exists($api, 'search_aluno_by_responsavel')) {
            wp_send_json_error(array('message' => __('Método search_aluno_by_responsavel não implementado.', 'positivo-crm')));
        }
        $response = $api->search_aluno_by_responsavel($lead_id);
        if (is_wp_error($response)) {
            wp_send_json_error(array(
                'message' => $response->get_error_message(),
                'code' => $response->get_error_code(),
                'data' => $response->get_error_data(),
            ));
        }
        wp_send_json_success([
            'result' => $this->sanitize_alunos_public_data($response['result'] ?? $response['value'] ?? []),
        ]);
    }

    /**
     * Endpoint AJAX responsável por retornar as próximas datas disponíveis
     * para agendamento, considerando a unidade selecionada.
     *
     * Este método:
     * - Percorre os próximos dias a partir da data atual;
     * - Identifica o dia da semana de cada data;
     * - Busca os horários de funcionamento configurados no painel administrativo
     *   para a unidade e dia da semana correspondentes;
     * - Gera os slots de horários respeitando a duração da visita definida no admin;
     * - Consulta os agendamentos já realizados no CRM Educacional Positivo;
     * - Remove automaticamente os horários que colidem total ou parcialmente
     *   com agendamentos existentes no CRM;
     * - Retorna apenas as datas que possuem pelo menos um horário disponível;
     * - Limita o retorno às próximas 5 datas disponíveis.
     *
     * A fonte de verdade para bloqueio de horários é o CRM, garantindo
     * consistência entre o sistema WordPress e o CRM externo.
     *
     * Retorno (JSON):
     * {
     *   success: true,
     *   data: {
     *     dates: [
     *       {
     *         date: "2025-12-19",
     *         weekday: "sexta",
     *         times: ["08:00", "09:30", "11:00"]
     *       },
     *       {
     *         date: "2025-12-20",
     *         weekday: "sabado",
     *         times: ["09:00", "10:30"]
     *       }
     *     ]
     *   }
     * }
     */
    public function ajax_get_next_available_dates()
    {
        $this->require_public_ajax_nonce();

        $unit = sanitize_text_field($_POST['unit'] ?? '');

        if (empty($unit)) {
            wp_send_json_error(['message' => 'Unidade não informada.']);
        }

        $max_dates = 5;
        $found = [];
        $today = new DateTime('tomorrow');
        $api = new Positivo_CRM_API();

        // Mapeamento de dias
        $dias_map = [
            'monday' => 'segunda',
            'tuesday' => 'terca',
            'wednesday' => 'quarta',
            'thursday' => 'quinta',
            'friday' => 'sexta',
            'saturday' => 'sabado',
            'sunday' => 'domingo',
        ];

        global $wpdb;
        $table_horarios = $wpdb->prefix . 'positivo_unidade_horarios';
        $table_agenda = $wpdb->prefix . 'positivo_agendamentos';

        // Vamos buscar até no máximo 30 dias à frente
        for ($i = 0; $i < 30 && count($found) < $max_dates; $i++) {

            $date = (clone $today)->modify("+{$i} days");
            $date_str = $date->format('Y-m-d');
            $weekday_en = strtolower($date->format('l'));
            $dia_semana = $dias_map[$weekday_en] ?? null;

            if (!$dia_semana) {
                continue;
            }

            // Horários configurados no admin
            $rows = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT * FROM {$table_horarios}
                 WHERE unidade = %s AND dia_semana = %s",
                    $unit,
                    $dia_semana
                )
            );

            if (!$rows) {
                continue;

            }
            // ---- BUSCA AGENDAMENTOS DO CRM ----
            $unitTemp = preg_replace('/[{}]/', '', $unit);
            $crm_agendamentos = $api->get_agendamentos_by_unidade_and_data($unitTemp, $date_str);
            $intervalos_ocupados = [];
            if (!is_wp_error($crm_agendamentos) && isset($crm_agendamentos['result'])) {
                $agendamentos = $crm_agendamentos['result'];

                // 🔥 Se veio só 1 registro (objeto), transforma em array de 1 item
                if (isset($agendamentos['activityid'])) {
                    $agendamentos = [$agendamentos];
                }
                foreach ($agendamentos as $ag) {
                    // Debug certo agora
                    // if (empty($ag['scheduledstart']) || empty($ag['scheduledend'])) {
                    //     continue;
                    // }

                    array_push(
                        $intervalos_ocupados,
                        [
                            'start' => new DateTime($ag['scheduledstart']),
                            'end' => new DateTime($ag['scheduledend']),
                        ]
                    );
                }
            }
            // ---- GERA SLOTS DISPONÍVEIS ----
            $slots = [];
            foreach ($rows as $row) {
                $tz = new DateTimeZone('America/Sao_Paulo');
                $start = DateTime::createFromFormat(
                    'Y-m-d H:i:s',
                    $date_str . ' ' . $row->hora_inicio,
                    $tz
                );
                $end = DateTime::createFromFormat(
                    'Y-m-d H:i:s',
                    $date_str . ' ' . $row->hora_fim,
                    $tz
                );
                if (!$start || !$end) {
                    continue;
                }
                $duracao = max(15, intval($row->duracao_visita_minutos ?: 120));
                $current = clone $start;
                while (true) {
                    $slot_start = clone $current;
                    $slot_end = (clone $slot_start)->add(
                        new DateInterval('PT' . $duracao . 'M')
                    );
                    if ($slot_end > $end) {
                        break;
                    }
                    $colide = false;
                    foreach ($intervalos_ocupados as $ocupado) {
                        if (
                            $slot_start < $ocupado['end'] &&
                            $slot_end > $ocupado['start']
                        ) {
                            $colide = true;
                            break;
                        }
                    }
                    if (!$colide) {
                        $slots[] = $slot_start->format('H:i');
                    }
                    $current = $slot_end;
                }
            }


            if (!empty($slots)) {
                $agendados_locais = $wpdb->get_col(
                    $wpdb->prepare(
                        "SELECT hora_agendamento FROM {$table_agenda}
                        WHERE data_agendamento = %s AND unidade_id = %s AND status IN ('pendente','enviado','cancelado')",
                        $date_str,
                        $unit
                    )
                );

                $agendados_locais = array_map(function ($hora) {
                    return substr($hora, 0, 5);
                }, $agendados_locais);

                $slots = array_values(array_diff($slots, $agendados_locais));
            }

            if (!empty($slots)) {
                sort($slots);
                $found[] = [
                    'date' => $date_str,
                    'weekday' => $dia_semana,
                    'times' => array_values(array_unique($slots)),
                ];
            }
        }

        if (empty($found)) {
            wp_send_json_error(['message' => 'Nenhuma data disponível encontrada.']);
        }

        wp_send_json_success([
            'dates' => $found
        ]);
    }


    /**
     * Endpoint AJAX responsável por retornar os horários disponíveis
     * para uma data específica e unidade selecionada.
     *
     * Este método:
     * - Identifica o dia da semana com base na data informada;
     * - Busca os intervalos de funcionamento configurados no painel administrativo;
     * - Gera os slots de horários respeitando a duração da visita definida no admin;
     * - Consulta os agendamentos já realizados no CRM Educacional Positivo;
     * - Remove automaticamente os horários que colidem com agendamentos existentes;
     * - Retorna apenas os horários realmente disponíveis para novo agendamento.
     *
     * A fonte de verdade para bloqueio de horários é o CRM, garantindo
     * consistência entre o sistema WordPress e o CRM externo.
     *
     * Retorno (JSON):
     * {
     *   success: true,
     *   data: {
     *     times: ["08:00", "09:30", "11:00"]
     *   }
     * }
     */
    public function ajax_get_times()
    {

        Positivo_CRM_Logger::debug('AJAX callback: ajax_get_times', ['request_keys' => array_keys($_POST)]);
        $this->require_public_ajax_nonce();
        $date = sanitize_text_field($_POST['date'] ?? '');
        $unit = sanitize_text_field($_POST['unit'] ?? '');

        if (!$date) {
            wp_send_json_error(['message' => 'Data não fornecida.']);
        }
        // ----- IDENTIFICAR DIA DA SEMANA -----
        $dt = DateTime::createFromFormat('Y-m-d', $date);
        if (!$dt) {
            wp_send_json_error(['message' => 'Formato de data inválido.']);
        }
        $weekday_en = strtolower($dt->format('l'));
        $dias_map = [
            'monday' => 'segunda',
            'tuesday' => 'terca',
            'wednesday' => 'quarta',
            'thursday' => 'quinta',
            'friday' => 'sexta',
            'saturday' => 'sabado',
            'sunday' => 'domingo',
        ];
        $dia_semana = $dias_map[$weekday_en] ?? '';
        global $wpdb;
        $table_horarios = $wpdb->prefix . 'positivo_unidade_horarios';
        $table_agenda = $wpdb->prefix . 'positivo_agendamentos';
        // ----- BUSCAR HORÁRIOS DA UNIDADE -----
        if (!empty($unit)) {
            $rows = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT hora_inicio, hora_fim, duracao_visita_minutos FROM {$table_horarios}
                    WHERE unidade = %s AND dia_semana = %s",
                    $unit,
                    $dia_semana
                )
            );
        } else {
            $rows = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT hora_inicio, hora_fim, duracao_visita_minutos FROM {$table_horarios}
                    WHERE dia_semana = %s",
                    $dia_semana
                )
            );
        }
        if (!$rows) {
            wp_send_json_error(['message' => 'Nenhum horário configurado para este dia.']);
        }
        // ----- AGENDAMENTOS OCUPADOS (CRM) -----
        $api = new Positivo_CRM_API();
        $crm_agendamentos = $api->get_agendamentos_by_unidade_and_data($unit, $date);
        $intervalos_ocupados = [];
        if (
            !is_wp_error($crm_agendamentos) &&
            isset($crm_agendamentos['result'])
        ) {
            // 🔥 NORMALIZA: se vier objeto único, vira array
            $agendamentos = $crm_agendamentos['result'];

            if (isset($agendamentos['activityid'])) {
                $agendamentos = [$agendamentos];
            }

            foreach ($agendamentos as $ag) {

                if (
                    empty($ag['scheduledstart']['#text']) ||
                    empty($ag['scheduledend']['#text'])
                ) {
                    continue;
                }

                try {
                    $intervalos_ocupados[] = [
                        'start' => new DateTime($ag['scheduledstart']['#text']),
                        'end' => new DateTime($ag['scheduledend']['#text']),
                    ];
                } catch (Exception $e) {
                    // opcional: log
                }
            }
        }

        // ----- GERAR HORÁRIOS DISPONÍVEIS -----
        $available_times = [];
        foreach ($rows as $row) {
            $start = DateTime::createFromFormat('H:i:s', $row->hora_inicio);
            $end = DateTime::createFromFormat('H:i:s', $row->hora_fim);
            if (!$start || !$end) {
                continue;
            }
            $duracao = max(15, intval($row->duracao_visita_minutos ?: 120));
            $current = clone $start;
            while (true) {
                $slot_start = clone $current;
                $slot_end = (clone $slot_start)->add(
                    new DateInterval('PT' . $duracao . 'M')
                );
                if ($slot_end > $end) {
                    break;
                }
                // 🔥 VERIFICA COLISÃO COM CRM
                $colide = false;
                foreach ($intervalos_ocupados as $ocupado) {
                    if (
                        $slot_start < $ocupado['end'] &&
                        $slot_end > $ocupado['start']
                    ) {
                        $colide = true;
                        break;
                    }
                }
                if (!$colide) {
                    $available_times[] = $slot_start->format('H:i');
                }
                $current = $slot_end;
            }
        }
        // ----- BUSCAR AGENDAMENTOS EXISTENTES -----
        $agendados = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT hora_agendamento FROM {$table_agenda}
                WHERE data_agendamento = %s AND unidade_id = %s AND status IN ('pendente','enviado','cancelado')",
                $date,
                $unit
            )
        );
        // Normaliza horas: remove segundos caso existam
        $agendados = array_map(function ($h) {
            return substr($h, 0, 5);
        }, $agendados);
        // ----- UNIFICAR HORÁRIOS OCUPADOS (CRM + LOCAL) -----
        $horarios_final = array_values(
            array_diff($available_times, $agendados)
        );
        if (empty($horarios_final)) {
            wp_send_json_error(['message' => 'Nenhum horário disponível.']);
        }
        wp_send_json_success(['times' => $horarios_final]);
    }


    /**
     * Cria ou atualiza as tabelas utilizadas pelo plugin.
     *
     * Este método utiliza dbDelta, que compara a estrutura desejada com a
     * existente e executa as alterações necessárias. Ele deve ser chamado
     * durante a ativação do plugin.
     */
    public static function install_tables()
    {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $charset_collate = $wpdb->get_charset_collate();

        $agendamentos_table = $wpdb->prefix . 'positivo_agendamentos';
        $horarios_table = $wpdb->prefix . 'positivo_unidade_horarios';

        $sql_agendamentos = "CREATE TABLE {$agendamentos_table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            responsavel_nome varchar(100) NOT NULL,
            responsavel_sobrenome varchar(100) NOT NULL,
            responsavel_email varchar(255) NOT NULL,
            responsavel_telefone varchar(20) NOT NULL,
            responsavel_serie_interesse varchar(255) DEFAULT NULL,
            responsavel_serie_id varchar(255) DEFAULT NULL,
            responsavel_como_conheceu int(11) DEFAULT NULL,
            aluno_nome varchar(100) NOT NULL,
            aluno_sobrenome varchar(100) NOT NULL,
            aluno_serie_interesse varchar(255) NOT NULL,
            aluno_serie_id varchar(255) DEFAULT NULL,
            aluno_ano_interesse int(11) NOT NULL,
            aluno_escola_origem varchar(255) DEFAULT NULL,
            unidade_id varchar(255) NOT NULL,
            unidade_nome varchar(255) DEFAULT NULL,
            servico varchar(255) DEFAULT NULL,
            recurso varchar(255) DEFAULT NULL,
            data_agendamento date NOT NULL,
            hora_agendamento time NOT NULL,
            duracao_minutos int(11) DEFAULT 120,
            status varchar(50) DEFAULT 'pendente',
            enviado_crm tinyint(1) DEFAULT 0,
            data_envio_crm datetime DEFAULT NULL,
            lead_id varchar(255) DEFAULT NULL,
            atividade_id varchar(255) DEFAULT NULL,
            erro_envio text DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            created_by bigint(20) DEFAULT NULL,
            PRIMARY KEY  (id),
            KEY idx_email (responsavel_email),
            KEY idx_unidade (unidade_id),
            KEY idx_data (data_agendamento),
            KEY idx_status (status),
            KEY idx_enviado (enviado_crm)
        ) {$charset_collate};";

        $sql_horarios = "CREATE TABLE {$horarios_table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            unidade varchar(255) NOT NULL,
            dia_semana varchar(20) NOT NULL,
            duracao_visita_minutos int(11) DEFAULT 120,
            hora_inicio time NOT NULL,
            hora_fim time NOT NULL,
            PRIMARY KEY  (id)
        ) {$charset_collate};";

        dbDelta($sql_agendamentos);
        dbDelta($sql_horarios);

        // Executar migração para adicionar colunas faltantes em tabelas existentes
        self::migrate_agendamentos_table();
        update_option('positivo_crm_db_version', POSITIVO_CRM_DB_VERSION);
    }

    /**
     * Migra a tabela de agendamentos adicionando colunas faltantes
     * 
     * Esta função verifica se as colunas necessárias existem e as adiciona se necessário.
     * É executada automaticamente durante a ativação/atualização do plugin.
     */
    private static function migrate_agendamentos_table()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'positivo_agendamentos';

        // Verificar se a tabela existe
        if ($wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") != $table_name) {
            return; // Tabela não existe, será criada pelo dbDelta
        }

        // Lista de colunas que precisam existir
        $required_columns = array(
            'responsavel_sobrenome' => "ALTER TABLE {$table_name} ADD COLUMN responsavel_sobrenome varchar(100) NOT NULL AFTER responsavel_nome",
            'responsavel_email' => "ALTER TABLE {$table_name} ADD COLUMN responsavel_email varchar(255) NOT NULL AFTER responsavel_sobrenome",
            'responsavel_telefone' => "ALTER TABLE {$table_name} ADD COLUMN responsavel_telefone varchar(20) NOT NULL AFTER responsavel_email",
            'responsavel_serie_interesse' => "ALTER TABLE {$table_name} ADD COLUMN responsavel_serie_interesse varchar(255) DEFAULT NULL AFTER responsavel_telefone",
            'responsavel_serie_id' => "ALTER TABLE {$table_name} ADD COLUMN responsavel_serie_id varchar(255) DEFAULT NULL AFTER responsavel_serie_interesse",
            'responsavel_como_conheceu' => "ALTER TABLE {$table_name} ADD COLUMN responsavel_como_conheceu int(11) DEFAULT NULL AFTER responsavel_serie_id",
            'aluno_sobrenome' => "ALTER TABLE {$table_name} ADD COLUMN aluno_sobrenome varchar(100) NOT NULL AFTER aluno_nome",
            'aluno_serie_interesse' => "ALTER TABLE {$table_name} ADD COLUMN aluno_serie_interesse varchar(255) NOT NULL AFTER aluno_sobrenome",
            'aluno_serie_id' => "ALTER TABLE {$table_name} ADD COLUMN aluno_serie_id varchar(255) DEFAULT NULL AFTER aluno_serie_interesse",
            'aluno_ano_interesse' => "ALTER TABLE {$table_name} ADD COLUMN aluno_ano_interesse int(11) NOT NULL AFTER aluno_serie_id",
            'aluno_escola_origem' => "ALTER TABLE {$table_name} ADD COLUMN aluno_escola_origem varchar(255) DEFAULT NULL AFTER aluno_ano_interesse",
            'unidade_id' => "ALTER TABLE {$table_name} ADD COLUMN unidade_id varchar(255) NOT NULL AFTER aluno_escola_origem",
            'unidade_nome' => "ALTER TABLE {$table_name} ADD COLUMN unidade_nome varchar(255) DEFAULT NULL AFTER unidade_id",
            'servico' => "ALTER TABLE {$table_name} ADD COLUMN servico varchar(255) DEFAULT NULL AFTER unidade_nome",
            'recurso' => "ALTER TABLE {$table_name} ADD COLUMN recurso varchar(255) DEFAULT NULL AFTER servico",
            'hora_agendamento' => "ALTER TABLE {$table_name} ADD COLUMN hora_agendamento time NOT NULL AFTER data_agendamento",
            'duracao_minutos' => "ALTER TABLE {$table_name} ADD COLUMN duracao_minutos int(11) DEFAULT 120 AFTER hora_agendamento",
            'status' => "ALTER TABLE {$table_name} ADD COLUMN status varchar(50) DEFAULT 'pendente' AFTER duracao_minutos",
            'enviado_crm' => "ALTER TABLE {$table_name} ADD COLUMN enviado_crm tinyint(1) DEFAULT 0 AFTER status",
            'data_envio_crm' => "ALTER TABLE {$table_name} ADD COLUMN data_envio_crm datetime DEFAULT NULL AFTER enviado_crm",
            'lead_id' => "ALTER TABLE {$table_name} ADD COLUMN lead_id varchar(255) DEFAULT NULL AFTER data_envio_crm",
            'atividade_id' => "ALTER TABLE {$table_name} ADD COLUMN atividade_id varchar(255) DEFAULT NULL AFTER lead_id",
            'erro_envio' => "ALTER TABLE {$table_name} ADD COLUMN erro_envio text DEFAULT NULL AFTER atividade_id",
            'updated_at' => "ALTER TABLE {$table_name} ADD COLUMN updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at",
            'created_by' => "ALTER TABLE {$table_name} ADD COLUMN created_by bigint(20) DEFAULT NULL AFTER updated_at"
        );

        // Obter colunas existentes
        $existing_columns = array();
        $columns = $wpdb->get_results("SHOW COLUMNS FROM {$table_name}");
        foreach ($columns as $column) {
            $existing_columns[] = $column->Field;
        }

        // Adicionar colunas faltantes
        foreach ($required_columns as $column_name => $sql) {
            if (!in_array($column_name, $existing_columns)) {
                $wpdb->query($sql);
            }
        }

        // Adicionar índices se não existirem
        $indexes = array(
            'idx_email' => "ALTER TABLE {$table_name} ADD INDEX idx_email (responsavel_email)",
            'idx_unidade' => "ALTER TABLE {$table_name} ADD INDEX idx_unidade (unidade_id)",
            'idx_data' => "ALTER TABLE {$table_name} ADD INDEX idx_data (data_agendamento)",
            'idx_status' => "ALTER TABLE {$table_name} ADD INDEX idx_status (status)",
            'idx_enviado' => "ALTER TABLE {$table_name} ADD INDEX idx_enviado (enviado_crm)"
        );

        $existing_indexes = array();
        $index_results = $wpdb->get_results("SHOW INDEX FROM {$table_name}");
        foreach ($index_results as $index) {
            $existing_indexes[] = $index->Key_name;
        }

        foreach ($indexes as $index_name => $sql) {
            if (!in_array($index_name, $existing_indexes)) {
                $wpdb->query($sql);
            }
        }
    }

    /**
     * Garante que a coluna duracao_visita_minutos exista na tabela de horários
     */
    private function ensure_duracao_visita_column()
    {
        global $wpdb;
        $table = $wpdb->prefix . 'positivo_unidade_horarios';
        // Verifica se a tabela existe
        $table_exists = $wpdb->get_var(
            $wpdb->prepare("SHOW TABLES LIKE %s", $table)
        );
        if ($table_exists !== $table) {
            return;
        }
        // Verifica se a coluna existe
        $column_exists = $wpdb->get_var(
            $wpdb->prepare(
                "SHOW COLUMNS FROM {$table} LIKE %s",
                'duracao_visita_minutos'
            )
        );
        if (empty($column_exists)) {
            $wpdb->query("
                ALTER TABLE {$table}
                ADD COLUMN duracao_visita_minutos INT(11) DEFAULT 120
                AFTER dia_semana
            ");
            Positivo_CRM_Logger::info('Coluna duracao_visita_minutos adicionada na tabela de horários');
        }
    }


    /**
     * Registra o menu principal e submenus para o plugin.
     */
    public function register_admin_menu()
    {
        // Menu principal
        add_menu_page(
            __('Positivo CRM', 'positivo-crm'),
            __('Positivo CRM', 'positivo-crm'),
            'manage_options',
            'positivo_crm',
            array($this, 'settings_page'),
            'dashicons-calendar-alt',
            56
        );
        // Submenus
        add_submenu_page(
            'positivo_crm',
            __('Configurações', 'positivo-crm'),
            __('Configurações', 'positivo-crm'),
            'manage_options',
            'positivo_crm',
            array($this, 'settings_page')
        );
        add_submenu_page(
            'positivo_crm',
            __('Agendamentos', 'positivo-crm'),
            __('Agendamentos', 'positivo-crm'),
            'manage_options',
            'positivo_crm_agendamentos',
            array($this, 'agendamentos_page')
        );
        add_submenu_page(
            'positivo_crm',
            __('Horários de Unidades', 'positivo-crm'),
            __('Horários', 'positivo-crm'),
            'manage_options',
            'positivo_crm_horarios',
            array($this, 'horarios_page')
        );
        add_submenu_page(
            'positivo_crm',
            __('Logs de Debug', 'positivo-crm'),
            __('Logs', 'positivo-crm'),
            'manage_options',
            'positivo-crm-logs',
            array($this, 'logs_page')
        );
    }

    /**
     * Registra configurações e campos de opções.
     */
    public function register_settings()
    {
        register_setting('positivo_crm_settings', 'positivo_crm_options', array($this, 'sanitize_options'));

        // Seção de credenciais
        add_settings_section(
            'positivo_crm_api_section',
            __('Credenciais da API', 'positivo-crm'),
            function () {
                echo '<p>' . esc_html__('Informe as credenciais de acesso à API do CRM Educacional.', 'positivo-crm') . '</p>';
            },
            'positivo_crm_settings'
        );
        // Campos de usuário, senha e tipo de autenticação
        add_settings_field(
            'crm_username',
            __('Usuário', 'positivo-crm'),
            array($this, 'text_field_callback'),
            'positivo_crm_settings',
            'positivo_crm_api_section',
            array('label_for' => 'crm_username', 'option_key' => 'crm_username')
        );
        add_settings_field(
            'crm_password',
            __('Senha', 'positivo-crm'),
            array($this, 'password_field_callback'),
            'positivo_crm_settings',
            'positivo_crm_api_section',
            array('label_for' => 'crm_password', 'option_key' => 'crm_password')
        );

        add_settings_field(
            'crm_auth_type',
            __('Tipo de Autenticação', 'positivo-crm'),
            array($this, 'text_field_callback'),
            'positivo_crm_settings',
            'positivo_crm_api_section',
            array('label_for' => 'crm_auth_type', 'option_key' => 'crm_auth_type')
        );

        // Campo para definir a rota principal (PROTECTED_PATH)
        add_settings_field(
            'protected_path',
            __('Rota Principal (PROTECTED_PATH)', 'positivo-crm'),
            array($this, 'text_field_callback'),
            'positivo_crm_settings',
            'positivo_crm_api_section',
            array('label_for' => 'protected_path', 'option_key' => 'protected_path')
        );

        // Seção de FetchXML
        add_settings_section(
            'positivo_crm_fetch_section',
            __('Requisições FetchXML', 'positivo-crm'),
            function () {
                echo '<p>' . esc_html__('Defina abaixo os corpos das requisições FetchXML utilizadas pela integração. Ajuste conforme necessário.', 'positivo-crm') . '</p>';
            },
            'positivo_crm_settings'
        );
        // Campos para as requisições FetchXML (Unidades, Responsável, Aluno, Agendamento)
        $fetch_fields = array(
            'fetch_xml_unidades' => __('Fetch Consulta Unidades', 'positivo-crm'),
            'fetch_xml_responsavel' => __('Fetch Consulta Responsável', 'positivo-crm'),
            'fetch_xml_aluno' => __('Fetch Consulta Aluno(s)', 'positivo-crm'),
            'fetch_xml_series' => __('Fetch Consulta Séries Escolar', 'positivo-crm'),
            'fetch_xml_agendamento' => __('Fetch Criação de Agendamento', 'positivo-crm'),
        );

        // Para cada FetchXML, adiciona o campo de texto e o campo de método correspondente
        foreach ($fetch_fields as $key => $label) {
            // Campo do corpo do FetchXML
            add_settings_field(
                $key,
                $label,
                array($this, 'textarea_field_callback'),
                'positivo_crm_settings',
                'positivo_crm_fetch_section',
                array('label_for' => $key, 'option_key' => $key)
            );
            // Determina o nome do campo de método correspondente
            // Ex: fetch_xml_unidades -> method_fetch_unidades
            $method_key = 'method_' . str_replace('fetch_xml_', '', $key);
            $method_label = sprintf(__('Método %s', 'positivo-crm'), $label);
            // Campo de seleção do método
            add_settings_field(
                $method_key,
                $method_label,
                array($this, 'method_field_callback'),
                'positivo_crm_settings',
                'positivo_crm_fetch_section',
                array('label_for' => $method_key, 'option_key' => $method_key)
            );
        }

        // Seção de HTML do formulário
        add_settings_section(
            'positivo_crm_html_section',
            __('Template do Formulário', 'positivo-crm'),
            function () {
                echo '<p>' . esc_html__('Edite abaixo o HTML que será exibido no formulário de agendamento. Use com cuidado e mantenha a estrutura do formulário.', 'positivo-crm') . '</p>';
            },
            'positivo_crm_settings'
        );
        add_settings_field(
            'html_template',
            __('HTML do Formulário', 'positivo-crm'),
            array($this, 'html_template_callback'),
            'positivo_crm_settings',
            'positivo_crm_html_section',
            array('label_for' => 'html_template', 'option_key' => 'html_template')
        );
        // Seção de Debug
        add_settings_section(
            'positivo_crm_debug_section',
            __('Configurações de Debug', 'positivo-crm'),
            function () {
                echo '<p>' . esc_html__('Configure as opções de debug e logging do plugin.', 'positivo-crm') . '</p>';
            },
            'positivo_crm_settings'
        );

        add_settings_field(
            'enable_debug',
            __('Ativar Debug', 'positivo-crm'),
            array($this, 'checkbox_field_callback'),
            'positivo_crm_settings',
            'positivo_crm_debug_section',
            array(
                'label_for' => 'enable_debug',
                'option_key' => 'enable_debug',
                'description' => __('Ativa o registro detalhado de logs para diagnóstico de problemas. Os logs podem ser visualizados em Positivo CRM > Logs.', 'positivo-crm')
            )
        );


    }

    /**
     * Sanitiza as opções antes de salvá-las.
     *
     * @param array $input Dados submetidos pelo formulário.
     * @return array Dados sanitizados.
     */
    public function sanitize_options($input)
    {
        $options = get_option('positivo_crm_options', array());
        $options['crm_username'] = isset($input['crm_username']) ? sanitize_text_field($input['crm_username']) : '';
        $options['crm_password'] = isset($input['crm_password']) ? sanitize_text_field($input['crm_password']) : '';
        $options['crm_auth_type'] = isset($input['crm_auth_type']) ? sanitize_text_field($input['crm_auth_type']) : '';
        // Campo de rota principal
        $options['protected_path'] = isset($input['protected_path']) ? sanitize_text_field($input['protected_path']) : '';
        /*
         * Os campos de FetchXML não devem ser sanitizados com funções que removem tags
         * HTML, pois o conteúdo é XML e precisa preservar as tags <fetch>, <entity>, etc.
         * Apenas remove slashes adicionados pelo WP e salva o valor bruto.
         */
        $fetch_keys = array('fetch_xml_unidades', 'fetch_xml_responsavel', 'fetch_xml_aluno', 'fetch_xml_series', 'fetch_xml_agendamento');
        foreach ($fetch_keys as $key) {
            if (isset($input[$key])) {
                // wp_unslash remove as barras adicionadas magicamente ao enviar via POST
                $options[$key] = wp_unslash($input[$key]);
            } else {
                $options[$key] = '';
            }
        }

        // Campos de método para cada FetchXML. Apenas aceita GET ou POST; default GET
        // Novas chaves de método, pares dos campos FetchXML
        $method_keys = array('method_unidades', 'method_responsavel', 'method_aluno', 'method_series', 'method_agendamento');
        foreach ($method_keys as $mkey) {
            $method_value = isset($input[$mkey]) ? $input[$mkey] : 'GET';
            $method_value = strtoupper($method_value);
            if (!in_array($method_value, array('GET', 'POST'), true)) {
                $method_value = 'GET';
            }
            $options[$mkey] = $method_value;
        }

        // O HTML do formulário deve permitir tags HTML comuns. Usamos wp_kses_post.
        $options['html_template'] = isset($input['html_template']) ? wp_kses_post($input['html_template']) : '';

        // Checkbox de debug: se marcado, vem como '1'; se desmarcado, não vem no POST
        $options['enable_debug'] = isset($input['enable_debug']) && $input['enable_debug'] === '1' ? '1' : '0';

        return $options;
    }

    /**
     * Callback para campos de texto simples.
     *
     * @param array $args Argumentos passados pelo add_settings_field.
     */
    public function text_field_callback($args)
    {
        $options = get_option('positivo_crm_options', array());
        $key = $args['option_key'];
        $value = isset($options[$key]) ? esc_attr($options[$key]) : '';
        printf('<input type="text" id="%1$s" name="positivo_crm_options[%1$s]" value="%2$s" class="regular-text"/>', esc_attr($key), $value);
    }

    /**
     * Callback para campos de seleção de método HTTP (GET/POST) usados nas requisições FetchXML.
     *
     * @param array $args Argumentos passados pelo add_settings_field.
     */
    public function method_field_callback($args)
    {
        $options = get_option('positivo_crm_options', array());
        $key = $args['option_key'];
        // Valor atual salvo; default para GET se não definido ou valor inválido
        $value = isset($options[$key]) ? $options[$key] : 'GET';
        if (!in_array($value, array('GET', 'POST'), true)) {
            $value = 'GET';
        }
        $select = sprintf('<select id="%1$s" name="positivo_crm_options[%1$s]">', esc_attr($key));
        $select .= sprintf('<option value="GET" %s>%s</option>', selected($value, 'GET', false), esc_html__('GET', 'positivo-crm'));
        $select .= sprintf('<option value="POST" %s>%s</option>', selected($value, 'POST', false), esc_html__('POST', 'positivo-crm'));
        $select .= '</select>';
        echo $select;
    }

    /**
     * Callback para campos de checkbox.
     *
     * @param array $args Argumentos passados pelo add_settings_field.
     */
    public function checkbox_field_callback($args)
    {
        $options = get_option('positivo_crm_options', array());
        $key = $args['option_key'];
        $value = isset($options[$key]) ? $options[$key] : '0';
        $checked = ($value === '1') ? 'checked' : '';
        $description = isset($args['description']) ? $args['description'] : '';

        printf(
            '<label><input type="checkbox" id="%1$s" name="positivo_crm_options[%1$s]" value="1" %2$s /> %3$s</label>',
            esc_attr($key),
            $checked,
            esc_html($description)
        );
    }

    /**
     * Callback para campo de senha.
     *
     * @param array $args Argumentos passados pelo add_settings_field.
     */
    public function password_field_callback($args)
    {
        $options = get_option('positivo_crm_options', array());
        $key = $args['option_key'];
        $value = isset($options[$key]) ? esc_attr($options[$key]) : '';
        printf('<input type="password" id="%1$s" name="positivo_crm_options[%1$s]" value="%2$s" class="regular-text"/>', esc_attr($key), $value);
    }

    /**
     * Callback para campos de área de texto (FetchXML).
     *
     * @param array $args Argumentos passados pelo add_settings_field.
     */
    public function textarea_field_callback($args)
    {
        $options = get_option('positivo_crm_options', array());
        $key = $args['option_key'];
        $value = isset($options[$key]) ? esc_textarea($options[$key]) : '';
        printf('<textarea id="%1$s" name="positivo_crm_options[%1$s]" rows="6" cols="60" class="large-text">%2$s</textarea>', esc_attr($key), $value);
    }

    /**
     * Callback para o campo de template HTML.
     *
     * @param array $args Argumentos passados pelo add_settings_field.
     */
    public function html_template_callback($args)
    {
        $options = get_option('positivo_crm_options', array());
        $key = $args['option_key'];
        $value = isset($options[$key]) ? esc_textarea($options[$key]) : '';
        printf('<textarea id="%1$s" name="positivo_crm_options[%1$s]" rows="12" cols="60" class="large-text code">%2$s</textarea>', esc_attr($key), $value);
        echo '<p class="description">' . esc_html__('Use este campo para personalizar o HTML do formulário. Mantenha os identificadores e classes existentes para garantir a funcionalidade.', 'positivo-crm') . '</p>';
    }

    /**
     * Página de configurações do plugin.
     */
    public function settings_page()
    {
        if (!current_user_can('manage_options')) {
            return;
        }
        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('Configurações do Positivo CRM', 'positivo-crm') . '</h1>';
        echo '<form method="post" action="options.php">';
        settings_fields('positivo_crm_settings');
        do_settings_sections('positivo_crm_settings');
        submit_button();
        echo '</form>';

        // Seção de testes
        echo '<h2>' . esc_html__('Testes de Integração', 'positivo-crm') . '</h2>';
        echo '<p>' . esc_html__('Utilize os botões abaixo para validar as diferentes operações da API. Os resultados serão exibidos abaixo de cada ação.', 'positivo-crm') . '</p>';

        // Teste de Token
        echo '<div class="positivo-crm-test-section" style="margin-bottom:20px;">';
        echo '<h3>' . esc_html__('Gerar Token', 'positivo-crm') . '</h3>';
        echo '<p>' . esc_html__('Clique para obter um novo token de acesso usando as credenciais informadas.', 'positivo-crm') . '</p>';
        echo '<button type="button" class="button" id="positivo-crm-test-token">' . esc_html__('Testar Token', 'positivo-crm') . '</button>';
        echo '<pre id="positivo-crm-test-token-result" style="background:#f1f1f1;border:1px solid #ddd;padding:1em;margin-top:1em;max-height:300px;overflow:auto;"></pre>';
        echo '</div>';

        // Teste de Unidades
        echo '<div class="positivo-crm-test-section" style="margin-bottom:20px;">';
        echo '<h3>' . esc_html__('Consultar Unidades', 'positivo-crm') . '</h3>';
        echo '<p>' . esc_html__('Executa a consulta de unidades utilizando o FetchXML configurado.', 'positivo-crm') . '</p>';
        echo '<button type="button" class="button" id="positivo-crm-test-unidades">' . esc_html__('Testar Unidades', 'positivo-crm') . '</button>';
        echo '<pre id="positivo-crm-test-unidades-result" style="background:#f1f1f1;border:1px solid #ddd;padding:1em;margin-top:1em;max-height:300px;overflow:auto;"></pre>';
        echo '</div>';

        // Teste de Responsável
        echo '<div class="positivo-crm-test-section" style="margin-bottom:20px;">';
        echo '<h3>' . esc_html__('Buscar Responsável', 'positivo-crm') . '</h3>';
        echo '<p>' . esc_html__('Informe um nome para buscar o responsável.', 'positivo-crm') . '</p>';
        echo '<input type="text" id="positivo-crm-test-responsavel-name" class="regular-text" placeholder="Nome do responsável" style="margin-right:10px;" />';
        echo '<button type="button" class="button" id="positivo-crm-test-responsavel">' . esc_html__('Testar Responsável', 'positivo-crm') . '</button>';
        echo '<pre id="positivo-crm-test-responsavel-result" style="background:#f1f1f1;border:1px solid #ddd;padding:1em;margin-top:1em;max-height:300px;overflow:auto;"></pre>';
        echo '</div>';

        // Teste de Alunos
        echo '<div class="positivo-crm-test-section" style="margin-bottom:20px;">';
        echo '<h3>' . esc_html__('Buscar Aluno(s)', 'positivo-crm') . '</h3>';
        echo '<p>' . esc_html__('Informe o ID do responsável (leadid) para buscar os alunos associados.', 'positivo-crm') . '</p>';
        echo '<input type="text" id="positivo-crm-test-alunos-id" class="regular-text" placeholder="ID do responsável" style="margin-right:10px;" />';
        echo '<button type="button" class="button" id="positivo-crm-test-alunos">' . esc_html__('Testar Alunos', 'positivo-crm') . '</button>';
        echo '<pre id="positivo-crm-test-alunos-result" style="background:#f1f1f1;border:1px solid #ddd;padding:1em;margin-top:1em;max-height:300px;overflow:auto;"></pre>';
        echo '</div>';

        // Teste de Séries Escolar
        echo '<div class="positivo-crm-test-section" style="margin-bottom:20px;">';
        echo '<h3>' . esc_html__('Consultar Séries Escolar', 'positivo-crm') . '</h3>';
        echo '<p>' . esc_html__('Executa a consulta de séries escolares utilizando o FetchXML configurado.', 'positivo-crm') . '</p>';
        echo '<button type="button" class="button" id="positivo-crm-test-series">' . esc_html__('Testar Séries', 'positivo-crm') . '</button>';
        echo '<pre id="positivo-crm-test-series-result" style="background:#f1f1f1;border:1px solid #ddd;padding:1em;margin-top:1em;max-height:300px;overflow:auto;"></pre>';
        echo '</div>';

        // Teste de Criação de Agendamento
        echo '<div class="positivo-crm-test-section" style="margin-bottom:20px;">';
        echo '<h3>' . esc_html__('Criar Agendamento (teste)', 'positivo-crm') . '</h3>';
        echo '<p>' . esc_html__('Cria um agendamento com dados de teste. O agendamento será salvo localmente e não será enviado à API externa.', 'positivo-crm') . '</p>';
        echo '<input type="text" id="positivo-crm-test-agendamento-responsavel" class="regular-text" placeholder="Nome do responsável" style="margin-right:10px;" />';
        echo '<input type="text" id="positivo-crm-test-agendamento-aluno" class="regular-text" placeholder="Nome do aluno" style="margin-right:10px;" />';
        echo '<input type="datetime-local" id="positivo-crm-test-agendamento-data" style="margin-right:10px;" />';
        echo '<button type="button" class="button" id="positivo-crm-test-agendamento">' . esc_html__('Testar Agendamento', 'positivo-crm') . '</button>';
        echo '<pre id="positivo-crm-test-agendamento-result" style="background:#f1f1f1;border:1px solid #ddd;padding:1em;margin-top:1em;max-height:300px;overflow:auto;"></pre>';
        echo '</div>';

        echo '</div>';
    }

    /**
     * Página administrativa que lista e gerencia agendamentos.
     */
    /**
     * Página administrativa completa de Agendamentos
     * Com todos os campos necessários para enviar para a API do CRM
     */

    public function agendamentos_page()
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        global $wpdb;
        $table = $wpdb->prefix . 'positivo_agendamentos';

        // Criar tabela se não existir
        $this->create_agendamentos_table();

        // Manipulação de exclusão
        if (isset($_GET['delete'])) {
            check_admin_referer('delete_agendamento_' . intval($_GET['delete']));
            $delete_id = intval($_GET['delete']);
            $wpdb->delete($table, array('id' => $delete_id), array('%d'));
            echo '<div class="updated notice"><p>' . esc_html__('Agendamento removido com sucesso.', 'positivo-crm') . '</p></div>';
        }

        // Manipulação de envio para CRM
        if (isset($_POST['enviar_crm'])) {
            check_admin_referer('positivo_crm_agendamento_action', 'positivo_crm_agendamento_nonce');
            $agendamento_id = intval($_POST['agendamento_id']);
            $resultado = $this->enviar_agendamento_para_crm($agendamento_id);

            if (is_wp_error($resultado)) {
                echo '<div class="error notice"><p>' . esc_html($resultado->get_error_message()) . '</p></div>';
            } else {
                echo '<div class="updated notice"><p>' . esc_html__('Agendamento enviado para o CRM com sucesso!', 'positivo-crm') . '</p></div>';
            }
        }

        // Manipulação de criação/edição
        if (isset($_POST['submit_agendamento_admin'])) {
            check_admin_referer('positivo_crm_agendamento_action', 'positivo_crm_agendamento_nonce');

            $id = isset($_POST['agendamento_id']) ? intval($_POST['agendamento_id']) : 0;

            // Dados do responsável
            $dados = array(
                'responsavel_nome' => sanitize_text_field($_POST['responsavel_nome']),
                'responsavel_sobrenome' => sanitize_text_field($_POST['responsavel_sobrenome']),
                'responsavel_email' => sanitize_email($_POST['responsavel_email']),
                'responsavel_telefone' => sanitize_text_field($_POST['responsavel_telefone']),
                'responsavel_serie_interesse' => sanitize_text_field($_POST['responsavel_serie_interesse']),
                'responsavel_serie_id' => isset($_POST['responsavel_serie_id']) ? sanitize_text_field($_POST['responsavel_serie_id']) : '',
                'responsavel_como_conheceu' => intval($_POST['responsavel_como_conheceu']),

                // Dados do aluno
                'aluno_nome' => sanitize_text_field($_POST['aluno_nome']),
                'aluno_sobrenome' => sanitize_text_field($_POST['aluno_sobrenome']),
                'aluno_serie_interesse' => sanitize_text_field($_POST['aluno_serie_interesse']),
                'aluno_serie_id' => isset($_POST['aluno_serie_id']) ? sanitize_text_field($_POST['aluno_serie_id']) : '',
                'aluno_ano_interesse' => intval($_POST['aluno_ano_interesse']),
                'aluno_escola_origem' => sanitize_text_field($_POST['aluno_escola_origem']),

                // Dados da unidade
                'unidade_id' => sanitize_text_field($_POST['unidade_id']),
                'unidade_nome' => sanitize_text_field($_POST['unidade_nome']),

                // Dados do agendamento
                'data_agendamento' => sanitize_text_field($_POST['data_agendamento']),
                'hora_agendamento' => sanitize_text_field($_POST['hora_agendamento']),
                'duracao_minutos' => intval($_POST['duracao_minutos']),

                // Status
                'status' => 'pendente',
                'enviado_crm' => 0,
            );

            $formatos = array(
                '%s',
                '%s',
                '%s',
                '%s',
                '%s',
                '%s',
                '%d',  // responsavel (adicionado serie_id)
                '%s',
                '%s',
                '%s',
                '%s',
                '%d',
                '%s',        // aluno (adicionado serie_id)
                '%s',
                '%s',                                // unidade
                '%s',
                '%s',
                '%d',                          // agendamento
                '%s',
                '%d'                                 // status
            );

            if ($id > 0) {
                // Atualiza
                $wpdb->update($table, $dados, array('id' => $id), $formatos, array('%d'));
                echo '<div class="updated notice"><p>' . esc_html__('Agendamento atualizado com sucesso.', 'positivo-crm') . '</p></div>';
            } else {
                // Insere
                $dados['created_by'] = get_current_user_id();
                $formatos[] = '%d';
                $wpdb->insert($table, $dados, $formatos);
                echo '<div class="updated notice"><p>' . esc_html__('Agendamento criado com sucesso.', 'positivo-crm') . '</p></div>';
            }
        }

        // Obter dados para edição
        $edit_item = null;
        if (isset($_GET['edit'])) {
            $edit_id = intval($_GET['edit']);
            $edit_item = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $edit_id));
        }

        // Lista de agendamentos
        $items = $wpdb->get_results("SELECT * FROM {$table} ORDER BY created_at DESC");

        // Buscar unidades da API para o select
        $api = new Positivo_CRM_API();
        $units_response = $api->get_unidades();
        $unidades = array();
        if (!is_wp_error($units_response) && isset($units_response['result']) && is_array($units_response['result'])) {
            $unidades = $units_response['result'];
        }

        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Agendamentos', 'positivo-crm'); ?></h1>

            <!-- Formulário de Criação/Edição -->
            <h2><?php echo $edit_item ? esc_html__('Editar Agendamento', 'positivo-crm') : esc_html__('Novo Agendamento', 'positivo-crm'); ?>
            </h2>

            <form method="post" action="" id="form-agendamento">
                <?php wp_nonce_field('positivo_crm_agendamento_action', 'positivo_crm_agendamento_nonce'); ?>
                <input type="hidden" name="agendamento_id" value="<?php echo $edit_item ? intval($edit_item->id) : 0; ?>" />

                <table class="form-table">
                    <tbody>
                        <!-- SEÇÃO: DADOS DO RESPONSÁVEL -->
                        <tr>
                            <th colspan="2">
                                <h3><?php esc_html_e('Dados do Responsável', 'positivo-crm'); ?></h3>
                            </th>
                        </tr>

                        <tr>
                            <th><label for="responsavel_nome"><?php esc_html_e('Nome *', 'positivo-crm'); ?></label></th>
                            <td>
                                <input type="text" id="responsavel_nome" name="responsavel_nome" class="regular-text"
                                    value="<?php echo $edit_item ? esc_attr($edit_item->responsavel_nome) : ''; ?>" required />
                            </td>
                        </tr>

                        <tr>
                            <th><label for="responsavel_sobrenome"><?php esc_html_e('Sobrenome *', 'positivo-crm'); ?></label>
                            </th>
                            <td>
                                <input type="text" id="responsavel_sobrenome" name="responsavel_sobrenome" class="regular-text"
                                    value="<?php echo $edit_item ? esc_attr($edit_item->responsavel_sobrenome) : ''; ?>"
                                    required />
                            </td>
                        </tr>

                        <tr>
                            <th><label for="responsavel_email"><?php esc_html_e('E-mail *', 'positivo-crm'); ?></label></th>
                            <td>
                                <input type="email" id="responsavel_email" name="responsavel_email" class="regular-text"
                                    value="<?php echo $edit_item ? esc_attr($edit_item->responsavel_email) : ''; ?>" required />
                                <p class="description"><?php esc_html_e('Usado para buscar o lead no CRM', 'positivo-crm'); ?>
                                </p>
                            </td>
                        </tr>

                        <tr>
                            <th><label for="responsavel_telefone"><?php esc_html_e('Telefone *', 'positivo-crm'); ?></label>
                            </th>
                            <td>
                                <input type="tel" id="responsavel_telefone" name="responsavel_telefone" class="regular-text"
                                    value="<?php echo $edit_item ? esc_attr($edit_item->responsavel_telefone) : ''; ?>"
                                    placeholder="27999999999" required />
                                <p class="description"><?php esc_html_e('Apenas números (DDD + número)', 'positivo-crm'); ?>
                                </p>
                            </td>
                        </tr>

                        <tr>
                            <th><label
                                    for="responsavel_serie_interesse"><?php esc_html_e('Série de Interesse', 'positivo-crm'); ?></label>
                            </th>
                            <td>
                                <select id="responsavel_serie_interesse" name="responsavel_serie_interesse"
                                    class="regular-text serie-select-admin">
                                    <option value=""><?php esc_html_e('Carregando...', 'positivo-crm'); ?></option>
                                </select>
                                <p class="description">
                                    <?php esc_html_e('Selecione a série de interesse do responsável', 'positivo-crm'); ?>
                                </p>
                                <input type="hidden" id="responsavel_serie_id" name="responsavel_serie_id"
                                    value="<?php echo ($edit_item && isset($edit_item->responsavel_serie_id)) ? esc_attr($edit_item->responsavel_serie_id) : ''; ?>" />
                            </td>
                        </tr>

                        <tr>
                            <th><label
                                    for="responsavel_como_conheceu"><?php esc_html_e('Como Conheceu *', 'positivo-crm'); ?></label>
                            </th>
                            <td>
                                <select id="responsavel_como_conheceu" name="responsavel_como_conheceu" class="regular-text"
                                    required>
                                    <option value=""><?php esc_html_e('Selecione...', 'positivo-crm'); ?></option>
                                    <option value="191030009" <?php selected($edit_item ? $edit_item->responsavel_como_conheceu : '', 191030009); ?>>Site/Internet</option>
                                    <option value="191030001" <?php selected($edit_item ? $edit_item->responsavel_como_conheceu : '', 191030001); ?>>Indicação</option>
                                    <option value="191030002" <?php selected($edit_item ? $edit_item->responsavel_como_conheceu : '', 191030002); ?>>Redes Sociais</option>
                                    <option value="191030003" <?php selected($edit_item ? $edit_item->responsavel_como_conheceu : '', 191030003); ?>>Google</option>
                                    <option value="191030004" <?php selected($edit_item ? $edit_item->responsavel_como_conheceu : '', 191030004); ?>>Outdoor</option>
                                    <option value="191030005" <?php selected($edit_item ? $edit_item->responsavel_como_conheceu : '', 191030005); ?>>Rádio/TV</option>
                                </select>
                            </td>
                        </tr>

                        <!-- SEÇÃO: DADOS DO ALUNO -->
                        <tr>
                            <th colspan="2">
                                <h3><?php esc_html_e('Dados do Aluno', 'positivo-crm'); ?></h3>
                            </th>
                        </tr>

                        <tr>
                            <th><label for="aluno_nome"><?php esc_html_e('Nome do Aluno *', 'positivo-crm'); ?></label></th>
                            <td>
                                <input type="text" id="aluno_nome" name="aluno_nome" class="regular-text"
                                    value="<?php echo $edit_item ? esc_attr($edit_item->aluno_nome) : ''; ?>" required />
                            </td>
                        </tr>

                        <tr>
                            <th><label
                                    for="aluno_sobrenome"><?php esc_html_e('Sobrenome do Aluno *', 'positivo-crm'); ?></label>
                            </th>
                            <td>
                                <input type="text" id="aluno_sobrenome" name="aluno_sobrenome" class="regular-text"
                                    value="<?php echo $edit_item ? esc_attr($edit_item->aluno_sobrenome) : ''; ?>" required />
                            </td>
                        </tr>

                        <tr>
                            <th><label
                                    for="aluno_serie_interesse"><?php esc_html_e('Série de Interesse *', 'positivo-crm'); ?></label>
                            </th>
                            <td>
                                <select id="aluno_serie_interesse" name="aluno_serie_interesse"
                                    class="regular-text serie-select-admin">
                                    <option value=""><?php esc_html_e('Carregando...', 'positivo-crm'); ?></option>
                                </select>
                                <p class="description">
                                    <?php esc_html_e('Selecione a série de interesse do aluno', 'positivo-crm'); ?>
                                </p>
                                <input type="hidden" id="aluno_serie_id" name="aluno_serie_id"
                                    value="<?php echo ($edit_item && isset($edit_item->aluno_serie_id)) ? esc_attr($edit_item->aluno_serie_id) : ''; ?>" />
                            </td>
                        </tr>

                        <tr>
                            <th><label
                                    for="aluno_ano_interesse"><?php esc_html_e('Ano de Interesse *', 'positivo-crm'); ?></label>
                            </th>
                            <td>
                                <select id="aluno_ano_interesse" name="aluno_ano_interesse" class="regular-text" required>
                                    <?php
                                    $ano_atual = date('Y');
                                    for ($i = 0; $i <= 2; $i++) {
                                        $ano = $ano_atual + $i;
                                        $selected = $edit_item && intval($edit_item->aluno_ano_interesse) === $ano ? 'selected' : '';
                                        echo '<option value="' . $ano . '" ' . $selected . '>' . $ano . '</option>';
                                    }
                                    ?>
                                </select>
                            </td>
                        </tr>

                        <tr>
                            <th><label
                                    for="aluno_escola_origem"><?php esc_html_e('Escola de Origem', 'positivo-crm'); ?></label>
                            </th>
                            <td>
                                <input type="text" id="aluno_escola_origem" name="aluno_escola_origem" class="regular-text"
                                    value="<?php echo $edit_item ? esc_attr($edit_item->aluno_escola_origem) : ''; ?>"
                                    placeholder="Ex: Escola Municipal ABC" />
                            </td>
                        </tr>

                        <!-- SEÇÃO: DADOS DA UNIDADE E AGENDAMENTO -->
                        <tr>
                            <th colspan="2">
                                <h3><?php esc_html_e('Dados do Agendamento', 'positivo-crm'); ?></h3>
                            </th>
                        </tr>

                        <tr>
                            <th><label for="unidade_id"><?php esc_html_e('Unidade *', 'positivo-crm'); ?></label></th>
                            <td>
                                <select id="unidade_id" name="unidade_id" class="regular-text" required>
                                    <option value=""><?php esc_html_e('Selecione a unidade...', 'positivo-crm'); ?></option>
                                    <?php foreach ($unidades as $unidade): ?>
                                        <?php if (isset($unidade['cad_categoriaid']) && isset($unidade['cad_name'])): ?>
                                            <option value="<?php echo esc_attr($unidade['cad_categoriaid']); ?>"
                                                data-nome="<?php echo esc_attr($unidade['cad_name']); ?>" <?php selected($edit_item ? $edit_item->unidade_id : '', $unidade['cad_categoriaid']); ?>>
                                                <?php echo esc_html($unidade['cad_name']); ?>
                                            </option>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </select>
                                <input type="hidden" id="unidade_nome" name="unidade_nome"
                                    value="<?php echo $edit_item ? esc_attr($edit_item->unidade_nome) : ''; ?>" />
                            </td>
                        </tr>

                        <tr>
                            <th><label for="data_agendamento"><?php esc_html_e('Data *', 'positivo-crm'); ?></label></th>
                            <td>
                                <input type="date" id="data_agendamento" name="data_agendamento" class="regular-text"
                                    value="<?php echo $edit_item ? esc_attr($edit_item->data_agendamento) : ''; ?>" required />
                            </td>
                        </tr>

                        <tr>
                            <th><label for="hora_agendamento"><?php esc_html_e('Horário *', 'positivo-crm'); ?></label></th>
                            <td>
                                <input type="time" id="hora_agendamento" name="hora_agendamento" class="regular-text"
                                    value="<?php echo $edit_item ? esc_attr($edit_item->hora_agendamento) : ''; ?>" required />
                            </td>
                        </tr>

                        <tr>
                            <th><label for="duracao_minutos"><?php esc_html_e('Duração (minutos)', 'positivo-crm'); ?></label>
                            </th>
                            <td>
                                <input type="number" id="duracao_minutos" name="duracao_minutos" class="small-text"
                                    value="<?php echo $edit_item ? intval($edit_item->duracao_minutos) : 60; ?>" min="15"
                                    step="15" />
                                <p class="description"><?php esc_html_e('Duração padrão: 60 minutos', 'positivo-crm'); ?></p>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <?php submit_button($edit_item ? __('Atualizar Agendamento', 'positivo-crm') : __('Criar Agendamento', 'positivo-crm'), 'primary', 'submit_agendamento_admin'); ?>
            </form>

            <script>
                jQuery(document).ready(function ($) {
                    // Atualiza o campo oculto com o nome da unidade quando selecionar
                    $('#unidade_id').on('change', function () {
                        var selectedOption = $(this).find('option:selected');
                        var unidadeNome = selectedOption.data('nome');
                        $('#unidade_nome').val(unidadeNome || '');
                    });

                    // Carrega séries escolares via AJAX
                    function loadSeriesAdmin() {
                        $.ajax({
                            url: ajaxurl,
                            type: 'POST',
                            data: {
                                action: 'positivo_crm_get_series',
                                nonce: '<?php echo wp_create_nonce('positivo-crm-nonce'); ?>'
                            },
                            success: function (response) {
                                if (response.success && response.data) {
                                    var series = [];
                                    var results = response.data.resultset ? response.data.resultset.result : response.data.result;

                                    // Normaliza para array
                                    if (!Array.isArray(results)) {
                                        results = [results];
                                    }

                                    // Processa cada série
                                    results.forEach(function (item) {
                                        if (item.cad_servicoeducacionalid && item.cad_name) {
                                            var id = item.cad_servicoeducacionalid.replace(/[{}]/g, '').toLowerCase();
                                            series.push({
                                                id: id,
                                                name: item.cad_name
                                            });
                                        }
                                    });

                                    // Popula os selects
                                    $('.serie-select-admin').each(function () {
                                        var $select = $(this);
                                        var currentValue = $select.val();
                                        var hiddenFieldId = $select.attr('id').replace('_interesse', '_id');
                                        var savedId = $('#' + hiddenFieldId).val();

                                        // Normaliza o ID salvo (remove chaves e converte para minúsculas)
                                        if (savedId) {
                                            savedId = savedId.replace(/[{}]/g, '').toLowerCase();
                                        }

                                        console.log('=== DEBUG SÉRIE ===');
                                        console.log('Campo:', $select.attr('id'));
                                        console.log('ID salvo (bruto):', $('#' + hiddenFieldId).val());
                                        console.log('ID salvo (normalizado):', savedId);
                                        console.log('Total de séries:', series.length);

                                        $select.empty();
                                        $select.append('<option value="">Selecione...</option>');

                                        series.forEach(function (serie) {
                                            var selected = (savedId && savedId === serie.id) ? 'selected' : '';
                                            // Value do select é o NOME, data-id é o ID
                                            $select.append('<option value="' + serie.name + '" data-id="' + serie.id + '" ' + selected + '>' + serie.name + '</option>');
                                            if (selected) {
                                                console.log('Série selecionada:', serie.name, 'ID:', serie.id);
                                            }
                                        });
                                    });

                                    // Handler para atualizar campo hidden ao selecionar
                                    $('.serie-select-admin').on('change', function () {
                                        var $select = $(this);
                                        var selectedOption = $select.find('option:selected');
                                        var serieId = selectedOption.data('id') || '';
                                        var serieName = $select.val();
                                        var hiddenFieldId = $select.attr('id').replace('_interesse', '_id');
                                        // Atualiza o campo hidden com o ID
                                        $('#' + hiddenFieldId).val(serieId);
                                        console.log('Série alterada:', serieName, 'ID:', serieId);
                                    });
                                } else {
                                    console.error('Erro ao carregar séries:', response);
                                    $('.serie-select-admin').html('<option value="">Erro ao carregar séries</option>');
                                }
                            },
                            error: function (xhr, status, error) {
                                console.error('Erro AJAX ao carregar séries:', error);
                                $('.serie-select-admin').html('<option value="">Erro ao carregar séries</option>');
                            }
                        });
                    }

                    // Carrega séries ao iniciar
                    loadSeriesAdmin();
                });
            </script>

            <!-- Listagem de Agendamentos -->
            <h2><?php esc_html_e('Lista de Agendamentos', 'positivo-crm'); ?></h2>

            <?php if (!empty($items)): ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('ID', 'positivo-crm'); ?></th>
                            <th><?php esc_html_e('Responsável', 'positivo-crm'); ?></th>
                            <th><?php esc_html_e('Aluno', 'positivo-crm'); ?></th>
                            <th><?php esc_html_e('Unidade', 'positivo-crm'); ?></th>
                            <th><?php esc_html_e('Data/Hora', 'positivo-crm'); ?></th>
                            <th><?php esc_html_e('Status', 'positivo-crm'); ?></th>
                            <th><?php esc_html_e('Ações', 'positivo-crm'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item): ?>
                            <tr>
                                <td><?php echo intval($item->id); ?></td>
                                <td><?php echo esc_html($item->responsavel_nome . ' ' . $item->responsavel_sobrenome); ?></td>
                                <td><?php echo esc_html($item->aluno_nome . ' ' . $item->aluno_sobrenome); ?></td>
                                <td><?php echo esc_html($item->unidade_nome); ?></td>
                                <td><?php echo esc_html(date('d/m/Y', strtotime($item->data_agendamento)) . ' ' . $item->hora_agendamento); ?>
                                </td>
                                <td>
                                    <?php
                                    $status_labels = array(
                                        'pendente' => '<span style="color: orange;">●</span> Pendente',
                                        'enviado' => '<span style="color: green;">●</span> Enviado',
                                        'erro' => '<span style="color: red;">●</span> Erro',
                                        'cancelado' => '<span style="color: gray;">●</span> Cancelado'
                                    );
                                    echo isset($status_labels[$item->status]) ? $status_labels[$item->status] : $item->status;
                                    ?>
                                </td>
                                <td>
                                    <?php
                                    $edit_link = add_query_arg(array('page' => 'positivo_crm_agendamentos', 'edit' => intval($item->id)), admin_url('admin.php'));
                                    $delete_link = wp_nonce_url(
                                        add_query_arg(array('page' => 'positivo_crm_agendamentos', 'delete' => intval($item->id)), admin_url('admin.php')),
                                        'delete_agendamento_' . intval($item->id)
                                    );
                                    ?>
                                    <a href="<?php echo esc_url($edit_link); ?>"><?php esc_html_e('Editar', 'positivo-crm'); ?></a>
                                    |
                                    <a href="<?php echo esc_url($delete_link); ?>"
                                        onclick="return confirm('<?php echo esc_js(__('Tem certeza de que deseja remover este agendamento?', 'positivo-crm')); ?>');">
                                        <?php esc_html_e('Excluir', 'positivo-crm'); ?>
                                    </a>

                                    <?php if ($item->status === 'pendente' || $item->status === 'erro'): ?>
                                        |
                                        <form method="post" style="display:inline;">
                                            <?php wp_nonce_field('positivo_crm_agendamento_action', 'positivo_crm_agendamento_nonce'); ?>
                                            <input type="hidden" name="agendamento_id" value="<?php echo intval($item->id); ?>" />
                                            <button type="submit" name="enviar_crm" class="button button-small"
                                                onclick="return confirm('<?php echo esc_js(__('Enviar este agendamento para o CRM?', 'positivo-crm')); ?>');">
                                                <?php esc_html_e('Enviar para CRM', 'positivo-crm'); ?>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p><?php esc_html_e('Nenhum agendamento encontrado.', 'positivo-crm'); ?></p>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Cria a tabela de agendamentos se não existir
     */
    private function create_agendamentos_table()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'positivo_agendamentos';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        responsavel_nome varchar(100) NOT NULL,
        responsavel_sobrenome varchar(100) NOT NULL,
        responsavel_email varchar(255) NOT NULL,
        responsavel_telefone varchar(20) NOT NULL,
        responsavel_serie_interesse varchar(255) DEFAULT NULL,
        responsavel_como_conheceu int(11) DEFAULT NULL,
        aluno_nome varchar(100) NOT NULL,
        aluno_sobrenome varchar(100) NOT NULL,
        aluno_serie_interesse varchar(255) NOT NULL,
        aluno_ano_interesse int(11) NOT NULL,
        aluno_escola_origem varchar(255) DEFAULT NULL,
        unidade_id varchar(255) NOT NULL,
        unidade_nome varchar(255) DEFAULT NULL,
        data_agendamento date NOT NULL,
        hora_agendamento time NOT NULL,
        duracao_minutos int(11) DEFAULT 60,
        status varchar(50) DEFAULT 'pendente',
        enviado_crm tinyint(1) DEFAULT 0,
        data_envio_crm datetime DEFAULT NULL,
        lead_id varchar(255) DEFAULT NULL,
        atividade_id varchar(255) DEFAULT NULL,
        erro_envio text DEFAULT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        created_by bigint(20) DEFAULT NULL,
        PRIMARY KEY  (id),
        KEY idx_email (responsavel_email),
        KEY idx_unidade (unidade_id),
        KEY idx_data (data_agendamento),
        KEY idx_status (status),
        KEY idx_enviado (enviado_crm)
    ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }


    /**
     * Summary of enviar_agendamento_para_crm_json
     * @param mixed $data
     */
    private function enviar_agendamento_para_crm_json($data)
    {

        Positivo_CRM_Logger::info('Iniciando envio para CRM', [
            'data' => $data
        ]);

        $options = get_option('positivo_crm_options', []);

        $service_id = $data['servico'];
        $booking_status_id = $options['booking_status_id'] ?? '8b1707dc-f012-4979-a3cc-cc8317b942d5';
        $resource_id = $data['recurso'];
        $msdyn_status = intval($options['msdyn_status'] ?? 690970000);

        // ============================
        // RESPONSÁVEL
        // ============================

        $nome_completo = trim(preg_replace('/\s+/', ' ', $data['responsavel_nome']));
        $nome_parts = explode(' ', $nome_completo, 2);

        $responsavel_nome = $nome_parts[0] ?? '';
        $responsavel_sobrenome = $nome_parts[1] ?? '';

        // ============================
        // TIMEZONE
        // ============================

        $tz_br = new DateTimeZone('America/Sao_Paulo');
        $tz_utc = new DateTimeZone('UTC');

        $hora = trim($data['hora_agendamento']);
        $data_hora_br = $data['data_agendamento'] . ' ' . (strlen($hora) === 5 ? $hora . ':00' : $hora);

        $dt_inicio = new DateTime($data_hora_br, $tz_br);

        $dt_fim = clone $dt_inicio;
        $dt_fim->modify("+" . intval($data['duracao_minutos']) . " minutes");

        $dt_chegada = clone $dt_inicio;
        $dt_chegada->modify("-10 minutes");

        $dt_inicio->setTimezone($tz_utc);
        $dt_fim->setTimezone($tz_utc);
        $dt_chegada->setTimezone($tz_utc);

        $scheduledstart = $dt_inicio->format('Y-m-d\TH:i:s\Z');
        $scheduledend = $dt_fim->format('Y-m-d\TH:i:s\Z');
        $arrivaltime = $dt_chegada->format('Y-m-d\TH:i:s\Z');

        // ============================
        // UTM
        // ============================


        $utm_source = sanitize_text_field($data['utms']['utm_source'] ?? $_COOKIE['utm_source'] ?? '');
        $utm_medium = sanitize_text_field($data['utms']['utm_medium'] ?? $_COOKIE['utm_medium'] ?? '');
        $utm_campaign = sanitize_text_field($data['utms']['utm_campaign'] ?? $_COOKIE['utm_campaign'] ?? '');
        $utm_term = sanitize_text_field($data['utms']['utm_term'] ?? $_COOKIE['utm_term'] ?? '');
        $utm_content = sanitize_text_field($data['utms']['utm_content'] ?? $_COOKIE['utm_content'] ?? '');

        // ============================
        // DEPENDENTES
        // ============================

        $dependentes = [];

        foreach ($data['alunos'] as $key => $aluno) {

            $nome_completo = trim(preg_replace('/\s+/', ' ', $aluno['nome']));
            $nome_parts = explode(' ', $nome_completo, 2);

            $aluno_nome = $nome_parts[0] ?? '';
            $aluno_sobrenome = $nome_parts[1] ?? '';

            array_push($dependentes, [
                "cad_tipointeressado" => [
                    "option" => 1
                ],
                "firstname" => $aluno_nome,
                "lastname" => $aluno_sobrenome,
                "crm_unidadeinteresse" => [
                    "id" => $data['unidade_id']
                ],
                "crm_servicoeducacionalinteresse" => [
                    "id" => $aluno['serie_id']
                ],
                "col_anointeresse" => intval($aluno['ano_interesse']),
                "crmeduc_escoladeorigem" => ($aluno['escola_origem'] != "") ? $aluno['escola_origem'] : "Não localizei minha escola/Primeira Escola"
            ]);
        }

        // ============================
        // TEMPLATE
        // ============================

        $json_template = file_get_contents(
            POSITIVO_CRM_PATH . 'templates/agendamento-json-template.json'
        );

        $variables = [

            '{{responsavel_nome}}' => $responsavel_nome,
            '{{responsavel_sobrenome}}' => $responsavel_sobrenome,
            '{{responsavel_email}}' => $data['responsavel_email'],
            '{{responsavel_telefone}}' => $data['responsavel_telefone'],

            '{{unidade_id}}' => $data['unidade_id'],

            '{{scheduledstart}}' => $scheduledstart,
            '{{scheduledend}}' => $scheduledend,
            '{{estimatedarrivaltime}}' => $arrivaltime,
            '{{duracao_minutos}}' => intval($data['duracao_minutos']),

            '{{service_id}}' => $service_id,
            '{{resource_id}}' => $resource_id,

            '{{utm_source}}' => $utm_source,
            '{{utm_medium}}' => $utm_medium,
            '{{utm_campaign}}' => $utm_campaign,
            '{{utm_term}}' => $utm_term,
            '{{utm_content}}' => $utm_content,

            '{{subject}}' => 'Visita - ' . $data['unidade_nome'],
            '{{description}}' => 'Agendamento criado via site.',

            '{{dependentes}}' => json_encode($dependentes, JSON_UNESCAPED_UNICODE)
        ];

        $json_body = str_replace(
            array_keys($variables),
            array_values($variables),
            $json_template
        );
        $payload = json_decode($json_body, true);

        // ============================
        // ENVIO
        // ============================

        $api = new Positivo_CRM_API();
        $token = $api->get_access_token();

        if (is_wp_error($token)) {
            return $token;
        }

        $endpoint = 'https://colegiopositivoapi.crmeducacional.com/api/IntegracaoClientes/PositivoEnviarVisita';

        $response = wp_remote_post($endpoint, [
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $token
            ],
            'body' => json_encode($payload, JSON_PRETTY_PRINT | JSON_PRESERVE_ZERO_FRACTION),
            'timeout' => 60,
        ]);


        if (is_wp_error($response)) {
            return $response;
        }

        return json_decode(wp_remote_retrieve_body($response), true);
    }

    private function enviar_agendamento_para_crm($agendamento_id)
    {
        global $wpdb;

        Positivo_CRM_Logger::info('Iniciando envio de agendamento para CRM', [
            'agendamento_id' => $agendamento_id
        ]);

        $table = $wpdb->prefix . 'positivo_agendamentos';

        // ============================
        // BUSCA DO AGENDAMENTO
        // ============================
        $agendamento = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $agendamento_id)
        );

        if (!$agendamento) {
            Positivo_CRM_Logger::error('Agendamento não encontrado', [
                'agendamento_id' => $agendamento_id
            ]);
            return new WP_Error('agendamento_not_found', 'Agendamento não encontrado.');
        }

        // ============================
        // CONFIGURAÇÕES
        // ============================
        $options = get_option('positivo_crm_options', []);

        $service_id = $agendamento->servico;
        $booking_status_id = $options['booking_status_id'] ?? '8b1707dc-f012-4979-a3cc-cc8317b942d5';
        $resource_id = $agendamento->recurso;
        $msdyn_status = intval($options['msdyn_status'] ?? 690970000);
        $origem_positivo = intval($options['origem_positivo'] ?? 4);

        // ============================
        // DIVISÃO DE NOME DO RESPONSÁVEL
        // ============================
        $nome_completo = trim(preg_replace('/\s+/', ' ', $agendamento->responsavel_nome));
        $nome_parts = explode(' ', $nome_completo, 2);

        $responsavel_nome = $nome_parts[0] ?? '';
        $responsavel_sobrenome = $nome_parts[1] ?? '';

        // ============================
        // SÉRIES (IDS VINDOS DO FORM)
        // ============================
        $aluno_serie_id = $agendamento->aluno_serie_id ?: 'edbfd569-5dbc-ea11-a812-000d3ac06348';
        $responsavel_serie_id = $agendamento->responsavel_serie_id ?: 'edbfd569-5dbc-ea11-a812-000d3ac06348';

        // ============================
        // CORREÇÃO DE TIMEZONE (BR → UTC)
        // ============================
        $tz_br = new DateTimeZone('America/Sao_Paulo');
        $tz_utc = new DateTimeZone('UTC');

        $hora = trim($agendamento->hora_agendamento);
        $data_hora_br = $agendamento->data_agendamento . ' ' . (strlen($hora) === 5 ? $hora . ':00' : $hora);

        $dt_inicio = new DateTime($data_hora_br, $tz_br);
        $dt_fim = clone $dt_inicio;
        $dt_fim->modify("+" . intval($agendamento->duracao_minutos) . " minutes");

        $dt_chegada = clone $dt_inicio;
        $dt_chegada->modify("-10 minutes");

        $dt_inicio->setTimezone($tz_utc);
        $dt_fim->setTimezone($tz_utc);
        $dt_chegada->setTimezone($tz_utc);

        $scheduledstart = $dt_inicio->format('Y-m-d\TH:i:s\Z');
        $scheduledend = $dt_fim->format('Y-m-d\TH:i:s\Z');
        $arrivaltime = $dt_chegada->format('Y-m-d\TH:i:s\Z');

        // ============================
        // CAPTURA DE UTM (SEM BANCO)
        // ============================
        $utm_source = sanitize_text_field($_POST['utm_source'] ?? $_COOKIE['utm_source'] ?? '');
        $utm_medium = sanitize_text_field($_POST['utm_medium'] ?? $_COOKIE['utm_medium'] ?? '');
        $utm_campaign = sanitize_text_field($_POST['utm_campaign'] ?? $_COOKIE['utm_campaign'] ?? '');
        $utm_term = sanitize_text_field($_POST['utm_term'] ?? $_COOKIE['utm_term'] ?? '');
        $utm_content = sanitize_text_field($_POST['utm_content'] ?? $_COOKIE['utm_content'] ?? '');

        // ALUNO
        $nome_completo = trim(preg_replace('/\s+/', ' ', $agendamento->aluno_nome));
        $nome_parts = explode(' ', $nome_completo, 2);


        $aluno_nome = $nome_parts[0] ?? '';
        $aluno_sobrenome = $nome_parts[1] ?? '';


        // ============================
        // VARIÁVEIS DO TEMPLATE
        // ============================
        $variables = [
            '{{responsavel_nome}}' => $responsavel_nome,
            '{{responsavel_sobrenome}}' => $responsavel_sobrenome,
            '{{responsavel_email}}' => $agendamento->responsavel_email,
            '{{responsavel_telefone}}' => $agendamento->responsavel_telefone,
            '{{responsavel_serie_id}}' => preg_replace('/^\{|\}$/', '', $responsavel_serie_id),

            '{{aluno_nome}}' => $aluno_nome,
            '{{aluno_sobrenome}}' => $aluno_sobrenome,
            '{{aluno_ano_interesse}}' => intval($agendamento->aluno_ano_interesse),
            '{{aluno_escola_origem}}' => $agendamento->aluno_escola_origem ?: '',

            '{{unidade_id}}' => preg_replace('/^\{|\}$/', '', $agendamento->unidade_id),
            '{{unidade_nome}}' => $agendamento->unidade_nome,

            '{{scheduledstart}}' => $scheduledstart,
            '{{scheduledend}}' => $scheduledend,
            '{{estimatedarrivaltime}}' => $arrivaltime,
            '{{duracao_minutos}}' => intval($agendamento->duracao_minutos),

            '{{service_id}}' => $service_id,
            '{{booking_status_id}}' => $booking_status_id,
            '{{resource_id}}' => $resource_id,
            '{{msdyn_status}}' => $msdyn_status,
            '{{origem_positivo}}' => $origem_positivo,

            // 🔥 NOVOS CAMPOS DE CAMPANHA
            '{{utm_source}}' => $utm_source,
            '{{utm_medium}}' => $utm_medium,
            '{{utm_campaign}}' => $utm_campaign,
            '{{utm_term}}' => $utm_term,
            '{{utm_content}}' => $utm_content,

            '{{subject}}' => 'Visita - ' . $agendamento->unidade_nome,
            '{{description}}' => 'Agendamento criado via site.'
        ];

        // ============================
        // CARREGA TEMPLATE JSON
        // ============================
        $json_template = file_get_contents(
            POSITIVO_CRM_PATH . 'templates/agendamento-json-template.json'
        );

        $json_body = str_replace(array_keys($variables), array_values($variables), $json_template);

        $payload = json_decode($json_body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return new WP_Error('invalid_json', json_last_error_msg());
        }

        // ============================
        // TOKEN E ENVIO
        // ============================
        $api = new Positivo_CRM_API();
        $token = $api->get_access_token();

        if (is_wp_error($token)) {
            return $token;
        }

        $endpoint = 'https://colegiopositivoapi.crmeducacional.com/api/IntegracaoClientes/PositivoEnviarVisita';

        $response = wp_remote_post($endpoint, [
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $token
            ],
            'body' => json_encode($payload, JSON_PRETTY_PRINT | JSON_PRESERVE_ZERO_FRACTION),
            'timeout' => 60,
        ]);

        if (is_wp_error($response)) {
            return $response;
        }

        return json_decode(wp_remote_retrieve_body($response), true);
    }



    /**
     * Retorna mapeamento de séries para IDs do CRM
     * TODO: Isso deve vir de uma API ou ser configurável
     */
    private function get_serie_id_map()
    {
        $options = get_option('positivo_crm_options', array());

        // Mapeamento padrão (deve ser configurável)
        $default_map = array(
            'educacao-infantil' => 'edbfd569-5dbc-ea11-a812-000d3ac06348',
            '1-ano' => 'edbfd569-5dbc-ea11-a812-000d3ac06348',
            '2-ano' => 'edbfd569-5dbc-ea11-a812-000d3ac06348',
            '3-ano' => 'edbfd569-5dbc-ea11-a812-000d3ac06348',
            '4-ano' => 'edbfd569-5dbc-ea11-a812-000d3ac06348',
            '5-ano' => 'edbfd569-5dbc-ea11-a812-000d3ac06348',
            '6-ano' => 'edbfd569-5dbc-ea11-a812-000d3ac06348',
            '7-ano' => 'edbfd569-5dbc-ea11-a812-000d3ac06348',
            '8-ano' => 'edbfd569-5dbc-ea11-a812-000d3ac06348',
            '9-ano' => 'edbfd569-5dbc-ea11-a812-000d3ac06348',
            '1-serie-em' => 'edbfd569-5dbc-ea11-a812-000d3ac06348',
            '2-serie-em' => 'edbfd569-5dbc-ea11-a812-000d3ac06348',
            '3-serie-em' => 'edbfd569-5dbc-ea11-a812-000d3ac06348',
            'fundamental-1' => 'edbfd569-5dbc-ea11-a812-000d3ac06348',
            'fundamental-2' => 'edbfd569-5dbc-ea11-a812-000d3ac06348',
            'ensino-medio' => 'edbfd569-5dbc-ea11-a812-000d3ac06348',
        );

        // Permitir override via configurações
        if (isset($options['serie_id_map']) && is_array($options['serie_id_map'])) {
            return array_merge($default_map, $options['serie_id_map']);
        }

        return $default_map;
    }

    public function horarios_page()
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        global $wpdb;
        $table = $wpdb->prefix . 'positivo_unidade_horarios';

        // =========================
        // EXCLUSÃO
        // =========================
        if (isset($_GET['delete'])) {
            $delete_id = intval($_GET['delete']);
            $wpdb->delete($table, ['id' => $delete_id], ['%d']);
            echo '<div class="updated notice"><p>Horário removido com sucesso.</p></div>';
        }

        // =========================
        // CREATE / UPDATE
        // =========================
        if (isset($_POST['submit_horario_admin'])) {
            check_admin_referer('positivo_crm_horario_action', 'positivo_crm_horario_nonce');

            $id = intval($_POST['horario_id'] ?? 0);
            $unidade = sanitize_text_field($_POST['unidade'] ?? '');
            $dia_semana = sanitize_text_field($_POST['dia_semana'] ?? '');
            $hora_inicio = sanitize_text_field($_POST['hora_inicio'] ?? '');
            $hora_fim = sanitize_text_field($_POST['hora_fim'] ?? '');
            $duracao = max(15, intval($_POST['duracao_visita_minutos'] ?? 120));

            $dados = [
                'unidade' => $unidade,

                'dia_semana' => $dia_semana,
                'hora_inicio' => $hora_inicio,
                'hora_fim' => $hora_fim,
                'duracao_visita_minutos' => $duracao,
            ];

            $format = ['%s', '%s', '%s', '%s', '%d'];

            if ($id > 0) {
                $wpdb->update($table, $dados, ['id' => $id], $format, ['%d']);
                echo '<div class="updated notice"><p>Horário atualizado com sucesso.</p></div>';
            } else {
                $wpdb->insert($table, $dados, $format);
                echo '<div class="updated notice"><p>Horário criado com sucesso.</p></div>';
            }
        }

        // =========================
        // EDIÇÃO
        // =========================
        $edit_item = null;
        if (isset($_GET['edit'])) {
            $edit_item = $wpdb->get_row(
                $wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", intval($_GET['edit']))
            );
        }

        $items = $wpdb->get_results("SELECT * FROM {$table} ORDER BY id DESC");

        // =========================
        // MAPA DE UNIDADES
        // =========================
        $units_map = [];
        $api = new Positivo_CRM_API();
        $units_res = $api->get_unidades();

        if (!is_wp_error($units_res) && isset($units_res['result'])) {
            foreach ($units_res['result'] as $u) {
                if (isset($u['cad_categoriaid'], $u['cad_name'])) {
                    $units_map[$u['cad_categoriaid']] = $u['cad_name'];
                }
            }
        }

        // =========================
        // FORM
        // =========================
        ?>
        <div class="wrap">
            <h1>Horários de Unidades</h1>

            <form method="post">
                <?php wp_nonce_field('positivo_crm_horario_action', 'positivo_crm_horario_nonce'); ?>
                <input type="hidden" name="horario_id" value="<?= esc_attr($edit_item->id ?? 0) ?>">

                <table class="form-table">
                    <tr>
                        <th>Unidade</th>
                        <td>
                            <select name="unidade" required>
                                <option value="">Selecione...</option>
                                <?php foreach ($units_map as $id => $nome): ?>
                                    <option value="<?= esc_attr($id) ?>" <?= selected($edit_item->unidade ?? '', $id, false) ?>>
                                        <?= esc_html($nome) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>

                    <tr>
                        <th>Dia da Semana</th>
                        <td>
                            <select name="dia_semana" required>
                                <?php
                                $dias = ['segunda', 'terca', 'quarta', 'quinta', 'sexta', 'sabado', 'domingo'];
                                foreach ($dias as $d):
                                    ?>
                                    <option value="<?= $d ?>" <?= selected($edit_item->dia_semana ?? '', $d, false) ?>>
                                        <?= ucfirst($d) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>

                    <tr>
                        <th>Hora Início</th>
                        <td><input type="time" name="hora_inicio" value="<?= esc_attr($edit_item->hora_inicio ?? '') ?>"
                                required></td>
                    </tr>

                    <tr>
                        <th>Hora Fim</th>
                        <td><input type="time" name="hora_fim" value="<?= esc_attr($edit_item->hora_fim ?? '') ?>" required>
                        </td>
                    </tr>

                    <tr>
                        <th>Duração da Visita (min)</th>
                        <td>
                            <input type="number" name="duracao_visita_minutos" min="15" step="15"
                                value="<?= esc_attr($edit_item->duracao_visita_minutos ?? 120) ?>" required>
                            <p class="description">Tempo de cada visita nesta unidade/dia</p>
                        </td>
                    </tr>
                </table>

                <?php
                submit_button(
                    $edit_item ? 'Atualizar Horário' : 'Criar Horário',
                    'primary',
                    'submit_horario_admin'
                );
                ?>
            </form>

            <hr>

            <h2>Lista de Horários</h2>

            <table class="wp-list-table widefat striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Unidade</th>
                        <th>Dia</th>
                        <th>Início</th>
                        <th>Fim</th>
                        <th>Duração</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td><?= intval($item->id) ?></td>
                            <td><?= esc_html($units_map[$item->unidade] ?? $item->unidade) ?></td>
                            <td><?= esc_html(ucfirst($item->dia_semana)) ?></td>
                            <td><?= esc_html($item->hora_inicio) ?></td>
                            <td><?= esc_html($item->hora_fim) ?></td>
                            <td><?= intval($item->duracao_visita_minutos) ?> min</td>
                            <td>
                                <a href="<?= esc_url(add_query_arg(['edit' => $item->id])) ?>">Editar</a> |
                                <a href="<?= esc_url(add_query_arg(['delete' => $item->id])) ?>"
                                    onclick="return confirm('Remover este horário?')">Excluir</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }


    /**
     * Adiciona a meta box ao editor de páginas.
     */
    public function add_meta_boxes($post_type)
    {
        if ('page' === $post_type) {
            add_meta_box(
                'positivo_crm_form_meta',
                __('Formulário de Agendamento Positivo CRM', 'positivo-crm'),
                array($this, 'render_form_meta_box'),
                'page',
                'side',
                'default'
            );
        }
    }

    /**
     * Renderiza o conteúdo da meta box.
     */
    public function render_form_meta_box($post)
    {
        $value = get_post_meta($post->ID, '_positivo_crm_add_form', true);
        wp_nonce_field('positivo_crm_meta_box', 'positivo_crm_meta_box_nonce');
        echo '<label><input type="checkbox" name="positivo_crm_add_form" value="1" ' . checked($value, '1', false) . ' /> ' . esc_html__('Inserir formulário de agendamento nesta página', 'positivo-crm') . '</label>';
    }

    /**
     * Salva o valor da meta box quando a página é salva.
     *
     * @param int $post_id ID do post.
     * @param WP_Post $post Objeto do post.
     */
    public function save_page_meta($post_id, $post)
    {
        // Verifica nonce
        if (!isset($_POST['positivo_crm_meta_box_nonce']) || !wp_verify_nonce($_POST['positivo_crm_meta_box_nonce'], 'positivo_crm_meta_box')) {
            return;
        }
        // Evita autosave e revisões
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if ('page' !== $post->post_type) {
            return;
        }
        // Permissão
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        // Atualiza meta
        $should_add = isset($_POST['positivo_crm_add_form']) ? '1' : '';
        if ($should_add) {
            update_post_meta($post_id, '_positivo_crm_add_form', '1');
        } else {
            delete_post_meta($post_id, '_positivo_crm_add_form');
        }
    }

    /**
     * Insere o formulário de agendamento no conteúdo da página, se configurado.
     *
     * @param string $content Conteúdo original.
     * @return string Conteúdo possivelmente com o formulário anexado.
     */

    /**
     * Página de visualização de logs
     */
    public function logs_page()
    {
        if (!current_user_can('manage_options')) {
            return;
        }
        require_once POSITIVO_CRM_PATH . 'includes/logs-page.php';
    }


    public function append_agendamento_to_content($content)
    {
        // Não executar durante requisições AJAX (evita erro no editor de blocos)
        if (wp_doing_ajax()) {
            return $content;
        }

        if (is_singular('page') && in_the_loop() && is_main_query()) {
            global $post;
            $add_form = get_post_meta($post->ID, '_positivo_crm_add_form', true);
            if ($add_form) {
                $form_html = self::get_form_html();
                $content .= $form_html;
            }
        }
        return $content;
    }

    /**
     * Retorna o HTML do formulário, usando o template salvo nas opções ou o padrão.
     *
     * @return string HTML do formulário.
     */
    public static function get_form_html()
    {
        $options = get_option('positivo_crm_options', array());
        if (!empty($options['html_template'])) {
            // Usa o template salvo pelo usuário
            // Nota: Não usamos wp_kses_post aqui porque precisamos permitir tags <style>
            return $options['html_template'];
        }
        // Caso contrário, inclui o template padrão do plugin
        ob_start();
        include POSITIVO_CRM_PATH . 'templates/agendamento-form.php';
        return ob_get_clean();
    }

    /**
     * Insere um agendamento vindo do frontend.
     *
     * Este método é chamado via AJAX na submissão do formulário. Ele grava
     * informações básicas do agendamento no banco de dados e armazena todos
     * os dados do formulário em formato serializado para referência futura.
     *
     * @param array $form_data Array associativo com os dados do formulário.
     */
    public static function insert_agendamento_from_frontend($form_data)
    {
        if (!is_array($form_data)) {
            return;
        }
        global $wpdb;
        $table = $wpdb->prefix . 'positivo_agendamentos';
        $responsavel = isset($form_data['responsavel_nome']) ? sanitize_text_field($form_data['responsavel_nome']) : '';
        $aluno = isset($form_data['aluno_nome']) ? sanitize_text_field($form_data['aluno_nome']) : '';
        // Tenta capturar a data/hora de agendamento se existir
        $data_agendamento = isset($form_data['data_agendamento']) ? sanitize_text_field($form_data['data_agendamento']) : null;
        $created_at = current_time('mysql');
        $wpdb->insert($table, array(
            'responsavel_nome' => $responsavel,
            'aluno_nome' => $aluno,
            'data_agendamento' => $data_agendamento,
            'form_data' => maybe_serialize($form_data),
            'created_at' => $created_at,
        ), array('%s', '%s', '%s', '%s', '%s'));
    }
}
