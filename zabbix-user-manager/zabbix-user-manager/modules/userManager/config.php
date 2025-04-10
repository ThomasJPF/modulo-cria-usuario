<?php
/**
 * Arquivo de configuração para o módulo UserManager
 *
 * Este arquivo contém configurações necessárias para o funcionamento do módulo,
 * incluindo configurações de SMTP para envio de e-mails.
 * 
 * @package     UserManager
 * @version     1.0
 * @author      Zabbix User Manager Team
 * @copyright   2024
 */

// Não permita acesso direto ao arquivo
if (!defined('ZABBIX_MODULE')) {
    exit;
}

return [
    // Configurações de SMTP para envio de e-mails
    'smtp' => [
        'server'    => '', // Endereço do servidor SMTP
        'port'      => 587, // Porta do servidor SMTP
        'username'  => '', // Usuário para autenticação SMTP
        'password'  => '', // Senha para autenticação SMTP
        'from'      => 'zabbix@seudominio.com.br', // E-mail de origem
        'from_name' => 'Zabbix User Manager', // Nome de origem
        'use_tls'   => true // Usar TLS para conexão segura
    ],
    
    // Configurações da senha aleatória gerada
    'password' => [
        'length'        => 12, // Tamanho da senha
        'include_spec'  => true, // Incluir caracteres especiais
        'expiry_days'   => 30 // Dias até expiração da senha inicial
    ],
    
    // Configurações de log
    'log' => [
        'enabled'       => true, // Ativar log de ações
        'keep_days'     => 90 // Dias para manter registros de log
    ],
    
    // Mensagem padrão para o e-mail de boas-vindas
    'email_template' => [
        'subject'       => 'Seu acesso ao Zabbix foi criado',
        'body'          => "Olá {nome},\n\nSeu acesso ao sistema Zabbix foi criado com sucesso.\n\n".
                           "Acesse {url} com as seguintes credenciais:\n\n".
                           "Usuário: {username}\n".
                           "Senha: {password}\n\n".
                           "Por motivos de segurança, recomendamos que você altere sua senha após o primeiro acesso.\n\n".
                           "Atenciosamente,\n".
                           "Equipe de Monitoramento"
    ]
]; 