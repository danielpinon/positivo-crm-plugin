<?php
require_once __DIR__.'/debug-helper.php';
/**
 * Plugin Name: Positivo CRM Educational Integration
 * Plugin URI:  https://github.com/
 * Description: Plugin para integrar o frontend de agendamento com a API do CRM Educacional do Colégio Positivo.
 * Version:     1.1.12
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
define( 'POSITIVO_CRM_DB_VERSION', '1.1.9' );

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
    define( 'POSITIVO_CRM_VERSION', '1.1.9' );
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
function positivo_crm_install_site() {
    if ( class_exists( 'Positivo_CRM_Admin' ) ) {
        Positivo_CRM_Admin::install_tables();
    }
}

/**
 * Executa instalação do plugin no contexto do site atual ou de toda a rede.
 *
 * @param bool $network_wide Se a ativação ocorreu em rede.
 */
function positivo_crm_activate( $network_wide ) {
    if ( is_multisite() && $network_wide ) {
        $site_ids = get_sites( array(
            'fields' => 'ids',
            'number' => 0,
        ) );

        foreach ( $site_ids as $site_id ) {
            switch_to_blog( (int) $site_id );
            positivo_crm_install_site();
            restore_current_blog();
        }

        return;
    }

    positivo_crm_install_site();
}

/**
 * Garante que o schema do plugin exista e esteja atualizado no site atual.
 */
function positivo_crm_maybe_upgrade_site() {
    $installed_version = get_option( 'positivo_crm_db_version' );

    if ( $installed_version === POSITIVO_CRM_DB_VERSION ) {
        return;
    }

    positivo_crm_install_site();
}

/**
 * Instala o plugin automaticamente para novos sites em uma rede multisite.
 *
 * @param WP_Site $new_site Objeto do novo site.
 */
function positivo_crm_on_initialize_site( $new_site ) {
    $active_sitewide_plugins = (array) get_site_option( 'active_sitewide_plugins', array() );

    if ( ! isset( $active_sitewide_plugins[ plugin_basename( __FILE__ ) ] ) ) {
        return;
    }

    switch_to_blog( (int) $new_site->blog_id );
    positivo_crm_install_site();
    restore_current_blog();
}

/**
 * Compatibilidade com hooks legados de criação de blog em multisite.
 *
 * @param int $blog_id ID do novo site.
 */
function positivo_crm_on_new_blog( $blog_id ) {
    $active_sitewide_plugins = (array) get_site_option( 'active_sitewide_plugins', array() );

    if ( ! isset( $active_sitewide_plugins[ plugin_basename( __FILE__ ) ] ) ) {
        return;
    }

    switch_to_blog( (int) $blog_id );
    positivo_crm_install_site();
    restore_current_blog();
}

// Registra a função de ativação
register_activation_hook( __FILE__, 'positivo_crm_activate' );
add_action( 'plugins_loaded', 'positivo_crm_maybe_upgrade_site', 5 );
add_action( 'wp_initialize_site', 'positivo_crm_on_initialize_site' );
add_action( 'wpmu_new_blog', 'positivo_crm_on_new_blog' );

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
