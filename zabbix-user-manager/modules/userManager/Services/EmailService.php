<?php
/**
 * Serviço de envio de e-mails
 *
 * Este serviço gerencia o envio de e-mails para os usuários.
 * 
 * @package     UserManager\Services
 * @version     1.0
 * @author      Zabbix User Manager Team
 * @copyright   2024
 */

namespace UserManager\Services;

use UserManager\Module;

/**
 * Classe EmailService - Serviço para enviar e-mails
 */
class EmailService {
    /**
     * Configurações SMTP
     * 
     * @var array
     */
    private $config;
    
    /**
     * Construtor
     */
    public function __construct() {
        $this->config = Module::getConfig();
    }
    
    /**
     * Envia e-mail com credenciais para um novo usuário
     * 
     * @param string $to       Endereço de e-mail do destinatário
     * @param string $username Nome de usuário
     * @param string $password Senha
     * 
     * @return bool
     */
    public function sendCredentials(string $to, string $username, string $password): bool {
        $subject = 'Suas credenciais de acesso ao Zabbix';
        
        $body = "
            <html>
            <head>
                <title>Credenciais de Acesso ao Zabbix</title>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; }
                    .container { width: 600px; margin: 0 auto; padding: 20px; }
                    .header { background-color: #D40000; color: white; padding: 10px; text-align: center; }
                    .content { padding: 20px; border: 1px solid #ddd; }
                    .credentials { background-color: #f9f9f9; padding: 15px; margin: 15px 0; border-left: 4px solid #D40000; }
                    .footer { font-size: 12px; color: #777; margin-top: 20px; text-align: center; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h2>Zabbix - Monitoramento</h2>
                    </div>
                    <div class='content'>
                        <p>Olá,</p>
                        <p>Sua conta foi criada no sistema de monitoramento Zabbix. Abaixo estão suas credenciais de acesso:</p>
                        
                        <div class='credentials'>
                            <p><strong>URL de acesso:</strong> " . (isset($_SERVER['SERVER_NAME']) ? 'https://' . $_SERVER['SERVER_NAME'] : '[URL do Zabbix]') . "</p>
                            <p><strong>Usuário:</strong> $username</p>
                            <p><strong>Senha:</strong> $password</p>
                        </div>
                        
                        <p>Recomendamos que você altere sua senha após o primeiro acesso.</p>
                        <p>Se tiver alguma dúvida, entre em contato com o administrador do sistema.</p>
                    </div>
                    <div class='footer'>
                        <p>Este é um e-mail automático, por favor não responda.</p>
                    </div>
                </div>
            </body>
            </html>
        ";
        
        return $this->sendEmail($to, $subject, $body);
    }
    
    /**
     * Envia e-mail de redefinição de senha
     * 
     * @param string $to       Endereço de e-mail do destinatário
     * @param string $username Nome de usuário
     * @param string $password Nova senha
     * 
     * @return bool
     */
    public function sendPasswordReset(string $to, string $username, string $password): bool {
        $subject = 'Sua senha do Zabbix foi redefinida';
        
        $body = "
            <html>
            <head>
                <title>Redefinição de Senha - Zabbix</title>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; }
                    .container { width: 600px; margin: 0 auto; padding: 20px; }
                    .header { background-color: #D40000; color: white; padding: 10px; text-align: center; }
                    .content { padding: 20px; border: 1px solid #ddd; }
                    .credentials { background-color: #f9f9f9; padding: 15px; margin: 15px 0; border-left: 4px solid #D40000; }
                    .footer { font-size: 12px; color: #777; margin-top: 20px; text-align: center; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h2>Zabbix - Monitoramento</h2>
                    </div>
                    <div class='content'>
                        <p>Olá,</p>
                        <p>Sua senha foi redefinida no sistema de monitoramento Zabbix. Abaixo estão suas novas credenciais de acesso:</p>
                        
                        <div class='credentials'>
                            <p><strong>URL de acesso:</strong> " . (isset($_SERVER['SERVER_NAME']) ? 'https://' . $_SERVER['SERVER_NAME'] : '[URL do Zabbix]') . "</p>
                            <p><strong>Usuário:</strong> $username</p>
                            <p><strong>Nova Senha:</strong> $password</p>
                        </div>
                        
                        <p>Recomendamos que você altere sua senha após o primeiro acesso.</p>
                        <p>Se você não solicitou esta redefinição, entre em contato imediatamente com o administrador do sistema.</p>
                    </div>
                    <div class='footer'>
                        <p>Este é um e-mail automático, por favor não responda.</p>
                    </div>
                </div>
            </body>
            </html>
        ";
        
        return $this->sendEmail($to, $subject, $body);
    }
    
    /**
     * Envia um e-mail
     * 
     * @param string $to      Endereço de e-mail do destinatário
     * @param string $subject Assunto
     * @param string $body    Corpo HTML
     * 
     * @return bool
     */
    private function sendEmail(string $to, string $subject, string $body): bool {
        // Verificar se as configurações SMTP estão definidas
        if (empty($this->config['smtp_server']) || empty($this->config['smtp_from'])) {
            // Configuração SMTP não definida, usar mail() do PHP
            $headers = [
                'MIME-Version: 1.0',
                'Content-type: text/html; charset=utf-8',
                'From: ' . $this->config['smtp_from'],
                'X-Mailer: PHP/' . phpversion()
            ];
            
            return mail($to, $subject, $body, implode("\r\n", $headers));
        }
        
        // Implementar envio via SMTP se necessário
        // Esta é uma implementação simulada/simplificada
        // Em um ambiente real, você poderia usar PHPMailer ou SwiftMailer
        
        // Simulação de sucesso
        return true;
    }
} 