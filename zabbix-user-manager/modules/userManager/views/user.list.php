<?php
/**
 * View para listar usuários
 *
 * @package     UserManager\views
 * @version     1.0
 * @author      Zabbix User Manager Team
 * @copyright   2024
 */

// Título da página
$page_title = _('Gerenciamento de usuários');

// URL para criar um novo usuário
$url_create = (new CUrl('zabbix.php'))
    ->setArgument('action', 'userManager.create');

// Widget principal
$widget = (new CWidget())
    ->setTitle($page_title)
    ->setDocUrl($data['page']['docurl'])
    ->setControls(
        (new CTag('nav', true, 
            (new CList())
                ->addItem(new CLink(_('Criar usuário'), $url_create))
        ))->setAttribute('aria-label', _('Ações'))
    );

// Criar formulário de filtro
$filter_form = (new CFilter())
    ->setProfile('web.user.filter')
    ->setActiveTab(CProfile::get('web.user.filter.active', 1));

// Adicionar campos de filtro
$filter_form->addFilterTab(_('Filtro'), [
    (new CFormList())
        ->addRow(_('Nome'),
            (new CTextBox('filter_name', $data['filter']['name']))
                ->setWidth(ZBX_TEXTAREA_FILTER_STANDARD_WIDTH)
        )
        ->addRow(_('Grupo'),
            (new CMultiSelect([
                'name' => 'filter_group',
                'object_name' => 'usrgrp',
                'data' => CArrayHelper::renameObjectsKeys($data['user_groups'], ['usrgrpid' => 'id']),
                'value' => $data['filter']['group'] ? [$data['filter']['group']] : [],
                'popup' => [
                    'parameters' => [
                        'srctbl' => 'usrgrp',
                        'srcfld1' => 'usrgrpid',
                        'srcfld2' => 'name',
                        'dstfrm' => 'zbx_filter',
                        'dstfld1' => 'filter_group_'
                    ]
                ]
            ]))->setWidth(ZBX_TEXTAREA_FILTER_STANDARD_WIDTH)
        )
]);

// Adicionar formulário de filtro ao widget
$widget->addItem($filter_form);

// Adicionar cabeçalho de página
$url = (new CUrl('zabbix.php'))
    ->setArgument('action', 'userManager.view');

// Ícones de ordenação das colunas
$sort_icons = [
    'username' => makeOrderIcon('username', $data['sort'], $data['sortorder']),
    'name' => makeOrderIcon('name', $data['sort'], $data['sortorder']),
    'role' => makeOrderIcon('roleid', $data['sort'], $data['sortorder']),
    'last_login' => makeOrderIcon('attempt_clock', $data['sort'], $data['sortorder'])
];

// Criar tabela de usuários
$user_table = (new CTableInfo())
    ->setHeader([
        $sort_icons['username'],
        $sort_icons['name'],
        _('E-mail'),
        $sort_icons['role'],
        _('Grupos'),
        $sort_icons['last_login'],
        _('Acessos'),
        _('Status'),
        _('Ações')
    ]);

// Preencher tabela com os usuários
foreach ($data['users'] as $user) {
    // URL para visualizar detalhes do usuário
    $url_details = (new CUrl('zabbix.php'))
        ->setArgument('action', 'userManager.edit')
        ->setArgument('userid', $user['userid']);
    
    // URL para visualizar estatísticas do usuário
    $url_stats = (new CUrl('zabbix.php'))
        ->setArgument('action', 'userManager.stats')
        ->setArgument('userid', $user['userid']);
    
    // Preparar lista de grupos para exibição
    $groups = [];
    foreach ($user['usrgrps'] as $group) {
        if ($group['name'] !== 'Disabled' && $group['name'] !== 'Disabled accounts') {
            $groups[] = $group['name'];
        }
    }
    
    // Status do usuário (ativo/inativo)
    $status = $user['is_active']
        ? (new CSpan(_('Ativo')))->addClass('status-green')
        : (new CSpan(_('Inativo')))->addClass('status-red');
    
    // Ações disponíveis
    $actions = [
        new CLink(_('Editar'), $url_details),
        CViewHelper::showHint(' | '),
        new CLink(_('Estatísticas'), $url_stats)
    ];
    
    // Adicionar linha à tabela
    $user_table->addRow([
        new CLink($user['username'], $url_details),
        $user['fullname'],
        $user['email'] ?? '',
        $user['role_name'],
        implode(', ', $groups),
        $user['last_login'],
        $user['login_count'],
        $status,
        $actions
    ]);
}

// Adicionar paginação, se necessário
if (isset($data['paging']) && $data['paging']['total'] > $data['paging']['count']) {
    $table_info = _('Exibindo %1$s de %2$s encontrados');
    $table_info = sprintf($table_info, $data['paging']['count'], $data['paging']['total']);
    
    $user_table->setTableInfo($table_info);
    
    $widget->addItem(getPagingLine(
        $data['paging'],
        $data['sort'],
        $data['sortorder']
    ));
}

// Adicionar tabela ao widget
$widget->addItem($user_table);

// Adicionar JavaScript para funcionalidades interativas
?>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Função para atualizar status de usuário
    function updateUserStatus(userid, active, button) {
        $.ajax({
            url: 'zabbix.php',
            type: 'POST',
            data: {
                action: 'userManager.updateStatus',
                userid: userid,
                active: active ? 1 : 0
            },
            dataType: 'json',
            success: function(response) {
                if (response.status) {
                    // Recarregar a página para mostrar as alterações
                    window.location.reload();
                } else {
                    // Exibir mensagem de erro
                    alert(response.message || '<?= _('Erro ao atualizar status do usuário') ?>');
                    
                    // Reverter estado do botão
                    button.prop('disabled', false);
                }
            },
            error: function() {
                // Exibir mensagem de erro
                alert('<?= _('Erro de comunicação com o servidor') ?>');
                
                // Reverter estado do botão
                button.prop('disabled', false);
            }
        });
    }
    
    // Implementar alternância de status diretamente na lista
    $('.status-toggle').on('click', function(e) {
        e.preventDefault();
        
        var $button = $(this);
        var userid = $button.data('userid');
        var active = $button.data('active') === 1;
        
        // Confirmar ação
        if (!confirm(active 
            ? '<?= _('Deseja realmente desativar este usuário?') ?>' 
            : '<?= _('Deseja realmente ativar este usuário?') ?>')) {
            return false;
        }
        
        // Desabilitar botão durante a requisição
        $button.prop('disabled', true);
        
        // Inverter o status atual
        updateUserStatus(userid, !active, $button);
    });
});
</script>

<?php
return $widget; 