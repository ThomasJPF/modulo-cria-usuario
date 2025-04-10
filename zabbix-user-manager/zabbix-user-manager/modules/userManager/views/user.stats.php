<?php
/**
 * View para estatísticas de usuário
 *
 * @package     UserManager\views
 * @version     1.0
 * @author      Zabbix User Manager Team
 * @copyright   2024
 */

// Preparar título da página
$page_title = _('Estatísticas do usuário') . ': ' . $data['user']['username'];

// URL para voltar à lista de usuários
$url_back = (new CUrl('zabbix.php'))
    ->setArgument('action', 'userManager.view');

// Widget principal
$widget = (new CWidget())
    ->setTitle($page_title)
    ->setDocUrl($data['page']['docurl'])
    ->setControls(
        (new CTag('nav', true, 
            (new CList())
                ->addItem(new CLink(_('Voltar para a lista'), $url_back))
        ))->setAttribute('aria-label', _('Ações'))
    );

// Informações básicas do usuário
$info_form = new CFormList();

// Adicionar campos de informação
$info_form->addRow(_('Nome de usuário'), $data['user']['username']);
$info_form->addRow(_('Nome completo'), $data['user']['fullname']);
$info_form->addRow(_('E-mail'), $data['user']['email'] ?? '');
$info_form->addRow(_('Papel (Role)'), $data['user']['role_name']);

// Preparar lista de grupos para exibição
$groups = [];
foreach ($data['user']['usrgrps'] as $group) {
    if ($group['name'] !== 'Disabled' && $group['name'] !== 'Disabled accounts') {
        $groups[] = $group['name'];
    }
}
$info_form->addRow(_('Grupos'), implode(', ', $groups));

// Status do usuário (ativo/inativo)
$status = $data['user']['is_active']
    ? (new CSpan(_('Ativo')))->addClass('status-green')
    : (new CSpan(_('Inativo')))->addClass('status-red');
$info_form->addRow(_('Status'), $status);

// Adicionar formulário de informações ao widget
$widget->addItem(
    (new CDiv())
        ->addClass('header-title')
        ->addItem(_('Informações do Usuário'))
);
$widget->addItem($info_form);

// Adicionar estatísticas em caixas
$widget->addItem(
    (new CDiv())
        ->addClass('header-title')
        ->addItem(_('Estatísticas de Acesso'))
);

// Caixas de estatísticas
$stats_container = (new CDiv())->addClass('user-stats');

// Último login
$stats_container->addItem(
    (new CDiv())
        ->addClass('stat-box')
        ->addItem(new CTag('h3', true, _('Último acesso')))
        ->addItem(
            (new CDiv())
                ->addClass('stat-value')
                ->addItem($data['login_stats']['last_login'])
        )
);

// Contagem de logins
$stats_container->addItem(
    (new CDiv())
        ->addClass('stat-box')
        ->addItem(new CTag('h3', true, _('Total de acessos')))
        ->addItem(
            (new CDiv())
                ->addClass('stat-value')
                ->addItem($data['login_stats']['login_count'])
        )
);

// Tentativas falhas
$stats_container->addItem(
    (new CDiv())
        ->addClass('stat-box')
        ->addItem(new CTag('h3', true, _('Tentativas falhas')))
        ->addItem(
            (new CDiv())
                ->addClass('stat-value')
                ->addItem($data['login_stats']['failed_attempts'])
        )
);

// Último IP
$stats_container->addItem(
    (new CDiv())
        ->addClass('stat-box')
        ->addItem(new CTag('h3', true, _('Último IP')))
        ->addItem(
            (new CDiv())
                ->addClass('stat-value')
                ->addItem($data['login_stats']['last_ip'] ?: _('Desconhecido'))
        )
);

$widget->addItem($stats_container);

// Adicionar gráfico de atividade (se houver dados)
if (isset($data['activity_data']) && !empty($data['activity_data']['data'])) {
    $widget->addItem(
        (new CDiv())
            ->addClass('header-title')
            ->addItem(_('Gráfico de Atividade'))
    );
    
    $widget->addItem(
        (new CDiv())
            ->addClass('activity-chart-container')
            ->addItem(
                (new CTag('canvas', true))
                    ->setId('activity-chart')
                    ->addClass('activity-chart')
            )
    );
    
    // Adicionar JavaScript para o gráfico
    $widget->addItem(
        (new CScript())->setAttribute('type', 'text/javascript')->addItem('
            jQuery(document).ready(function($) {
                if (typeof UserManager !== "undefined" && typeof Chart !== "undefined") {
                    var activityData = ' . json_encode($data['activity_data']['data']) . ';
                    var activityLabels = ' . json_encode($data['activity_data']['labels']) . ';
                    UserManager.renderActivityChart("activity-chart", activityData, activityLabels);
                }
            });
        ')
    );
}

// Histórico de ações do usuário
$widget->addItem(
    (new CDiv())
        ->addClass('header-title')
        ->addItem(_('Histórico de Ações'))
);

$history_table = (new CTableInfo())
    ->setHeader([
        _('Data/Hora'),
        _('Ação'),
        _('Autor'),
        _('IP'),
        _('Detalhes')
    ]);

// Preencher tabela com os históricos
if (isset($data['history']) && !empty($data['history'])) {
    foreach ($data['history'] as $log) {
        $history_table->addRow([
            $log['time'],
            $log['action_text'],
            $log['author'],
            $log['ip'],
            $log['details']
        ]);
    }
} else {
    $history_table->setNoDataMessage(_('Nenhum registro encontrado'));
}

$widget->addItem($history_table);

// Botões de ação para o usuário
$widget->addItem(
    (new CDiv())
        ->addClass('header-title')
        ->addItem(_('Ações'))
);

$action_buttons = (new CList())
    ->addClass('object-group-edit')
    ->addClass('user-actions');

// Botão para editar usuário
$url_edit = (new CUrl('zabbix.php'))
    ->setArgument('action', 'userManager.edit')
    ->setArgument('userid', $data['user']['userid']);

$action_buttons->addItem(
    (new CSimpleButton(_('Editar usuário')))
        ->addClass('js-edit-user')
        ->setAttribute('data-url', $url_edit->getUrl())
);

// Botão para redefinir senha
$action_buttons->addItem(
    (new CSimpleButton(_('Redefinir senha')))
        ->addClass('js-reset-password reset-password')
        ->setAttribute('data-userid', $data['user']['userid'])
);

// Botão para ativar/desativar usuário
if ($data['user']['is_active']) {
    $action_buttons->addItem(
        (new CSimpleButton(_('Desativar usuário')))
            ->addClass('js-disable-user status-toggle')
            ->setAttribute('data-userid', $data['user']['userid'])
            ->setAttribute('data-active', '1')
    );
} else {
    $action_buttons->addItem(
        (new CSimpleButton(_('Ativar usuário')))
            ->addClass('js-enable-user status-toggle')
            ->setAttribute('data-userid', $data['user']['userid'])
            ->setAttribute('data-active', '0')
    );
}

$widget->addItem($action_buttons);

// Adicionar JavaScript específico para a página
$widget->addItem(
    (new CScript())->setAttribute('type', 'text/javascript')->addItem('
        jQuery(document).ready(function($) {
            // Navegação para edição de usuário
            $(".js-edit-user").on("click", function(e) {
                e.preventDefault();
                window.location.href = $(this).data("url");
            });
        });
    ')
);

return $widget; 