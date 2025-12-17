<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Classe para gerenciar o Shortcode do formulário de agendamento.
 */
class Positivo_CRM_Shortcode {

	/**
	 * Construtor.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'register_shortcode' ) );
	}

	/**
	 * Registra o shortcode.
	 */
	public function register_shortcode() {
		add_shortcode( 'positivo_agendamento', array( $this, 'render_shortcode' ) );
	}

	/**
	 * Renderiza o conteúdo do shortcode.
	 *
	 * @param array $atts Atributos do shortcode.
	 * @return string O HTML a ser exibido.
	 */
	public function render_shortcode( $atts ) {
        // Em vez de incluir diretamente o template, usamos o método da classe de admin
        // que recupera o HTML personalizado (ou o padrão).
        if ( class_exists( 'Positivo_CRM_Admin' ) ) {
            return Positivo_CRM_Admin::get_form_html();
        }
        // Fallback para incluir o template original caso a classe não exista
        ob_start();
        include POSITIVO_CRM_PATH . 'templates/agendamento-form.php';
        return ob_get_clean();
	}
}
