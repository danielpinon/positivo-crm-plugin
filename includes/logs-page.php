<?php
/**
 * Página de Visualização de Logs do Plugin
 *
 * @package Positivo_CRM
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Processar ações
if ( isset( $_POST['clear_log'] ) && check_admin_referer( 'positivo_crm_clear_log' ) ) {
    Positivo_CRM_Logger::clear_log();
    echo '<div class="notice notice-success"><p>' . __( 'Log limpo com sucesso!', 'positivo-crm' ) . '</p></div>';
}

$log_content = Positivo_CRM_Logger::get_log_content( 1000 );
$log_size = Positivo_CRM_Logger::get_log_size();
$log_path = Positivo_CRM_Logger::get_log_path();
$debug_enabled = Positivo_CRM_Logger::is_debug_enabled();
?>

<div class="wrap">
    <h1><?php _e( 'Logs de Debug', 'positivo-crm' ); ?></h1>
    
    <div class="positivo-crm-logs-header" style="margin: 20px 0; padding: 15px; background: #fff; border-left: 4px solid #2271b1;">
        <h2 style="margin-top: 0;"><?php _e( 'Informações do Log', 'positivo-crm' ); ?></h2>
        
        <p>
            <strong><?php _e( 'Status do Debug:', 'positivo-crm' ); ?></strong>
            <?php if ( $debug_enabled ) : ?>
                <span style="color: #46b450; font-weight: bold;">✓ <?php _e( 'Ativado', 'positivo-crm' ); ?></span>
            <?php else : ?>
                <span style="color: #dc3232; font-weight: bold;">✗ <?php _e( 'Desativado', 'positivo-crm' ); ?></span>
                <br>
                <small><?php _e( 'Ative o debug em Positivo CRM > Configurações para registrar logs detalhados.', 'positivo-crm' ); ?></small>
            <?php endif; ?>
        </p>
        
        <p>
            <strong><?php _e( 'Caminho do Arquivo:', 'positivo-crm' ); ?></strong>
            <code><?php echo esc_html( $log_path ); ?></code>
        </p>
        
        <p>
            <strong><?php _e( 'Tamanho do Arquivo:', 'positivo-crm' ); ?></strong>
            <?php echo size_format( $log_size ); ?>
        </p>
        
        <p>
            <strong><?php _e( 'Últimas Linhas:', 'positivo-crm' ); ?></strong>
            1000 linhas (máximo)
        </p>
    </div>
    
    <div class="positivo-crm-logs-actions" style="margin: 20px 0;">
        <form method="post" style="display: inline-block;">
            <?php wp_nonce_field( 'positivo_crm_clear_log' ); ?>
            <button type="submit" name="clear_log" class="button button-secondary" onclick="return confirm('<?php _e( 'Tem certeza que deseja limpar todos os logs?', 'positivo-crm' ); ?>');">
                <?php _e( 'Limpar Logs', 'positivo-crm' ); ?>
            </button>
        </form>
        
        <a href="<?php echo admin_url( 'admin.php?page=positivo-crm-logs&download=1' ); ?>" class="button button-secondary">
            <?php _e( 'Baixar Log', 'positivo-crm' ); ?>
        </a>
        
        <button type="button" class="button button-secondary" onclick="location.reload();">
            <?php _e( 'Atualizar', 'positivo-crm' ); ?>
        </button>
    </div>
    
    <div class="positivo-crm-logs-legend" style="margin: 20px 0; padding: 10px; background: #f0f0f1; border-radius: 4px;">
        <strong><?php _e( 'Legenda:', 'positivo-crm' ); ?></strong>
        <span style="margin-left: 10px; color: #0073aa;">[DEBUG]</span> - Informações detalhadas de depuração
        <span style="margin-left: 10px; color: #46b450;">[INFO]</span> - Informações gerais
        <span style="margin-left: 10px; color: #f0b849;">[WARNING]</span> - Avisos
        <span style="margin-left: 10px; color: #dc3232;">[ERROR]</span> - Erros
        <span style="margin-left: 10px; color: #a00;">[CRITICAL]</span> - Erros críticos
    </div>
    
    <div class="positivo-crm-logs-content" style="margin: 20px 0;">
        <h2><?php _e( 'Conteúdo do Log', 'positivo-crm' ); ?></h2>
        
        <?php if ( empty( trim( $log_content ) ) ) : ?>
            <div class="notice notice-info">
                <p><?php _e( 'Nenhum log disponível.', 'positivo-crm' ); ?></p>
            </div>
        <?php else : ?>
            <textarea readonly style="width: 100%; height: 600px; font-family: monospace; font-size: 12px; padding: 10px; background: #1e1e1e; color: #d4d4d4; border: 1px solid #ccc; border-radius: 4px;"><?php echo esc_textarea( $log_content ); ?></textarea>
        <?php endif; ?>
    </div>
    
    <style>
        .positivo-crm-logs-content textarea {
            resize: vertical;
        }
        
        .positivo-crm-logs-content textarea::-webkit-scrollbar {
            width: 12px;
        }
        
        .positivo-crm-logs-content textarea::-webkit-scrollbar-track {
            background: #2b2b2b;
        }
        
        .positivo-crm-logs-content textarea::-webkit-scrollbar-thumb {
            background: #555;
            border-radius: 6px;
        }
        
        .positivo-crm-logs-content textarea::-webkit-scrollbar-thumb:hover {
            background: #777;
        }
    </style>
</div>

<?php
// Processar download
if ( isset( $_GET['download'] ) && $_GET['download'] == '1' ) {
    $log_file = Positivo_CRM_Logger::get_log_path();
    
    if ( file_exists( $log_file ) ) {
        header( 'Content-Type: text/plain' );
        header( 'Content-Disposition: attachment; filename="positivo-crm-debug-' . date( 'Y-m-d-H-i-s' ) . '.log"' );
        header( 'Content-Length: ' . filesize( $log_file ) );
        readfile( $log_file );
        exit;
    }
}
?>
