<?php
/**
 * Classe principal do módulo User Manager
 * 
 * Este módulo permite gerenciar usuários do Zabbix de forma simplificada,
 * com funcionalidades para criar usuários com senhas aleatórias e enviar por e-mail.
 * 
 * @package     UserManager
 * @version     1.0
 * @author      Zabbix User Manager Team
 * @copyright   2024
 */

namespace Modules\UserManager;

use APP\CModuleManager;
use CController as CControllerBase;

/**
 * Classe Module - Ponto de entrada principal para o módulo
 */
class Module extends \Core\CModule {
    /**
     * Inicializa o módulo.
     */
    public function init(): bool {
        // Registra eventos
        $this->setEventsHandlers([
            'profile.updated' => [$this, 'onProfileUpdate']
        ]);
        
        return true;
    }

    /**
     * Manipulador de evento quando um perfil de usuário é atualizado.
     * 
     * @param array $data Dados do evento
     * 
     * @return bool
     */
    public function onProfileUpdate(array $data): bool {
        // Aqui podemos adicionar lógica para rastrear alterações em perfis
        // Atualizar estatísticas, logs, etc.
        return true;
    }

    /**
     * Verifica se o usuário atual tem permissão para usar o módulo.
     * Apenas administradores Zabbix têm acesso.
     * 
     * @param CControllerBase $controller Controlador atual
     * 
     * @return bool
     */
    public static function checkPermissions(CControllerBase $controller): bool {
        return ($controller->getUserType() >= USER_TYPE_ZABBIX_ADMIN);
    }
    
    /**
     * Obtém a configuração do módulo.
     * 
     * @return array
     */
    public static function getConfig(): array {
        return include __DIR__ . '/config.php';
    }
    
    /**
     * Gera uma senha aleatória segura.
     * 
     * @param int $length Comprimento da senha
     * 
     * @return string
     */
    public static function generateRandomPassword(int $length = 12): string {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()-_=+';
        $password = '';
        
        $max = strlen($chars) - 1;
        
        for ($i = 0; $i < $length; $i++) {
            $password .= $chars[random_int(0, $max)];
        }
        
        return $password;
    }
} 