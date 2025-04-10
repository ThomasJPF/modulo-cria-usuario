<?php
/**
 * Controlador de ação para listar usuários
 *
 * Este controlador gerencia a listagem de usuários no sistema.
 * 
 * @package     UserManager\Actions
 * @version     1.0
 * @author      Zabbix User Manager Team
 * @copyright   2024
 */

namespace Modules\UserManager\Actions;

use API;
use CControllerResponseData;
use CController as CAction;
use Modules\UserManager\Module;
use Modules\UserManager\Models\User;
use Exception;

/**
 * Classe UserListAction - Controlador para listagem de usuários
 */
class UserListAction extends CAction {
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
            'page' => 'int32',
            'filter_name' => 'string',
            'filter_group' => 'string',
            'sort' => 'string',
            'sortorder' => 'string'
        ];
        
        $ret = $this->validateInput($fields);
        
        if (!$ret) {
            $this->setResponse(new CControllerResponseData([
                'main_block' => _('Dados de filtro inválidos')
            ]));
        }
        
        return $ret;
    }
    
    /**
     * Executa a ação.
     */
    protected function doAction(): void {
        // Preparar dados para a view
        $data = [
            'page' => [
                'title' => _('Gerenciamento de usuários'),
                'docurl' => 'https://github.com/ThomasJPF/modulo-cria-usuario'
            ],
            'filter' => [
                'name' => $this->getInput('filter_name', ''),
                'group' => $this->getInput('filter_group', '')
            ],
            'sort' => $this->getInput('sort', 'username'),
            'sortorder' => $this->getInput('sortorder', ZBX_SORT_UP)
        ];
        
        // Obter página atual
        $page = $this->getInput('page', 1);
        
        // Preparar consulta de usuários
        $options = [
            'output' => ['userid', 'username', 'name', 'surname', 'roleid', 'attempt_failed'],
            'selectUsrgrps' => ['usrgrpid', 'name'],
            'sortfield' => $data['sort'],
            'sortorder' => $data['sortorder'],
            'limit' => CSettingsHelper::get(CSettingsHelper::SEARCH_LIMIT)
        ];
        
        // Aplicar filtros
        if ($data['filter']['name'] !== '') {
            $options['search'] = ['username' => $data['filter']['name']];
        }
        
        if ($data['filter']['group'] !== '') {
            $options['usrgrpids'] = $data['filter']['group'];
        }
        
        // Buscar usuários via API
        $users = API::User()->get($options);
        
        // Preparar dados adicionais para cada usuário
        $userModel = new User();
        foreach ($users as &$user) {
            // Adicionar informações sobre o último login (simulado, pois depende da implementação real)
            // Em um ambiente de produção, devemos buscar esses dados do banco
            $login_stats = $userModel->getLoginStats($user['userid']);
            $user['last_login'] = $login_stats['last_login'] ? date('Y-m-d H:i:s', $login_stats['last_login']) : 'Nunca';
            $user['login_count'] = $login_stats['login_count'];
            
            // Verificar se o usuário está no grupo 'Disabled'
            $user['is_active'] = true;
            foreach ($user['usrgrps'] as $group) {
                if ($group['name'] === 'Disabled' || $group['name'] === 'Disabled accounts') {
                    $user['is_active'] = false;
                    break;
                }
            }
            
            // Buscar o nome do papel (role)
            $roles = API::Role()->get([
                'output' => ['name'],
                'roleids' => [$user['roleid']],
                'limit' => 1
            ]);
            $user['role_name'] = $roles ? $roles[0]['name'] : 'Desconhecido';
            
            // Preparar nome completo
            $user['fullname'] = trim($user['name'] . ' ' . $user['surname']);
            if (empty($user['fullname'])) {
                $user['fullname'] = $user['username'];
            }
        }
        
        // Obter grupos de usuário para o filtro
        $data['user_groups'] = API::UserGroup()->get([
            'output' => ['usrgrpid', 'name'],
            'preservekeys' => true
        ]);
        
        // Adicionar dados da paginação
        $data['paging'] = $this->getPagingLine($users, $page);
        
        // Adicionar dados dos usuários
        $data['users'] = $users;
        
        // Definir a resposta
        $response = new CControllerResponseData($data);
        $response->setTitle(_('Gerenciamento de usuários'));
        $this->setResponse($response);
    }
    
    /**
     * Cria a linha de paginação.
     * 
     * @param array $users Lista de usuários
     * @param int $page Página atual
     * 
     * @return array
     */
    private function getPagingLine(array &$users, int $page): array {
        $total = count($users);
        $limit = CSettingsHelper::get(CSettingsHelper::SEARCH_LIMIT);
        
        if ($total <= $limit) {
            return [
                'count' => $total,
                'total' => $total
            ];
        }
        
        // Calcular o offset e o limite para a página atual
        $offset = ($page - 1) * $limit;
        
        // Ajustar os usuários para mostrar apenas os da página atual
        $users = array_slice($users, $offset, $limit);
        
        return [
            'count' => count($users),
            'total' => $total,
            'offset' => $offset,
            'limit' => $limit,
            'page' => $page
        ];
    }
} 