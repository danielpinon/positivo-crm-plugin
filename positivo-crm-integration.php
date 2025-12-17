<?php
require_once __DIR__.'/debug-helper.php';
/**
 * Plugin Name: Positivo CRM Educational Integration
 * Plugin URI:  https://github.com/
 * Description: Plugin para integrar o frontend de agendamento com a API do CRM Educacional do Colégio Positivo.
 * Version:     1.1.0 - Final
 * Author:      Mentores
 * Author URI:  https://mentores.com.br
 * License:     GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: positivo-crm
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

define( 'POSITIVO_CRM_PATH', plugin_dir_path( __FILE__ ) );
define( 'POSITIVO_CRM_URL', plugin_dir_url( __FILE__ ) );

// Exporte a versão do plugin de forma programática. Isso é usado para quebrar caches de scripts
// quando assets são atualizados. Se você alterar o número de versão no cabeçalho do plugin,
// atualize esta constante também.
if ( ! defined( 'POSITIVO_CRM_VERSION' ) ) {
    // Atualiza a versão do plugin para corresponder ao cabeçalho. Ao alterar o
    // número de versão acima, atualize também este valor para quebrar o cache
    // dos assets do plugin (JS/CSS).
    // Incrementado para 1.1.0 ao adicionar o carregamento dinâmico das
    // unidades agrupadas por cidade e a exposição do ID do colégio
    // (cad_categoriaid) no frontend. Essa mudança ajuda a invalidar
    // corretamente o cache dos arquivos JS/CSS quando eles são
    // modificados.
    define( 'POSITIVO_CRM_VERSION', '1.1.0' );
}

// Inclui as classes principais do plugin
require_once POSITIVO_CRM_PATH . 'includes/class-positivo-crm-logger.php';
require_once POSITIVO_CRM_PATH . 'includes/class-positivo-crm-integration.php';
// Inclui a classe de administração para que possa ser instanciada posteriormente
require_once POSITIVO_CRM_PATH . 'includes/class-positivo-crm-admin.php';

/**
 * Executa rotinas de instalação quando o plugin é ativado.
 *
 * Cria as tabelas necessárias no banco de dados e define opções padrão.
 */
function positivo_crm_activate() {
    // Garante que a classe esteja disponível
    if ( class_exists( 'Positivo_CRM_Admin' ) ) {
        Positivo_CRM_Admin::install_tables();
    }
}

// Registra a função de ativação
register_activation_hook( __FILE__, 'positivo_crm_activate' );

/**
 * Função principal para iniciar o plugin.
 *
 * @return Positivo_CRM_Integration Instância única da classe principal.
 */
function positivo_crm_run() {
	return Positivo_CRM_Integration::get_instance();
}

// Inicia o plugin
add_action( 'plugins_loaded', 'positivo_crm_run' );

/**
 * Registra o bloco Gutenberg para o formulário de agendamento.
 *
 * O bloco utiliza renderização dinâmica via callback para exibir o
 * conteúdo gerado por Positivo_CRM_Admin::get_form_html(). No editor,
 * apenas um placeholder é mostrado.
 */
function positivo_crm_register_block() {
    // Registra o script que define o bloco no editor
    wp_register_script(
        'positivo-crm-block-agendamento',
        POSITIVO_CRM_URL . 'assets/js/block-agendamento.js',
        array( 'wp-blocks', 'wp-element', 'wp-i18n' ),
        '1.0',
        true
    );
    // Registra o bloco
    register_block_type( 'positivo-crm/agendamento', array(
        'editor_script'   => 'positivo-crm-block-agendamento',
        'render_callback' => function() {
            if ( class_exists( 'Positivo_CRM_Admin' ) ) {
                return Positivo_CRM_Admin::get_form_html();
            }
            return '';
        },
    ) );
}
add_action( 'init', 'positivo_crm_register_block' );
