<?php
/**
 * Controlador de ação para estatísticas de usuário
 *
 * Este controlador gerencia a exibição de estatísticas e histórico de usuários.
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
 * Classe UserStatsAction - Controlador para estatísticas de usuário
 */
class UserStatsAction extends CAction {
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
            'userid' => 'required|db users.userid'
        ];
        
        $ret = $this->validateInput($fields);
        
        if (!$ret) {
            $response = new CControllerResponseFatal();
            $response->setTitle(_('Erro'));
            $response->setMessage(_('Usuário não encontrado'));
            $this->setResponse($response);
        }
        
        return $ret;
    }
    
    /**
     * Executa a ação.
     */
    protected function doAction(): void {
        // Obter ID do usuário
        $userid = $this->getInput('userid');
        
        try {
            // Obter dados do usuário
            $userModel = new User();
            $user = $userModel->getById($userid);
            
            if (!$user) {
                throw new Exception(_('Usuário não encontrado'));
            }
            
            // Preparar dados para a view
            $data = [
                'page' => [
                    'title' => _('Estatísticas do usuário'),
                    'docurl' => 'https://github.com/ThomasJPF/modulo-cria-usuario'
                ],
                'user' => $user
            ];
            
            // Verificar se o usuário está no grupo 'Disabled'
            $data['user']['is_active'] = true;
            foreach ($user['usrgrps'] as $group) {
                if ($group['name'] === 'Disabled' || $group['name'] === 'Disabled accounts') {
                    $data['user']['is_active'] = false;
                    break;
                }
            }
            
            // Buscar o nome do papel (role)
            $roles = API::Role()->get([
                'output' => ['name'],
                'roleids' => [$user['roleid']],
                'limit' => 1
            ]);
            $data['user']['role_name'] = $roles ? $roles[0]['name'] : 'Desconhecido';
            
            // Preparar nome completo
            $data['user']['fullname'] = trim($user['name'] . ' ' . $user['surname']);
            if (empty($data['user']['fullname'])) {
                $data['user']['fullname'] = $user['username'];
            }
            
            // Obter estatísticas de login
            $data['login_stats'] = $userModel->getLoginStats($userid);
            
            // Formatar o timestamp de último login
            if ($data['login_stats']['last_login']) {
                $data['login_stats']['last_login'] = date('Y-m-d H:i:s', $data['login_stats']['last_login']);
            } else {
                $data['login_stats']['last_login'] = _('Nunca');
            }
            
            // Obter histórico de ações do usuário
            $data['history'] = $userModel->getActionHistory($userid);
            
            // Preparar dados para o gráfico de atividade nos últimos 30 dias
            $data['activity_data'] = $this->prepareActivityChartData($data['history']);
            
            // Definir a resposta
            $response = new CControllerResponseData($data);
            $response->setTitle(_('Estatísticas do usuário') . ': ' . $user['username']);
            $this->setResponse($response);
        } 
        catch (Exception $e) {
            $response = new CControllerResponseFatal();
            $response->setTitle(_('Erro'));
            $response->setMessage($e->getMessage());
            $this->setResponse($response);
        }
    }
    
    /**
     * Prepara dados para o gráfico de atividade.
     * 
     * @param array $history Histórico de ações do usuário
     * 
     * @return array
     */
    private function prepareActivityChartData(array $history): array {
        // Se não há histórico, retornar array vazio
        if (empty($history)) {
            return [
                'data' => [],
                'labels' => []
            ];
        }
        
        // Preparar dados para os últimos 30 dias
        $end_date = time();
        $start_date = $end_date - (30 * 24 * 3600); // 30 dias atrás
        
        $dates = [];
        $activity_counts = [];
        
        // Inicializar contadores para cada dia
        for ($i = 0; $i < 30; $i++) {
            $day = $end_date - ($i * 24 * 3600);
            $date_label = date('d/m/Y', $day);
            $dates[$date_label] = 0;
        }
        
        // Contar atividades por dia
        foreach ($history as $log) {
            $day = date('d/m/Y', $log['timestamp']);
            
            // Incluir apenas os dias dentro do intervalo
            if (isset($dates[$day])) {
                $dates[$day]++;
            }
        }
        
        // Preparar arrays para o gráfico
        $chart_labels = array_keys($dates);
        $chart_data = array_values($dates);
        
        // Inverter a ordem para mostrar da data mais antiga para a mais recente
        $chart_labels = array_reverse($chart_labels);
        $chart_data = array_reverse($chart_data);
        
        return [
            'data' => $chart_data,
            'labels' => $chart_labels
        ];
    }
} 