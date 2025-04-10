/**
 * Script JavaScript para o módulo User Manager
 * 
 * @package     UserManager
 * @version     1.0
 * @author      Zabbix User Manager Team
 * @copyright   2024
 */

var UserManager = {
    /**
     * Inicializa o módulo.
     */
    init: function() {
        this.setupEventHandlers();
        this.setupTooltips();
    },
    
    /**
     * Configura manipuladores de eventos.
     */
    setupEventHandlers: function() {
        // Manipulador para atualização de status de usuário
        $(document).on('click', '.status-toggle', function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var userid = $button.data('userid');
            var active = $button.data('active') === 1;
            
            // Confirmar ação
            if (!confirm(active 
                ? 'Deseja realmente desativar este usuário?' 
                : 'Deseja realmente ativar este usuário?')) {
                return false;
            }
            
            // Desabilitar botão durante a requisição
            $button.prop('disabled', true);
            
            // Inverter o status atual
            UserManager.updateUserStatus(userid, !active, $button);
        });
        
        // Manipulador para redefinição de senha
        $(document).on('click', '.reset-password', function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var userid = $button.data('userid');
            
            // Confirmar ação
            if (!confirm('Deseja realmente redefinir a senha deste usuário?')) {
                return false;
            }
            
            // Desabilitar botão durante a requisição
            $button.prop('disabled', true).text('Aguarde...');
            
            UserManager.resetPassword(userid, $button);
        });
        
        // Manipulador para exclusão de usuário
        $(document).on('click', '.delete-user', function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var userid = $button.data('userid');
            
            // Confirmar ação (duas vezes para exclusão)
            if (!confirm('Deseja realmente excluir este usuário?')) {
                return false;
            }
            
            if (!confirm('ATENÇÃO: Esta ação não pode ser desfeita. Confirma a exclusão?')) {
                return false;
            }
            
            // Desabilitar botão durante a requisição
            $button.prop('disabled', true).text('Excluindo...');
            
            UserManager.deleteUser(userid, $button);
        });
        
        // Manipulador para formulário de filtro
        $('#filter-form').on('submit', function(e) {
            // Permitir o envio normal do formulário
        });
    },
    
    /**
     * Configura tooltips.
     */
    setupTooltips: function() {
        // Inicializar tooltips, se o Zabbix tiver essa funcionalidade
        if (typeof $.fn.tooltip === 'function') {
            $('[data-toggle="tooltip"]').tooltip();
        }
    },
    
    /**
     * Atualiza o status de um usuário.
     * 
     * @param {number} userid ID do usuário
     * @param {boolean} active Status ativo (true/false)
     * @param {jQuery} button Botão que disparou a ação
     */
    updateUserStatus: function(userid, active, button) {
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
                    alert(response.message || 'Erro ao atualizar status do usuário');
                    
                    // Reverter estado do botão
                    button.prop('disabled', false);
                }
            },
            error: function() {
                // Exibir mensagem de erro
                alert('Erro de comunicação com o servidor');
                
                // Reverter estado do botão
                button.prop('disabled', false);
            }
        });
    },
    
    /**
     * Redefine a senha de um usuário.
     * 
     * @param {number} userid ID do usuário
     * @param {jQuery} button Botão que disparou a ação
     */
    resetPassword: function(userid, button) {
        $.ajax({
            url: 'zabbix.php',
            type: 'POST',
            data: {
                action: 'userManager.resetPassword',
                userid: userid
            },
            dataType: 'json',
            success: function(response) {
                if (response.status) {
                    // Exibir mensagem de sucesso
                    alert('Senha redefinida com sucesso! Um e-mail foi enviado ao usuário.');
                    
                    // Restaurar botão
                    button.prop('disabled', false).text('Redefinir senha');
                } else {
                    // Exibir mensagem de erro
                    alert(response.message || 'Erro ao redefinir senha do usuário');
                    
                    // Reverter estado do botão
                    button.prop('disabled', false).text('Redefinir senha');
                }
            },
            error: function() {
                // Exibir mensagem de erro
                alert('Erro de comunicação com o servidor');
                
                // Reverter estado do botão
                button.prop('disabled', false).text('Redefinir senha');
            }
        });
    },
    
    /**
     * Exclui um usuário.
     * 
     * @param {number} userid ID do usuário
     * @param {jQuery} button Botão que disparou a ação
     */
    deleteUser: function(userid, button) {
        $.ajax({
            url: 'zabbix.php',
            type: 'POST',
            data: {
                action: 'userManager.delete',
                userid: userid
            },
            dataType: 'json',
            success: function(response) {
                if (response.status) {
                    // Redirecionar para a lista de usuários
                    window.location.href = '?action=userManager.view';
                } else {
                    // Exibir mensagem de erro
                    alert(response.message || 'Erro ao excluir usuário');
                    
                    // Reverter estado do botão
                    button.prop('disabled', false).text('Excluir');
                }
            },
            error: function() {
                // Exibir mensagem de erro
                alert('Erro de comunicação com o servidor');
                
                // Reverter estado do botão
                button.prop('disabled', false).text('Excluir');
            }
        });
    },
    
    /**
     * Renderiza um gráfico de atividade (se estiver disponível a biblioteca de gráficos).
     * 
     * @param {string} elementId ID do elemento canvas para renderizar o gráfico
     * @param {Array} data Dados para o gráfico
     * @param {Array} labels Rótulos para o gráfico
     */
    renderActivityChart: function(elementId, data, labels) {
        // Verificar se a biblioteca de gráficos está disponível
        if (typeof Chart === 'undefined') {
            console.warn('Biblioteca de gráficos não disponível');
            return;
        }
        
        var ctx = document.getElementById(elementId).getContext('2d');
        
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Acessos',
                    data: data,
                    backgroundColor: 'rgba(51, 122, 183, 0.2)',
                    borderColor: 'rgba(51, 122, 183, 1)',
                    borderWidth: 1,
                    pointRadius: 3,
                    pointHoverRadius: 5
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                }
            }
        });
    }
};

// Inicializar o módulo quando o documento estiver pronto
jQuery(document).ready(function($) {
    UserManager.init();
}); 