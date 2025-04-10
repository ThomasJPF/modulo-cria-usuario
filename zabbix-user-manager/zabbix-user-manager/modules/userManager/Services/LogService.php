<?php
/**
 * Serviço de logs para o módulo UserManager
 *
 * Este serviço gerencia o registro e consulta de logs de ações de usuários.
 * 
 * @package     UserManager\Services
 * @version     1.0
 * @author      Zabbix User Manager Team
 * @copyright   2024
 */

namespace Modules\UserManager\Services;

use API;
use Modules\UserManager\Module;
use DB;
use DBException;
use Exception;

/**
 * Classe LogService - Gerencia logs de ações de usuários
 */
class LogService {
    /** @var string Nome da tabela de logs */
    private const LOG_TABLE = 'user_manager_logs';
    
    /**
     * Registra uma ação de usuário
     * 
     * @param string $userid ID do usuário alvo
     * @param string $action Tipo de ação
     * @param string $author Autor da ação
     * @param string $details Detalhes adicionais (opcional)
     * 
     * @return bool
     * @throws Exception
     */
    public static function logUserAction(string $userid, string $action, string $author, string $details = ''): bool {
        // Verificar se o módulo está registrando logs
        $config = Module::getConfig();
        if (!$config['log']['enabled']) {
            return true;
        }
        
        try {
            // Verificar se a tabela existe
            self::createTableIfNotExists();
            
            // Inserir log
            DB::insert(self::LOG_TABLE, [
                'userid' => $userid,
                'action' => $action,
                'author' => $author,
                'details' => $details,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'timestamp' => time()
            ], false);
            
            return true;
        } 
        catch (DBException $e) {
            error_log('Erro ao registrar log: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtém ações de um usuário específico
     * 
     * @param string $userid ID do usuário
     * @param int $limit Limite de registros
     * 
     * @return array
     */
    public static function getUserActions(string $userid, int $limit = 50): array {
        try {
            // Verificar se a tabela existe
            if (!self::tableExists()) {
                return [];
            }
            
            // Consultar logs
            $logs = [];
            $db_logs = DBselect(
                'SELECT * FROM ' . self::LOG_TABLE . 
                ' WHERE userid=' . zbx_dbstr($userid) .
                ' ORDER BY timestamp DESC'.
                ' LIMIT ' . $limit
            );
            
            while ($log = DBfetch($db_logs)) {
                // Formatar timestamp
                $log['time'] = date('Y-m-d H:i:s', $log['timestamp']);
                
                // Traduzir tipo de ação
                $log['action_text'] = self::getActionDescription($log['action']);
                
                $logs[] = $log;
            }
            
            return $logs;
        } 
        catch (Exception $e) {
            error_log('Erro ao obter logs: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Limpa logs antigos com base na configuração
     * 
     * @return bool
     */
    public static function cleanOldLogs(): bool {
        try {
            if (!self::tableExists()) {
                return true;
            }
            
            // Obter configuração
            $config = Module::getConfig();
            $keep_days = $config['log']['keep_days'];
            
            if ($keep_days <= 0) {
                return true;
            }
            
            // Calcular timestamp para exclusão
            $delete_before = time() - ($keep_days * 24 * 3600);
            
            // Excluir logs antigos
            DB::execute(
                'DELETE FROM ' . self::LOG_TABLE . 
                ' WHERE timestamp<' . $delete_before
            );
            
            return true;
        } 
        catch (Exception $e) {
            error_log('Erro ao limpar logs antigos: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Cria a tabela de logs se não existir
     * 
     * @return bool
     * @throws DBException
     */
    private static function createTableIfNotExists(): bool {
        if (!self::tableExists()) {
            // SQL para criar a tabela
            $sql = 'CREATE TABLE ' . self::LOG_TABLE . ' (' .
                   'id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,' .
                   'userid BIGINT UNSIGNED NOT NULL,' .
                   'action VARCHAR(50) NOT NULL,' .
                   'author VARCHAR(100) NOT NULL,' .
                   'details TEXT,' .
                   'ip VARCHAR(45) NOT NULL,' .
                   'timestamp INT NOT NULL,' .
                   'INDEX (userid),' .
                   'INDEX (timestamp)' .
                   ') ENGINE=InnoDB';
            
            // Executar SQL
            DB::execute($sql);
            
            return true;
        }
        
        return true;
    }
    
    /**
     * Verifica se a tabela de logs existe
     * 
     * @return bool
     */
    private static function tableExists(): bool {
        $result = DBselect(
            'SHOW TABLES LIKE ' . zbx_dbstr(self::LOG_TABLE)
        );
        
        return (bool)DBfetch($result);
    }
    
    /**
     * Obtém descrição textual para um tipo de ação
     * 
     * @param string $action Código da ação
     * 
     * @return string
     */
    private static function getActionDescription(string $action): string {
        $descriptions = [
            'create' => 'Criação de usuário',
            'update' => 'Atualização de usuário',
            'delete' => 'Exclusão de usuário',
            'activate' => 'Ativação de usuário',
            'deactivate' => 'Desativação de usuário',
            'password_reset' => 'Redefinição de senha',
            'login' => 'Login no sistema',
            'logout' => 'Logout do sistema',
            'failed_login' => 'Tentativa de login malsucedida'
        ];
        
        return $descriptions[$action] ?? $action;
    }
} 