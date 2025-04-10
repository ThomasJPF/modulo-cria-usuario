<?php
/**
 * Serviço de registro de logs
 *
 * Este serviço gerencia o registro de logs das ações do módulo.
 * 
 * @package     UserManager\Services
 * @version     1.0
 * @author      Zabbix User Manager Team
 * @copyright   2024
 */

namespace UserManager\Services;

use UserManager\Module;

/**
 * Classe LogService - Serviço para registrar logs
 */
class LogService {
    /**
     * Registra uma ação no log
     * 
     * @param string $action Nome da ação
     * @param array  $data   Dados relacionados à ação
     * 
     * @return bool
     */
    public function logAction(string $action, array $data = []): bool {
        // Obter informações do usuário atual
        $currentUser = [
            'userid' => isset($_SESSION['userid']) ? (int)$_SESSION['userid'] : 0,
            'username' => isset($_SESSION['username']) ? $_SESSION['username'] : 'system',
            'ip' => isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0'
        ];
        
        // Registrar no log
        $log = [
            'timestamp' => time(),
            'action' => $action,
            'user_id' => $currentUser['userid'],
            'username' => $currentUser['username'],
            'ip' => $currentUser['ip'],
            'data' => $data
        ];
        
        // Em um ambiente real, você poderia gravar em um arquivo ou banco de dados
        $this->writeLog($log);
        
        return true;
    }
    
    /**
     * Obtém ações de um usuário específico
     * 
     * @param int $userId ID do usuário
     * @param int $limit  Número máximo de registros para retornar
     * 
     * @return array
     */
    public function getUserActions(int $userId, int $limit = 50): array {
        // Em um ambiente real, você buscaria no arquivo ou banco de dados
        // Esta é uma implementação simulada
        
        $logs = $this->readLogs();
        
        // Filtrar logs pelo ID do usuário
        $userLogs = array_filter($logs, function($log) use ($userId) {
            return isset($log['data']['userid']) && $log['data']['userid'] == $userId;
        });
        
        // Ordenar por timestamp (mais recente primeiro)
        usort($userLogs, function($a, $b) {
            return $b['timestamp'] - $a['timestamp'];
        });
        
        // Limitar o número de registros
        return array_slice($userLogs, 0, $limit);
    }
    
    /**
     * Escreve um log
     * 
     * @param array $log Dados do log
     * 
     * @return bool
     */
    private function writeLog(array $log): bool {
        // Obter caminho do arquivo de log
        $logDir = __DIR__ . '/../logs';
        $logFile = $logDir . '/user_actions.log';
        
        // Criar diretório se não existir
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        // Formatar log para JSON
        $logJson = json_encode($log) . PHP_EOL;
        
        // Escrever no arquivo
        file_put_contents($logFile, $logJson, FILE_APPEND);
        
        return true;
    }
    
    /**
     * Lê os logs
     * 
     * @return array
     */
    private function readLogs(): array {
        $logFile = __DIR__ . '/../logs/user_actions.log';
        
        if (!file_exists($logFile)) {
            return [];
        }
        
        $logs = [];
        $lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        foreach ($lines as $line) {
            $log = json_decode($line, true);
            if ($log) {
                $logs[] = $log;
            }
        }
        
        return $logs;
    }
} 