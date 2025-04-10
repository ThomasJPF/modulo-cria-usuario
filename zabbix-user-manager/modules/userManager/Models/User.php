<?php
/**
 * Modelo de usuário para o módulo UserManager
 *
 * Esta classe gerencia operações relacionadas a usuários no sistema Zabbix.
 * 
 * @package     UserManager\Models
 * @version     1.0
 * @author      Zabbix User Manager Team
 * @copyright   2024
 */

namespace UserManager\Models;

use API;
use CApiInputValidator;
use UserManager\Module;
use UserManager\Services\EmailService;
use UserManager\Services\LogService;
use Exception;

/**
 * Classe User - Gerencia operações relacionadas a usuários
 */
class User {
    /** @var array Dados do usuário */
    private $data;
    
    /** @var array Log de histórico do usuário */
    private $history;
    
    /**
     * Serviço de e-mail
     * 
     * @var EmailService
     */
    private $emailService;
    
    /**
     * Serviço de log
     * 
     * @var LogService
     */
    private $logService;
    
    /**
     * Construtor da classe
     * 
     * @param array $data Dados do usuário (opcional)
     */
    public function __construct(array $data = []) {
        $this->data = $data;
        $this->history = [];
        $this->emailService = new EmailService();
        $this->logService = new LogService();
    }
    
    /**
     * Cria um novo usuário no Zabbix
     * 
     * @param string $email E-mail do usuário
     * @param array $groups IDs dos grupos de usuário
     * @param string $roleid ID do papel de usuário
     * @param array $media_types Tipos de mídia para o usuário
     * 
     * @return bool
     * @throws Exception
     */
    public function createUser(string $email, array $groups, string $roleid, array $media_types = []): bool {
        // Validar email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('E-mail inválido');
        }
        
        // Extrair nome de usuário do e-mail
        $username = explode('@', $email)[0];
        
        // Verificar se o usuário já existe
        $existing = API::User()->get([
            'filter' => ['username' => $username],
            'output' => ['userid']
        ]);
        
        if ($existing) {
            // Modificar o nome de usuário para evitar duplicação
            $username .= '_' . date('Ymd');
        }
        
        // Gerar senha aleatória
        $config = Module::getConfig();
        $password = Module::generateRandomPassword($config['password']['length']);
        
        // Preparar grupos de usuário
        $user_groups = [];
        foreach ($groups as $groupid) {
            $user_groups[] = ['usrgrpid' => $groupid];
        }
        
        // Preparar mídias de usuário
        $medias = [];
        foreach ($media_types as $media_type) {
            $medias[] = [
                'mediatypeid' => $media_type,
                'sendto' => [$email],
                'active' => MEDIA_STATUS_ACTIVE,
                'severity' => TRIGGER_SEVERITY_NOT_CLASSIFIED,
                'period' => '1-7,00:00-24:00'
            ];
        }
        
        // Adicionar mídia de email para envio de credenciais
        $email_media_types = API::MediaType()->get([
            'filter' => ['type' => MEDIA_TYPE_EMAIL],
            'output' => ['mediatypeid']
        ]);
        
        if ($email_media_types) {
            $medias[] = [
                'mediatypeid' => $email_media_types[0]['mediatypeid'],
                'sendto' => [$email],
                'active' => MEDIA_STATUS_ACTIVE,
                'severity' => TRIGGER_SEVERITY_NOT_CLASSIFIED,
                'period' => '1-7,00:00-24:00'
            ];
        }
        
        // Preparar dados do usuário
        $userData = [
            'username' => $username,
            'passwd' => $password,
            'roleid' => $roleid,
            'usrgrps' => $user_groups,
            'medias' => $medias,
            'email' => $email,
            'name' => '',
            'surname' => '',
            'lang' => 'pt_BR',
            'theme' => 'default',
            'autologin' => 0,
            'autologout' => '15m',
            'refresh' => '30s',
            'rows_per_page' => 50,
            'url' => ''
        ];
        
        // Criar usuário via API
        $result = API::User()->create($userData);
        
        if (!$result) {
            throw new Exception('Erro ao criar usuário: ' . API::getErrorMessage());
        }
        
        // Armazenar ID do usuário criado
        $userid = $result['userids'][0];
        
        // Registrar log de criação
        $this->logService->logAction('create_user', [
            'userid' => $userid,
            'email' => $email,
            'email_sent' => $this->emailService->sendCredentials($email, $username, $password)
        ]);
        
        return true;
    }
    
    /**
     * Obtém um usuário pelo ID
     * 
     * @param string $userid ID do usuário
     * 
     * @return array|null
     */
    public function getById(string $userid): ?array {
        $users = API::User()->get([
            'userids' => $userid,
            'output' => ['userid', 'username', 'name', 'surname', 'email', 'autologin', 'autologout', 
                         'lang', 'refresh', 'theme', 'rows_per_page', 'url', 'attempt_failed', 
                         'attempt_clock', 'attempt_ip'],
            'selectUsrgrps' => ['usrgrpid', 'name'],
            'selectMedias' => ['mediatypeid', 'sendto', 'active', 'severity', 'period']
        ]);
        
        if ($users) {
            $this->data = $users[0];
            return $this->data;
        }
        
        return null;
    }
    
    /**
     * Obtém estatísticas de login do usuário
     * 
     * @param string $userid ID do usuário
     * 
     * @return array
     */
    public function getLoginStats(string $userid): array {
        // Implementar consulta ao banco de dados ou API para obter histórico de logins
        // Como esta funcionalidade não está disponível nativamente na API do Zabbix,
        // seria necessário consultar diretamente a tabela "users_history" no banco de dados
        
        return [
            'last_login' => time(),
            'login_count' => 0,
            'failed_attempts' => $this->data['attempt_failed'] ?? 0,
            'last_ip' => $this->data['attempt_ip'] ?? ''
        ];
    }
    
    /**
     * Obtém histórico de ações do usuário
     * 
     * @param string $userid ID do usuário
     * @param int $limit Limite de registros
     * 
     * @return array
     */
    public function getActionHistory(string $userid, int $limit = 50): array {
        // Consultar logs de ação registrados pelo nosso módulo
        return $this->logService->getUserActions($userid, $limit);
    }
    
    /**
     * Ativa/desativa um usuário
     * 
     * @param string $userid ID do usuário
     * @param bool $active Status ativo (true/false)
     * 
     * @return bool
     */
    public function setUserStatus(string $userid, bool $active): bool {
        // Obter os grupos atuais do usuário
        $user = $this->getById($userid);
        
        if (!$user) {
            return false;
        }
        
        $user_groups = [];
        foreach ($user['usrgrps'] as $group) {
            $user_groups[] = ['usrgrpid' => $group['usrgrpid']];
        }
        
        // Obter o grupo "Disabled" do Zabbix
        $disabled_group = API::UserGroup()->get([
            'filter' => ['name' => 'Disabled'],
            'output' => ['usrgrpid']
        ]);
        
        if ($disabled_group) {
            $disabled_groupid = $disabled_group[0]['usrgrpid'];
            
            if (!$active) {
                // Adicionar ao grupo Disabled
                $user_groups[] = ['usrgrpid' => $disabled_groupid];
            } else {
                // Remover do grupo Disabled
                foreach ($user_groups as $key => $group) {
                    if ($group['usrgrpid'] === $disabled_groupid) {
                        unset($user_groups[$key]);
                        break;
                    }
                }
                $user_groups = array_values($user_groups);
            }
            
            // Atualizar o usuário
            $result = API::User()->update([
                'userid' => $userid,
                'usrgrps' => $user_groups
            ]);
            
            if ($result) {
                $status = $active ? 'activate' : 'deactivate';
                $this->logService->logAction($status . '_user', [
                    'userid' => $userid,
                    'email' => $user['email'],
                    'email_sent' => $this->emailService->sendCredentials($user['email'], $user['username'], '')
                ]);
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Redefine a senha de um usuário
     * 
     * @param string $userid ID do usuário
     * 
     * @return string Nova senha
     */
    public function resetPassword(string $userid): string {
        $user = $this->getById($userid);
        
        if (!$user) {
            throw new Exception('Usuário não encontrado');
        }
        
        // Gerar nova senha
        $config = Module::getConfig();
        $password = Module::generateRandomPassword($config['password']['length']);
        
        // Atualizar senha via API
        $result = API::User()->update([
            'userid' => $userid,
            'passwd' => $password
        ]);
        
        if (!$result) {
            throw new Exception('Erro ao redefinir senha: ' . API::getErrorMessage());
        }
        
        // Registrar log
        $this->logService->logAction('password_reset', [
            'userid' => $userid,
            'email' => $user['email'],
            'email_sent' => $this->emailService->sendPasswordReset($user['email'], $user['username'], $password)
        ]);
        
        return $password;
    }
    
    /**
     * Obtém o nome de usuário atual.
     * 
     * @return string
     */
    private function getUsername(): string {
        global $USER;
        return $USER->getUsername();
    }
} 