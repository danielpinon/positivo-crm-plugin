<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Classe principal do Plugin de Integração com o CRM Positivo.
 */
class Positivo_CRM_Integration {

	/**
	 * Instância única da classe.
	 *
	 * @var Positivo_CRM_Integration
	 */
	protected static $instance = null;

	/**
	 * Retorna a instância única da classe.
	 *
	 * @return Positivo_CRM_Integration
	 */
	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Construtor.
	 */
	private function __construct() {
		$this->includes();
		$this->hooks();
	}

	/**
	 * Inclui os arquivos necessários.
	 */
	private function includes() {
		require_once POSITIVO_CRM_PATH . 'includes/class-positivo-crm-api.php';
		require_once POSITIVO_CRM_PATH . 'includes/class-positivo-crm-shortcode.php';
	}

	/**
	 * Registra os hooks do WordPress.
	 */
	private function hooks() {
		// Enqueue scripts e styles
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		
		// Inicializa o shortcode
		new Positivo_CRM_Shortcode();
		
		// Inicializa o API Client (pode ser necessário para configurações ou hooks)
		new Positivo_CRM_API();

        // Somente no painel de administração inicializa a classe de admin
        if ( is_admin() ) {
            new Positivo_CRM_Admin();
        }
	}
	
	/**
	 * Enqueue scripts e styles necessários.
	 */
	public function enqueue_assets() {
        // Enfileira o CSS
        // Usa POSITIVO_CRM_VERSION como versão para quebrar cache quando o
        // plugin for atualizado. Isso força o navegador a carregar a nova versão
        // do arquivo agendamento.css.
        wp_enqueue_style( 'positivo-crm-agendamento', POSITIVO_CRM_URL . 'assets/css/agendamento.css', array(), POSITIVO_CRM_VERSION );

		// Enfileira o JS
        // O parâmetro de versão usa POSITIVO_CRM_VERSION para quebrar o cache
        // automaticamente sempre que o plugin for atualizado. Isso garante que
        // a versão mais recente do script seja carregada.
        wp_enqueue_script( 'positivo-crm-frontend', POSITIVO_CRM_URL . 'assets/js/frontend.js', array( 'jquery' ), POSITIVO_CRM_VERSION, true );

		// Localiza dados para o script JS (será usado na fase 5 para AJAX)
		wp_localize_script( 'positivo-crm-frontend', 'PositivoCRM', array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'rest_url' => rest_url( 'positivocrm/v1/' ),
			'nonce'    => wp_create_nonce( 'positivo-crm-nonce' ),
		) );
	}
}
