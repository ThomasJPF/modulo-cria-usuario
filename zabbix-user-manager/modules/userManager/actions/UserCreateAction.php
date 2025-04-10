<?php
/**
 * Controlador de ação para criar usuários
 *
 * Este controlador gerencia a criação de novos usuários no sistema Zabbix.
 * 
 * @package     UserManager\Actions
 * @version     1.0
 * @author      Zabbix User Manager Team
 * @copyright   2024
 */

namespace Modules\UserManager\Actions;

use API;
use CControllerResponseData;
use CControllerResponseFatal;
use CController as CAction;
use Modules\UserManager\Module;
use Modules\UserManager\Models\User;
use Exception;

/**
 * Classe UserCreateAction - Controlador para criação de usuários
 */
class UserCreateAction extends CAction {
    /**
     * Inicializa o controlador.
     */
    protected function init(): void {
        // Definir que este controlador requer acesso de administrador
        $this->disableSIDvalidation();
    }
    
    /**
     * Verifica acesso.
     * 
     * @return bool
     */
    protected function checkPermissions(): bool {
        return Module::checkPermissions($this);
    }
    
    /**
     * Verifica campos de entrada.
     * 
     * @return bool
     */
    protected function checkInput(): bool {
        $fields = [
            'email' => 'required|string',
            'usrgrps' => 'required|array',
            'roleid' => 'required|db users.roleid',
            'media_types' => 'array',
            'create' => 'string'
        ];
        
        $ret = $this->validateInput($fields);
        
        if (!$ret) {
            $this->setResponse(
                (new CControllerResponseData(['main_block' => json_encode([
                    'status' => false,
                    'message' => _('Dados de formulário inválidos')
                ])]))->disableView()
            );
        }
        
        return $ret;
    }
    
    /**
     * Executa a ação.
     */
    protected function doAction(): void {
        // Se for uma requisição de carregamento, carregar a página
        if (!$this->hasInput('create')) {
            // Preparar dados para a view
            $data = [
                'page' => [
                    'title' => _('Criar novo usuário'),
                    'docurl' => 'https://github.com/seu-usuario/zabbix-user-manager'
                ]
            ];
            
            // Obter grupos de usuário disponíveis
            $data['user_groups'] = API::UserGroup()->get([
                'output' => ['usrgrpid', 'name'],
                'preservekeys' => true
            ]);
            
            // Obter papéis disponíveis
            $data['roles'] = API::Role()->get([
                'output' => ['roleid', 'name'],
                'preservekeys' => true
            ]);
            
            // Obter tipos de mídia disponíveis
            $data['media_types'] = API::MediaType()->get([
                'output' => ['mediatypeid', 'name', 'type'],
            ]);
            
            $response = new CControllerResponseData($data);
            $response->setTitle(_('Criar novo usuário'));
            $this->setResponse($response);
            
            return;
        }
        
        // Criar usuário
        try {
            // Obter dados do formulário
            $email = $this->getInput('email');
            $usrgrps = $this->getInput('usrgrps', []);
            $roleid = $this->getInput('roleid');
            $media_types = $this->getInput('media_types', []);
            
            // Criar o usuário
            $userModel = new User();
            $result = $userModel->createUser($email, $usrgrps, $roleid, $media_types);
            
            if ($result) {
                $data = [
                    'status' => true,
                    'message' => _('Usuário criado com sucesso!')
                ];
            } 
            else {
                $data = [
                    'status' => false,
                    'message' => _('Erro ao criar usuário')
                ];
            }
        } 
        catch (Exception $e) {
            $data = [
                'status' => false,
                'message' => $e->getMessage()
            ];
        }
        
        // Retornar resposta JSON
        $this->setResponse(
            (new CControllerResponseData(['main_block' => json_encode($data)]))->disableView()
        );
    }
} 