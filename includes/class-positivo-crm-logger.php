<?php
/**
 * Classe de Logging para o Plugin Positivo CRM
 *
 * @package Positivo_CRM
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Positivo_CRM_Logger {
    
    /**
     * Nome do arquivo de log
     */
    const LOG_FILE = 'positivo-crm-debug.log';
    
    /**
     * Níveis de log
     */
    const LEVEL_DEBUG = 'DEBUG';
    const LEVEL_INFO = 'INFO';
    const LEVEL_WARNING = 'WARNING';
    const LEVEL_ERROR = 'ERROR';
    const LEVEL_CRITICAL = 'CRITICAL';
    
    /**
     * Caminho completo do arquivo de log
     *
     * @var string
     */
    private static $log_path;
    
    /**
     * Verifica se o debug está habilitado
     *
     * @return bool
     */
    public static function is_debug_enabled() {
        $options = get_option( 'positivo_crm_options', array() );
        return isset( $options['enable_debug'] ) && $options['enable_debug'] === '1';
    }
    
    /**
     * Inicializa o logger
     */
    public static function init() {
        $upload_dir = wp_upload_dir();
        $log_dir = $upload_dir['basedir'] . '/positivo-crm-logs';
        
        // Criar diretório se não existir
        if ( ! file_exists( $log_dir ) ) {
            wp_mkdir_p( $log_dir );
            
            // Adicionar .htaccess para proteger logs
            $htaccess = $log_dir . '/.htaccess';
            if ( ! file_exists( $htaccess ) ) {
                file_put_contents( $htaccess, "Deny from all\n" );
            }
            
            // Adicionar index.php vazio
            $index = $log_dir . '/index.php';
            if ( ! file_exists( $index ) ) {
                file_put_contents( $index, "<?php\n// Silence is golden.\n" );
            }
        }
        
        self::$log_path = $log_dir . '/' . self::LOG_FILE;
    }
    
    /**
     * Escreve uma mensagem de log
     *
     * @param string $level Nível do log
     * @param string $message Mensagem
     * @param array $context Contexto adicional
     */
    private static function write_log( $level, $message, $context = array() ) {
        // Se debug não está habilitado, não loga (exceto erros críticos)
        if ( ! self::is_debug_enabled() && $level !== self::LEVEL_CRITICAL && $level !== self::LEVEL_ERROR ) {
            return;
        }
        
        if ( empty( self::$log_path ) ) {
            self::init();
        }
        
        $timestamp = current_time( 'Y-m-d H:i:s' );
        $user_id = get_current_user_id();
        $user_info = $user_id ? " [User: $user_id]" : " [Guest]";
        
        // Formatar contexto
        $context_str = '';
        if ( ! empty( $context ) ) {
            $context_str = "\n" . print_r( $context, true );
        }
        
        // Formatar mensagem
        $log_entry = sprintf(
            "[%s] [%s]%s %s%s\n",
            $timestamp,
            $level,
            $user_info,
            $message,
            $context_str
        );
        
        // Escrever no arquivo
        error_log( $log_entry, 3, self::$log_path );
        
        // Limitar tamanho do arquivo (10MB)
        self::rotate_log_if_needed();
    }
    
    /**
     * Rotaciona o arquivo de log se necessário
     */
    private static function rotate_log_if_needed() {
        if ( ! file_exists( self::$log_path ) ) {
            return;
        }
        
        $max_size = 10 * 1024 * 1024; // 10MB
        
        if ( filesize( self::$log_path ) > $max_size ) {
            $backup = self::$log_path . '.old';
            
            // Remove backup antigo
            if ( file_exists( $backup ) ) {
                unlink( $backup );
            }
            
            // Renomeia atual para backup
            rename( self::$log_path, $backup );
        }
    }
    
    /**
     * Log de debug
     *
     * @param string $message Mensagem
     * @param array $context Contexto
     */
    public static function debug( $message, $context = array() ) {
        self::write_log( self::LEVEL_DEBUG, $message, $context );
    }
    
    /**
     * Log de informação
     *
     * @param string $message Mensagem
     * @param array $context Contexto
     */
    public static function info( $message, $context = array() ) {
        self::write_log( self::LEVEL_INFO, $message, $context );
    }
    
    /**
     * Log de aviso
     *
     * @param string $message Mensagem
     * @param array $context Contexto
     */
    public static function warning( $message, $context = array() ) {
        self::write_log( self::LEVEL_WARNING, $message, $context );
    }
    
    /**
     * Log de erro
     *
     * @param string $message Mensagem
     * @param array $context Contexto
     */
    public static function error( $message, $context = array() ) {
        self::write_log( self::LEVEL_ERROR, $message, $context );
    }
    
    /**
     * Log crítico
     *
     * @param string $message Mensagem
     * @param array $context Contexto
     */
    public static function critical( $message, $context = array() ) {
        self::write_log( self::LEVEL_CRITICAL, $message, $context );
    }
    
    /**
     * Log de requisição HTTP
     *
     * @param string $method Método HTTP
     * @param string $url URL
     * @param array $args Argumentos da requisição
     * @param mixed $response Resposta
     */
    public static function log_http_request( $method, $url, $args = array(), $response = null ) {
        $context = array(
            'method' => $method,
            'url' => $url,
            'headers' => isset( $args['headers'] ) ? $args['headers'] : array(),
            'body' => isset( $args['body'] ) ? $args['body'] : '',
        );
        
        // Ocultar token de autenticação no log
        if ( isset( $context['headers']['Authorization'] ) ) {
            $context['headers']['Authorization'] = 'Bearer ***HIDDEN***';
        }
        
        if ( $response ) {
            if ( is_wp_error( $response ) ) {
                $context['response_error'] = $response->get_error_message();
                self::error( "HTTP Request Failed: $method $url", $context );
            } else {
                $context['response_code'] = wp_remote_retrieve_response_code( $response );
                $context['response_body'] = wp_remote_retrieve_body( $response );
                self::debug( "HTTP Request: $method $url", $context );
            }
        } else {
            self::debug( "HTTP Request Initiated: $method $url", $context );
        }
    }
    
    /**
     * Log de operação de banco de dados
     *
     * @param string $operation Operação (INSERT, UPDATE, DELETE, SELECT)
     * @param string $table Tabela
     * @param array $data Dados
     * @param mixed $result Resultado
     */
    public static function log_db_operation( $operation, $table, $data = array(), $result = null ) {
        $context = array(
            'operation' => $operation,
            'table' => $table,
            'data' => $data,
        );
        
        if ( $result !== null ) {
            if ( is_wp_error( $result ) ) {
                $context['error'] = $result->get_error_message();
                self::error( "DB Operation Failed: $operation on $table", $context );
            } else {
                $context['result'] = $result;
                self::debug( "DB Operation: $operation on $table", $context );
            }
        } else {
            self::debug( "DB Operation Initiated: $operation on $table", $context );
        }
    }
    
    /**
     * Obtém o conteúdo do log
     *
     * @param int $lines Número de linhas a retornar (padrão: 500)
     * @return string
     */
    public static function get_log_content( $lines = 500 ) {
        if ( empty( self::$log_path ) ) {
            self::init();
        }
        
        if ( ! file_exists( self::$log_path ) ) {
            return __( 'Nenhum log disponível.', 'positivo-crm' );
        }
        
        // Ler últimas N linhas
        $file = new SplFileObject( self::$log_path, 'r' );
        $file->seek( PHP_INT_MAX );
        $total_lines = $file->key();
        
        $start_line = max( 0, $total_lines - $lines );
        
        $file->seek( $start_line );
        $content = '';
        
        while ( ! $file->eof() ) {
            $content .= $file->fgets();
        }
        
        return $content;
    }
    
    /**
     * Limpa o arquivo de log
     */
    public static function clear_log() {
        if ( empty( self::$log_path ) ) {
            self::init();
        }
        
        if ( file_exists( self::$log_path ) ) {
            unlink( self::$log_path );
        }
        
        self::info( 'Log file cleared by user' );
    }
    
    /**
     * Obtém o caminho do arquivo de log
     *
     * @return string
     */
    public static function get_log_path() {
        if ( empty( self::$log_path ) ) {
            self::init();
        }
        
        return self::$log_path;
    }
    
    /**
     * Obtém o tamanho do arquivo de log
     *
     * @return int Tamanho em bytes
     */
    public static function get_log_size() {
        if ( empty( self::$log_path ) ) {
            self::init();
        }
        
        if ( ! file_exists( self::$log_path ) ) {
            return 0;
        }
        
        return filesize( self::$log_path );
    }
}

// Inicializar logger
Positivo_CRM_Logger::init();
