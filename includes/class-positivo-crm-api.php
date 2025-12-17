<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Classe para gerenciar a integração com a API do CRM Educacional Positivo.
 *
 * Esta classe lida com a autenticação (Get Token, Refresh Token) e as requisições
 * protegidas (Lista de Unidades, Busca de Responsável, Busca de Aluno).
 */
class Positivo_CRM_API {

	// Defina as constantes de configuração da API.
	// O usuário precisará fornecer os valores reais para {{base_url}}, {{token_path}}, {{protected_path}},
	// e as credenciais (username, password, authentication_type).
	// Por enquanto, usaremos placeholders e faremos um método para configurar.
	const BASE_URL = 'https://colegiopositivo.api.crmeducacional.com/api/';
    /**
     * Caminho do endpoint responsável pela obtenção do token de acesso.
     * De acordo com a documentação do CRM Educacional do Colégio Positivo, o endpoint é
     * simplesmente "token". Caso a API mude, este valor pode ser ajustado nas constantes.
     */
    const TOKEN_PATH = 'token';
    /**
     * Caminho para a API de Localização Avançada.
     * Este endpoint permite executar consultas FetchXML avançadas no CRM.
     * O valor é concatenado com BASE_URL, que já inclui "/api/".
     */
    const PROTECTED_PATH = 'LocalizacaoAvancada';
    /**
     * Constantes de credenciais padrão. Estes valores podem ser sobrescritos pelas
     * opções salvas nas configurações do plugin. Consulte o método
     * get_credentials() para obter as credenciais efetivas.
     */
    const USERNAME = 'YOUR_USERNAME';
    const PASSWORD = 'YOUR_PASSWORD';
    /**
     * Tipo de autenticação padrão utilizado para obtenção do token.
     * Se a opção correspondente for configurada no admin, o valor salvo em
     * positivo_crm_options['crm_auth_type'] será utilizado no lugar deste.
     */
    const AUTH_TYPE = 'APICRMEducacional';

    /**
     * Recupera as credenciais (username, password, auth_type) a partir das opções
     * salvas no banco de dados. Se alguma delas não estiver definida, utiliza
     * os valores das constantes como fallback.
     *
     * @return array Array associativo com as chaves username, password e auth_type.
     */
    private function get_credentials() {
        $defaults = array(
            'username'   => self::USERNAME,
            'password'   => self::PASSWORD,
            'auth_type'  => self::AUTH_TYPE,
        );
        $options = get_option( 'positivo_crm_options', array() );
        $username  = ! empty( $options['crm_username'] ) ? $options['crm_username'] : $defaults['username'];
        $password  = ! empty( $options['crm_password'] ) ? $options['crm_password'] : $defaults['password'];
        // Tipo de autenticação pode ser definido nas configurações do plugin.
        $auth_type  = ! empty( $options['crm_auth_type'] ) ? $options['crm_auth_type'] : $defaults['auth_type'];
        return array(
            'username'  => $username,
            'password'  => $password,
            'auth_type' => $auth_type,
        );
    }

	/**
	 * Chave para armazenar o token no banco de dados do WordPress.
	 */
	const TOKEN_OPTION_KEY = 'positivo_crm_api_tokens';

	/**
	 * Construtor.
	 */
	public function __construct() {
		// Adiciona hooks para AJAX (para usuários logados e não logados)
		add_action( 'wp_ajax_nopriv_positivo_crm_get_units', array( $this, 'ajax_get_units' ) );
		add_action( 'wp_ajax_positivo_crm_get_units', array( $this, 'ajax_get_units' ) );

		add_action( 'wp_ajax_nopriv_positivo_crm_search_responsavel', array( $this, 'ajax_search_responsavel' ) );
		add_action( 'wp_ajax_positivo_crm_search_responsavel', array( $this, 'ajax_search_responsavel' ) );

		add_action( 'wp_ajax_nopriv_positivo_crm_submit_agendamento', array( $this, 'ajax_submit_agendamento' ) );
		add_action( 'wp_ajax_positivo_crm_submit_agendamento', array( $this, 'ajax_submit_agendamento' ) );

		add_action( 'wp_ajax_nopriv_positivo_crm_get_series', array( $this, 'ajax_get_series' ) );
		add_action( 'wp_ajax_positivo_crm_get_series', array( $this, 'ajax_get_series' ) );

		add_action( 'wp_ajax_nopriv_positivo_crm_get_students', array( $this, 'ajax_get_students' ) );
		add_action( 'wp_ajax_positivo_crm_get_students', array( $this, 'ajax_get_students' ) );

		// Registra os endpoints da API REST
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
	}

	/**
	 * Obtém os tokens de acesso e refresh armazenados.
	 *
	 * @return array|false Os tokens ou false se não existirem.
	 */
	private function get_stored_tokens() {
		return get_option( self::TOKEN_OPTION_KEY, false );
	}

	/**
	 * Armazena os tokens de acesso e refresh.
	 *
	 * @param array $tokens Os tokens a serem armazenados.
	 * @return bool True em caso de sucesso, false caso contrário.
	 */
	private function set_stored_tokens( $tokens ) {
		return update_option( self::TOKEN_OPTION_KEY, $tokens );
	}

	/**
	 * Realiza a requisição para obter o token de acesso inicial.
	 *
	 * @return array|WP_Error O array de tokens (access_token, refresh_token, expires_in) ou WP_Error.
	 */
	public function get_token() {
		$url = self::BASE_URL . self::TOKEN_PATH;
		Positivo_CRM_Logger::debug( 'Requesting authentication token' );

        $creds = $this->get_credentials();
        $body  = array(
            'grant_type'          => 'password',
            'username'            => $creds['username'],
            'password'            => $creds['password'],
            'authentication_type' => $creds['auth_type'],
        );

		$response = wp_remote_post( $url, array(
			'headers' => array(
				'Content-Type' => 'application/x-www-form-urlencoded',
				'Accept'       => 'application/json',
			),
			'body'    => $body,
		) );

		return $this->handle_token_response( $response );
	}

    /**
     * Obtém um token de acesso válido, renovando se necessário.
     *
     * @return string|WP_Error O token de acesso ou WP_Error.
     */
    public function get_access_token() {
        return $this->get_valid_access_token();
    }


	/**
	 * Realiza a requisição para renovar o token de acesso.
	 *
	 * @param string $refresh_token O token de refresh.
	 * @return array|WP_Error O array de tokens ou WP_Error.
	 */
	public function refresh_token( $refresh_token ) {
		$url = self::BASE_URL . self::TOKEN_PATH;

        $creds = $this->get_credentials();
        $body  = array(
            'grant_type'          => 'refresh_token',
            'refresh_token'       => $refresh_token,
            'authentication_type' => $creds['auth_type'],
        );

		$response = wp_remote_post( $url, array(
			'headers' => array(
				'Content-Type' => 'application/x-www-form-urlencoded',
				'Accept'       => 'application/json',
			),
			'body'    => $body,
		) );

		return $this->handle_token_response( $response );
	}

	/**
	 * Trata a resposta da requisição de token.
	 *
	 * @param array|WP_Error $response A resposta da requisição.
	 * @return array|WP_Error O array de tokens ou WP_Error.
	 */
	private function handle_token_response( $response ) {
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( 200 !== wp_remote_retrieve_response_code( $response ) || ! isset( $data['access_token'] ) ) {
			Positivo_CRM_Logger::error( 'Failed to obtain token', array( 'response' => $data ) );
			return new WP_Error( 'api_token_error', 'Erro ao obter/renovar token: ' . ( isset( $data['error_description'] ) ? $data['error_description'] : 'Resposta desconhecida.' ) );
		}
		Positivo_CRM_Logger::info( 'Authentication token obtained successfully' );

		$tokens = array(
			'access_token'  => $data['access_token'],
			'refresh_token' => isset( $data['refresh_token'] ) ? $data['refresh_token'] : null,
			'expires_in'    => isset( $data['expires_in'] ) ? $data['expires_in'] : 3600, // Padrão de 1h se não especificado
			'acquired_at'   => time(),
		);

		$this->set_stored_tokens( $tokens );
		return $tokens;
	}

	/**
	 * Obtém um token de acesso válido, renovando se necessário.
	 *
	 * @return string|WP_Error O token de acesso ou WP_Error.
	 */
	private function get_valid_access_token() {
		$tokens = $this->get_stored_tokens();

		if ( ! $tokens || ! isset( $tokens['access_token'] ) ) {
			$tokens = $this->get_token();
			if ( is_wp_error( $tokens ) ) {
				return $tokens;
			}
		}

		// Verifica se o token está prestes a expirar (ex: 5 minutos antes).
		$expiration_time = $tokens['acquired_at'] + $tokens['expires_in'] - 300;
		if ( time() >= $expiration_time ) {
			if ( isset( $tokens['refresh_token'] ) ) {
				$new_tokens = $this->refresh_token( $tokens['refresh_token'] );
				if ( is_wp_error( $new_tokens ) ) {
					// Se a renovação falhar, tenta obter um novo token.
					$new_tokens = $this->get_token();
					if ( is_wp_error( $new_tokens ) ) {
						return $new_tokens;
					}
				}
				$tokens = $new_tokens;
			} else {
				// Se não houver refresh token, tenta obter um novo token.
				$new_tokens = $this->get_token();
				if ( is_wp_error( $new_tokens ) ) {
					return $new_tokens;
				}
				$tokens = $new_tokens;
			}
		}

		return $tokens['access_token'];
	}

	/**
	 * Realiza uma requisição protegida à API.
	 *
	 * @param string $method O método HTTP (GET, POST, etc.).
	 * @param string $fetch_xml O FetchXML a ser enviado no corpo da requisição.
	 * @return array|WP_Error A resposta decodificada da API ou WP_Error.
	 */
    private function protected_request( $method, $fetch_xml ) {
		$access_token = $this->get_valid_access_token();

		if ( is_wp_error( $access_token ) ) {
			return $access_token;
		}

        // Determina a rota protegida a partir das opções ou utiliza a constante padrão
        $options = get_option( 'positivo_crm_options', array() );
        $custom_path = isset( $options['protected_path'] ) && ! empty( $options['protected_path'] ) ? trim( $options['protected_path'], '/' ) : self::PROTECTED_PATH;
        $url = self::BASE_URL . $custom_path;

        // Quando o método é GET e há corpo a ser enviado, a API do CRM Educacional
        // requer que o corpo (FetchXML) seja enviado mesmo assim. Entretanto, o WP HTTP
        // API apresenta um bug ao tentar construir query strings para GET com corpo
        // (gera um TypeError). Para contornar isso, usamos cURL manualmente para
        // requisições GET com corpo. Para outros métodos, utilizamos wp_remote_request.
        if ( strtoupper( $method ) === 'GET' ) {
            /*
             * A API do CRM Educacional permite enviar o FetchXML no corpo de uma
             * requisição GET. Entretanto, o WP_HTTP API (wp_remote_request)
             * tenta transformar o corpo em parâmetros de query string,
             * resultando em um erro fatal (http_build_query espera array).
             * Para contornar, usamos cURL diretamente quando o método é GET.
             */
            $curl = curl_init( $url );
            curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
            curl_setopt( $curl, CURLOPT_CUSTOMREQUEST, 'GET' );
            // Mesmo para GET, o corpo deve ser enviado via POSTFIELDS
            curl_setopt( $curl, CURLOPT_POSTFIELDS, $fetch_xml );
            // Define os cabeçalhos. Inclui Content-Type para indicar XML.
            $headers = array(
                'Authorization: Bearer ' . $access_token,
                'Accept: application/json',
                'Content-Type: application/xml',
            );
            curl_setopt( $curl, CURLOPT_HTTPHEADER, $headers );
            // Define um timeout razoável
            curl_setopt( $curl, CURLOPT_TIMEOUT, 30 );
            $response_body = curl_exec( $curl );
            $http_code     = curl_getinfo( $curl, CURLINFO_HTTP_CODE );
            $curl_error    = curl_error( $curl );
            curl_close( $curl );
            if ( $response_body === false ) {
                return new WP_Error( 'curl_error', 'Erro ao executar cURL: ' . $curl_error );
            }
            $response = array(
                'headers'  => array(),
                'body'     => $response_body,
                'response' => array( 'code' => $http_code, 'message' => '' ),
            );
        } else {
            // Para métodos POST/PUT etc., use WP_HTTP API normalmente.
            $args = array(
                'method'  => $method,
                'headers' => array(
                    'Authorization' => 'Bearer ' . $access_token,
                    'Accept'        => 'application/json',
                    // Content-Type: application/xml para corpo FetchXML
                    'Content-Type'  => 'application/xml',
                ),
                'body'    => $fetch_xml,
                'timeout' => 30,
            );
            $response = wp_remote_request( $url, $args );
        }

		if ( is_wp_error( $response ) ) {
			return $response;
		}

        $body = wp_remote_retrieve_body( $response );

        // Verifica o código de resposta HTTP
        $response_code = wp_remote_retrieve_response_code( $response );

        /*
         * Quando a API retorna código 204 (No Content), significa que a consulta
         * foi executada com sucesso, mas nenhum registro foi encontrado. Em vez
         * de tratar isso como erro genérico, retornamos um WP_Error com
         * código "no_results" e uma mensagem amigável ao usuário. Isso é
         * capturado pelos callbacks AJAX e repassado ao JavaScript.
         */
        if ( 204 === $response_code ) {
            return new WP_Error( 'no_results', __( 'Nenhum resultado encontrado.', 'positivo-crm' ) );
        }

        if ( 200 !== $response_code ) {
            // Tenta decodificar a mensagem de erro se possível
            $error_data = json_decode( $body, true );
            return new WP_Error( 'api_request_error', 'Erro na requisição protegida: ' . wp_remote_retrieve_response_message( $response ), $error_data );
        }

        // Tenta decodificar JSON; se falhar, retorna o corpo bruto
        $data = json_decode( $body, true );
        if ( json_last_error() === JSON_ERROR_NONE ) {
            return $data;
        }
        // Tenta carregar como XML
        $xml = @simplexml_load_string( $body );
        if ( $xml !== false ) {
            // Converte SimpleXML para array associativo
            $json = json_encode( $xml );
            $array = json_decode( $json, true );
            return $array;
        }
        // Caso não seja possível decodificar, retorna o corpo de resposta (string)
        return $body;
	}

	/**
	 * Requisição: Lista de Unidades da Positivo.
	 *
	 * @return array|WP_Error A lista de unidades ou WP_Error.
	 */
	public function get_unidades() {
		// FetchXML da requisição "Lista de Unidades da Positivo"
		// Permite substituição pelo template salvo nas configurações
		$default_fetch = '<fetch version="1.0" output-format="xml-platform" mapping="logical" distinct="false">
  <entity name="cad_categoria">
    <attribute name="cad_categoriaid" />
    <attribute name="cad_name" />
    <attribute name="pos_endereco_unidade" />
    <attribute name="col_telefonedaunidade" />
    <attribute name="crmeduc_emaildaunidade" />
    <attribute name="crmeduc_supervisor" />
    <attribute name="createdon" />
    <order attribute="cad_name" descending="false" />
    <filter type="and">
      <condition attribute="statecode" operator="eq" value="0" />
    </filter>
  </entity>
</fetch>';
		$options    = get_option( 'positivo_crm_options', array() );
		$fetch_xml  = isset( $options['fetch_xml_unidades'] ) && ! empty( $options['fetch_xml_unidades'] ) ? $options['fetch_xml_unidades'] : $default_fetch;
		// Determina o método de requisição (GET ou POST) a partir das opções salvas
		$options = get_option( 'positivo_crm_options', array() );
		$method  = isset( $options['method_unidades'] ) ? strtoupper( $options['method_unidades'] ) : 'GET';
		if ( ! in_array( $method, array( 'GET', 'POST' ), true ) ) {
			$method = 'GET';
		}
		return $this->protected_request( $method, $fetch_xml );
	}

	/**
	 * Requisição: Busca de Responsável.
	 *
	 * @param string $fullname Nome completo do responsável.
	 * @return array|WP_Error O resultado da busca ou WP_Error.
	 */
	public function search_responsavel( $fullname ) {
        // Recupera o FetchXML salvo ou utiliza o padrão. Substitui o placeholder pelo nome.
        $default_template = '<fetch version="1.0" output-format="xml-platform" mapping="logical" distinct="false">
  <entity name="lead">
    <attribute name="fullname" />
    <attribute name="leadid" />
    <attribute name="createdon" />
    <attribute name="pos_origem_positivo" />
    <attribute name="col_numerofilhos" />
    <attribute name="col_comoconheceu" />
    <attribute name="mobilephone" />
    <attribute name="emailaddress1" />
    <attribute name="col_visitasrealizadas" />
    <attribute name="crmeduc_whatsappsrealizados" />
    <attribute name="crmeduc_telefonemasrealizados" />
    <attribute name="crmeduc_emailsenviados" />
    <order attribute="createdon" descending="true" />
    <filter type="and">
      <condition attribute="cad_tipointeressado" operator="eq" value="0" />
      <filter type="and">
        <condition attribute="fullname" operator="not-like" value="%%Teste%%" />
        <condition attribute="fullname" operator="not-like" value="%%Compromisso%%" />
      </filter>
      <condition attribute="fullname" operator="like" value="%%%s%%" />
    </filter>
  </entity>
</fetch>';
        $options    = get_option( 'positivo_crm_options', array() );
        $fetch_xml  = isset( $options['fetch_xml_responsavel'] ) && ! empty( $options['fetch_xml_responsavel'] ) ? $options['fetch_xml_responsavel'] : $default_template;

        // Define template_raw com o conteúdo atual de $fetch_xml para evitar variáveis indefinidas.
        $template_raw = $fetch_xml;

        // Sanitiza o termo de busca usando sanitize_text_field ao invés de esc_attr
        $search = sanitize_text_field( $fullname );
        // Substitui o placeholder %s ou marcadores personalizados pelo nome do responsável
        if ( strpos( $template_raw, '%s' ) !== false ) {
            $fetch_xml = sprintf( $template_raw, $search );
        } else {
            // Tenta substituir marcadores {fullname} ou {nome} se presentes
            $fetch_xml = str_replace( array( '{fullname}', '{nome}' ), $search, $template_raw );
        }

        // Determina o método de requisição para a busca de responsável
        $options = get_option( 'positivo_crm_options', array() );
        $method  = isset( $options['method_responsavel'] ) ? strtoupper( $options['method_responsavel'] ) : 'GET';
        if ( ! in_array( $method, array( 'GET', 'POST' ), true ) ) {
            $method = 'GET';
        }
        return $this->protected_request( $method, $fetch_xml );
	}

	/**
	 * Requisição: Busca de Aluno Por Responsável.
	 *
	 * @param string $responsavel_id ID do lead do responsável.
	 * @return array|WP_Error O resultado da busca ou WP_Error.
	 */
	public function search_aluno_by_responsavel( $responsavel_id ) {
        // O FetchXML precisa ser dinâmico para incluir o ID do responsável.

        // Validação simples do formato do GUID (leadid) do responsável. O CRM
        // espera um identificador no formato 8-4-4-4-12 (32 dígitos e 4 hifens).
        $responsavel_id = trim( $responsavel_id );
        $guid_pattern    = '/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}$/';
        if ( ! preg_match( $guid_pattern, $responsavel_id ) ) {
            return new WP_Error( 'invalid_guid', __( 'ID do responsável inválido. O ID deve ser um GUID no formato XXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX.', 'positivo-crm' ) );
        }

        $default_template = '<fetch version="1.0" output-format="xml-platform" mapping="logical" distinct="false">
  <entity name="lead">
    <attribute name="fullname" />
    <attribute name="leadid" />
    <attribute name="createdon" />
    <attribute name="cad_responsavel" />
    <attribute name="col_anointeresse" />
    <attribute name="crm_unidadeinteresse" />
    <attribute name="crm_servicoeducacionalinteresse" />
    <attribute name="col_turnointeresse" />
    <attribute name="col_funilconversao" />
    <attribute name="col_statusdareuniao" />
    <attribute name="col_datadoagendamento" />
    <order attribute="createdon" descending="true" />
    <filter type="and">
      <condition attribute="cad_tipointeressado" operator="eq" value="1" />
      <condition attribute="cad_responsavel" operator="eq" uitype="lead" value="{%s}" />
    </filter>
    <link-entity name="lead" from="leadid" to="cad_responsavel" visible="false" link-type="outer" alias="a_952d28142ca440d8b2f654054ba0d2b1">
	      <attribute name="mobilephone" />
	      <attribute name="pos_origem_positivo" />
	      <attribute name="col_visitasrealizadas" />
	    </link-entity>
	    <link-entity name="cad_servicoeducacional" from="cad_servicoeducacionalid" to="crm_servicoeducacionalinteresse" visible="false" link-type="outer" alias="a_f9d768aae4e34d27a9a95edeb6498c43">
	      <attribute name="col_segmento" />
	    </link-entity>
	  </entity>
	</fetch>';
        $options    = get_option( 'positivo_crm_options', array() );
        // Busca o template salvo para alunos, caso exista
        $fetch_xml  = isset( $options['fetch_xml_aluno'] ) && ! empty( $options['fetch_xml_aluno'] ) ? $options['fetch_xml_aluno'] : $default_template;
        $template_raw = $fetch_xml; // copia para manipulação
        // Sanitiza o ID do responsável
        $search_id    = sanitize_text_field( $responsavel_id );
        // Substitui placeholder %s ou marcadores personalizados pelo ID do responsável
        if ( strpos( $template_raw, '%s' ) !== false ) {
            $fetch_xml = sprintf( $template_raw, $search_id );
        } else {
            $fetch_xml = str_replace( array( '{responsavel_id}', '{id}', '{leadid}' ), $search_id, $template_raw );
        }
        // Determina o método de requisição para consulta de aluno
        $options = get_option( 'positivo_crm_options', array() );
        $method  = isset( $options['method_aluno'] ) ? strtoupper( $options['method_aluno'] ) : 'GET';
        if ( ! in_array( $method, array( 'GET', 'POST' ), true ) ) {
            $method = 'GET';
        }
        return $this->protected_request( $method, $fetch_xml );
    }
	/**
	 * Requisição: Consulta Séries Escolar.
	 *
	 * Retorna a lista de todas as séries escolares disponíveis no CRM.
	 *
	 * @return array|WP_Error O resultado da busca ou WP_Error.
	 */
	public function get_series_escolares() {
		// FetchXML da requisição "Consulta Séries Escolar"
		// Permite substituição pelo template salvo nas configurações
		$default_fetch = '<fetch version="1.0" output-format="xml-platform" mapping="logical" distinct="false">
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
</fetch>';
		$options    = get_option( 'positivo_crm_options', array() );
		$fetch_xml  = isset( $options['fetch_xml_series'] ) && ! empty( $options['fetch_xml_series'] ) ? $options['fetch_xml_series'] : $default_fetch;
		// Determina o método de requisição (GET ou POST) a partir das opções salvas
		$method  = isset( $options['method_series'] ) ? strtoupper( $options['method_series'] ) : 'GET';
		if ( ! in_array( $method, array( 'GET', 'POST' ), true ) ) {
			$method = 'GET';
		}
		return $this->protected_request( $method, $fetch_xml );
	}

	/**
	 * Busca alunos (Leads) associados a um responsável.
	 *
	 * @param string $responsavel_id ID do responsável (Lead).
	 * @return array|WP_Error Array de alunos ou WP_Error em caso de falha.
	 */
	public function get_students_by_responsavel( $responsavel_id ) {
		// FetchXML para buscar alunos (Leads) relacionados ao responsável
		// Ajuste conforme a estrutura do seu CRM
		$fetch_xml = '<fetch version="1.0" output-format="xml-platform" mapping="logical" distinct="false">
  <entity name="lead">
    <attribute name="leadid" />
    <attribute name="fullname" />
    <attribute name="col_turnointeresse" />
    <attribute name="col_serieinteresse" />
    <filter type="and">
      <condition attribute="parentcontactid" operator="eq" value="' . esc_attr( $responsavel_id ) . '" />
      <condition attribute="statecode" operator="eq" value="0" />
    </filter>
  </entity>
</fetch>';

		return $this->protected_request( 'GET', $fetch_xml );
	}

	// Métodos de Callback AJAX
	// =========================================================================

	/**
	 * Callback AJAX para obter a lista de unidades.
	 */
	public function ajax_get_units() {
		// Endpoint público - não requer autenticação

		$response = $this->get_unidades();

		if ( is_wp_error( $response ) ) {
			wp_send_json_error( array(
				'message' => $response->get_error_message(),
				'code'    => $response->get_error_code(),
				'data'    => $response->get_error_data(),
			) );
		}

		wp_send_json_success( $response );
	}

	/**
	 * Callback AJAX para buscar responsável.
	 */
	public function ajax_search_responsavel() {
		// Endpoint público - não requer autenticação

		$fullname = isset( $_POST['fullname'] ) ? sanitize_text_field( wp_unslash( $_POST['fullname'] ) ) : '';

		if ( empty( $fullname ) ) {
			wp_send_json_error( array( 'message' => 'Nome completo do responsável é obrigatório.' ) );
		}

		$response = $this->search_responsavel( $fullname );

		if ( is_wp_error( $response ) ) {
			wp_send_json_error( array(
				'message' => $response->get_error_message(),
				'code'    => $response->get_error_code(),
				'data'    => $response->get_error_data(),
			) );
		}

		wp_send_json_success( $response );
	}

	/**
	 * Callback AJAX para obter a lista de séries escolares.
	 */
	public function ajax_get_series() {
		// Endpoint público - não requer autenticação

		$response = $this->get_series_escolares();

		if ( is_wp_error( $response ) ) {
			wp_send_json_error( array(
				'message' => $response->get_error_message(),
				'code'    => $response->get_error_code(),
				'data'    => $response->get_error_data(),
			) );
		}

		wp_send_json_success( $response );
	}

	/**
	 * Callback AJAX para buscar alunos do responsável.
	 */
	public function ajax_get_students() {
		// Endpoint público - não requer autenticação

		$responsavel_id = isset( $_POST['responsavel_id'] ) ? sanitize_text_field( wp_unslash( $_POST['responsavel_id'] ) ) : '';

		if ( empty( $responsavel_id ) ) {
			wp_send_json_error( array( 'message' => 'ID do responsável não fornecido.' ) );
		}

		$response = $this->get_students_by_responsavel( $responsavel_id );

		if ( is_wp_error( $response ) ) {
			wp_send_json_error( array(
				'message' => $response->get_error_message(),
				'code'    => $response->get_error_code(),
				'data'    => $response->get_error_data(),
			) );
		}

		wp_send_json_success( $response );
	}

	/**
	 * Callback AJAX para submeter o agendamento (Exemplo Simplificado).
	 *
	 * NOTA: A submissão real de agendamento não está no Postman fornecido.
	 * Esta função é um placeholder para a lógica de submissão do formulário.
	 * Idealmente, ela usaria os dados do formulário para criar um novo Lead/Agendamento
	 * na API do CRM.
	 */
	public function ajax_submit_agendamento() {
		// Endpoint público - não requer autenticação

		// 1. Validar e sanitizar os dados do formulário
		$form_data = array();
		parse_str( isset( $_POST['form_data'] ) ? wp_unslash( $_POST['form_data'] ) : '', $form_data );

		// Exemplo de validação básica
		if ( empty( $form_data['responsavel_nome'] ) || empty( $form_data['aluno_nome'] ) ) {
			wp_send_json_error( array( 'message' => 'Dados incompletos no formulário.' ) );
		}

		// 2. Lógica de integração com a API para criar o agendamento
		// Como não temos a requisição de criação de Lead/Agendamento, vamos simular o sucesso.
		// Em um cenário real, você faria uma requisição POST/PUT para a API aqui.
		
		// Exemplo de como seria a chamada para a API (hipotética):
		// $api_response = $this->create_agendamento_lead( $form_data );
		
		// if ( is_wp_error( $api_response ) ) {
		// 	wp_send_json_error( array( 'message' => 'Falha ao criar agendamento no CRM.', 'details' => $api_response->get_error_message() ) );
		// }

        // Persiste o agendamento no banco de dados
        if ( class_exists( 'Positivo_CRM_Admin' ) && method_exists( 'Positivo_CRM_Admin', 'insert_agendamento_from_frontend' ) ) {
            Positivo_CRM_Admin::insert_agendamento_from_frontend( $form_data );
        }
        // Retorna sucesso ao frontend
        wp_send_json_success( array( 'message' => 'Agendamento submetido com sucesso.' ) );
	}

	/**
	 * Registra os endpoints da API REST.
	 */
	public function register_rest_routes() {
		// Endpoint para obter unidades
		register_rest_route( 'positivocrm/v1', '/units', array(
			'methods'  => 'GET',
			'callback' => array( $this, 'rest_get_units' ),
			'permission_callback' => '__return_true', // Acesso público
		) );

		// Endpoint para obter séries
		register_rest_route( 'positivocrm/v1', '/series', array(
			'methods'  => 'GET',
			'callback' => array( $this, 'rest_get_series' ),
			'permission_callback' => '__return_true',
		) );

		// Endpoint para buscar responsável
		register_rest_route( 'positivocrm/v1', '/search-responsavel', array(
			'methods'  => 'POST',
			'callback' => array( $this, 'rest_search_responsavel' ),
			'permission_callback' => '__return_true',
		) );

		// Endpoint para obter alunos
		register_rest_route( 'positivocrm/v1', '/get-students', array(
			'methods'  => 'POST',
			'callback' => array( $this, 'rest_get_students' ),
			'permission_callback' => '__return_true',
		) );

		// Endpoint para obter horários
		register_rest_route( 'positivocrm/v1', '/get-times', array(
			'methods'  => 'POST',
			'callback' => array( $this, 'rest_get_times' ),
			'permission_callback' => '__return_true',
		) );

		// Endpoint para submeter agendamento
		register_rest_route( 'positivocrm/v1', '/submit-agendamento', array(
			'methods'  => 'POST',
			'callback' => array( $this, 'rest_submit_agendamento' ),
			'permission_callback' => '__return_true',
		) );
	}

	/**
	 * Callback REST para obter unidades.
	 */
	public function rest_get_units( $request ) {
		$response = $this->get_unidades();
		if ( is_wp_error( $response ) ) {
			return new WP_REST_Response( array( 'message' => $response->get_error_message() ), 500 );
		}
		return new WP_REST_Response( $response, 200 );
	}

	/**
	 * Callback REST para obter séries.
	 */
	public function rest_get_series( $request ) {
		$response = $this->get_series();
		if ( is_wp_error( $response ) ) {
			return new WP_REST_Response( array( 'message' => $response->get_error_message() ), 500 );
		}
		return new WP_REST_Response( $response, 200 );
	}

	/**
	 * Callback REST para buscar responsável.
	 */
	public function rest_search_responsavel( $request ) {
		$fullname = $request->get_param( 'fullname' );
		if ( empty( $fullname ) ) {
			return new WP_REST_Response( array( 'message' => 'Nome completo é obrigatório.' ), 400 );
		}
		$response = $this->search_responsavel_by_name( $fullname );
		if ( is_wp_error( $response ) ) {
			return new WP_REST_Response( array( 'message' => $response->get_error_message() ), 500 );
		}
		return new WP_REST_Response( $response, 200 );
	}

	/**
	 * Callback REST para obter alunos.
	 */
	public function rest_get_students( $request ) {
		$responsavel_id = $request->get_param( 'responsavel_id' );
		if ( empty( $responsavel_id ) ) {
			return new WP_REST_Response( array( 'message' => 'ID do responsável é obrigatório.' ), 400 );
		}
		$response = $this->get_students_by_responsavel( $responsavel_id );
		if ( is_wp_error( $response ) ) {
			return new WP_REST_Response( array( 'message' => $response->get_error_message() ), 500 );
		}
		return new WP_REST_Response( $response, 200 );
	}

	/**
	 * Callback REST para obter horários (simulado).
	 */
	public function rest_get_times( $request ) {
		$date = $request->get_param( 'date' );
		$unit = $request->get_param( 'unit' );
		// Lógica para obter horários (simulada)
		$times = array( '09:00', '10:00', '11:00', '14:00', '15:00' );
		return new WP_REST_Response( array( 'times' => $times ), 200 );
	}

	/**
	 * Callback REST para submeter agendamento.
	 */
	public function rest_submit_agendamento( $request ) {
		$form_data = $request->get_json_params();
		if ( empty( $form_data ) ) {
			return new WP_REST_Response( array( 'message' => 'Dados do formulário são obrigatórios.' ), 400 );
		}
		// Lógica para submeter agendamento (reutilizar do AJAX)
		if ( class_exists( 'Positivo_CRM_Admin' ) && method_exists( 'Positivo_CRM_Admin', 'insert_agendamento_from_frontend' ) ) {
			Positivo_CRM_Admin::insert_agendamento_from_frontend( $form_data );
		}
		return new WP_REST_Response( array( 'success' => true, 'message' => 'Agendamento realizado com sucesso!' ), 200 );
	}
}
