<?php
/**
 * View para criar usuários
 *
 * @package     UserManager\views
 * @version     1.0
 * @author      Zabbix User Manager Team
 * @copyright   2024
 */

// Interface de criação de usuário
$page_title = _('Criar novo usuário');

// Criar formulário
$form = (new CForm('post'))
    ->addItem((new CVar('create', '1'))->removeId())
    ->setId('create-user-form')
    ->setName('create-user-form')
    ->addStyle('display: block;');

// Tabela do formulário
$form_list = new CFormList('user-form-list');

// Campo de e-mail
$form_list->addRow(
    (new CLabel(_('E-mail'), 'email'))->setAsteriskMark(),
    (new CTextBox('email'))
        ->setWidth(ZBX_TEXTAREA_STANDARD_WIDTH)
        ->setAriaRequired()
        ->setAttribute('placeholder', 'usuario@exemplo.com.br')
        ->setAttribute('autofocus', 'autofocus')
);

// Selecionar grupos de usuário
$user_groups_multiselect = (new CMultiSelect([
    'name' => 'usrgrps[]',
    'object_name' => 'usrgrp',
    'data' => [],
    'popup' => [
        'parameters' => [
            'srctbl' => 'usrgrp',
            'srcfld1' => 'usrgrpid',
            'srcfld2' => 'name',
            'dstfrm' => $form->getName(),
            'dstfld1' => 'usrgrps_'
        ]
    ]
]))->setWidth(ZBX_TEXTAREA_STANDARD_WIDTH);

// Preencher grupos de usuário disponíveis
if (isset($data['user_groups']) && !empty($data['user_groups'])) {
    $user_groups_data = [];
    foreach ($data['user_groups'] as $group_id => $group) {
        if ($group['name'] !== 'Disabled' && $group['name'] !== 'Disabled accounts') {
            $user_groups_data[] = [
                'id' => $group_id,
                'name' => $group['name']
            ];
        }
    }
    $user_groups_multiselect->setData($user_groups_data);
}

$form_list->addRow(
    (new CLabel(_('Grupos de usuário'), 'usrgrps__ms'))->setAsteriskMark(),
    $user_groups_multiselect
);

// Selecionar papel (role)
$role_select = (new CSelect('roleid'))
    ->setFocusableElementId('roleid')
    ->setWidth(ZBX_TEXTAREA_STANDARD_WIDTH);

if (isset($data['roles']) && !empty($data['roles'])) {
    foreach ($data['roles'] as $role_id => $role) {
        $role_select->addOption(new CSelectOption($role_id, $role['name']));
    }
}

$form_list->addRow(
    (new CLabel(_('Papel (Role)'), 'roleid'))->setAsteriskMark(),
    $role_select
);

// Selecionar tipos de mídia adicionais
if (isset($data['media_types']) && !empty($data['media_types'])) {
    $media_types_container = (new CDiv())
        ->addClass('media-types-container')
        ->addStyle('max-height: 200px; overflow-y: auto;');
    
    foreach ($data['media_types'] as $media_type) {
        // Pular o tipo de mídia de e-mail pois já será adicionado automaticamente
        if ($media_type['type'] == MEDIA_TYPE_EMAIL) {
            continue;
        }
        
        $media_types_container->addItem(
            (new CCheckBox("media_types[{$media_type['mediatypeid']}]"))
                ->setLabel($media_type['name'])
                ->setChecked(false)
        );
    }
    
    $form_list->addRow(
        new CLabel(_('Tipos de mídia adicionais'), null),
        $media_types_container
    );
}

// Informações sobre senha
$password_info = (new CDiv())
    ->addClass('msg-info')
    ->addItem(new CSpan(_('Uma senha aleatória segura será gerada e enviada por e-mail para o usuário.')))
    ->addStyle('margin-top: 5px;');

$form_list->addRow(null, $password_info);

// Botões de ação
$form_list->addRow(null, [
    (new CSimpleButton(_('Criar')))
        ->addClass('js-create')
        ->addClass('btn-primary'),
    (new CSimpleButton(_('Cancelar')))
        ->addClass('js-cancel')
        ->setAttribute('data-url', '?action=userManager.view')
]);

// Adicionar lista ao formulário
$form->addItem($form_list);

// Adicionar JavaScript
?>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Enviar formulário via AJAX
    $('.js-create').on('click', function(e) {
        e.preventDefault();
        
        var $form = $('#create-user-form');
        
        // Validar e-mail
        var email = $form.find('input[name="email"]').val();
        if (!email || !email.match(/^[^@]+@[^@]+\.[^@]+$/)) {
            alert('<?= _('Por favor, informe um e-mail válido') ?>');
            return false;
        }
        
        // Validar grupos
        var groups = $form.find('input[name="usrgrps[]"]').val();
        if (!groups) {
            alert('<?= _('Por favor, selecione pelo menos um grupo de usuário') ?>');
            return false;
        }
        
        // Validar papel
        var roleid = $form.find('select[name="roleid"]').val();
        if (!roleid) {
            alert('<?= _('Por favor, selecione um papel (role)') ?>');
            return false;
        }
        
        // Desabilitar botão para evitar múltiplos envios
        $(this).prop('disabled', true).text('<?= _('Criando...') ?>');
        
        // Enviar dados do formulário
        $.ajax({
            url: '?action=userManager.create',
            type: 'POST',
            data: $form.serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.status) {
                    // Exibir mensagem de sucesso
                    var messageBox = $('<div>').addClass('msg-good')
                        .append($('<span>').text(response.message));
                    
                    // Limpar formulário
                    $form[0].reset();
                    
                    // Adicionar mensagem ao topo do formulário
                    $form.prepend(messageBox);
                    
                    // Redirecionar após 2 segundos
                    setTimeout(function() {
                        window.location.href = '?action=userManager.view';
                    }, 2000);
                } else {
                    // Exibir mensagem de erro
                    var messageBox = $('<div>').addClass('msg-bad')
                        .append($('<span>').text(response.message));
                    
                    // Adicionar mensagem ao topo do formulário
                    $form.prepend(messageBox);
                    
                    // Reativar botão
                    $('.js-create').prop('disabled', false).text('<?= _('Criar') ?>');
                }
            },
            error: function() {
                // Exibir mensagem de erro
                var messageBox = $('<div>').addClass('msg-bad')
                    .append($('<span>').text('<?= _('Error de comunicação com o servidor') ?>'));
                
                // Adicionar mensagem ao topo do formulário
                $form.prepend(messageBox);
                
                // Reativar botão
                $('.js-create').prop('disabled', false).text('<?= _('Criar') ?>');
            }
        });
    });
    
    // Botão cancelar
    $('.js-cancel').on('click', function(e) {
        e.preventDefault();
        window.location.href = $(this).data('url');
    });
});
</script>

<?php
return $form; 