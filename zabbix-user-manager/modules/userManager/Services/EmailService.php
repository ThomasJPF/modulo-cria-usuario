<?php
/**
 * Serviço de e-mail para o módulo UserManager
 *
 * Este serviço gerencia o envio de e-mails, incluindo credenciais para novos usuários.
 * 
 * @package     UserManager\Services
 * @version     1.0
 * @author      Zabbix User Manager Team
 * @copyright   2024
 */

namespace Modules\UserManager\Services;

use Modules\UserManager\Module;
use Exception;

/**
 * Classe EmailService - Gerencia envio de e-mails
 */
class EmailService {
    /** @var array Configurações do SMTP */
    private $config;
    
    /**
     * Construtor da classe
     */
    public function __construct() {
        $this->config = Module::getConfig()['smtp'];
    }
    
    /**
     * Envia credenciais para um novo usuário
     * 
     * @param string $email E-mail do destinatário
     * @param string $username Nome de usuário
     * @param string $password Senha gerada
     * 
     * @return bool
     * @throws Exception
     */
    public function sendUserCredentials(string $email, string $username, string $password): bool {
        // Obter o URL do Zabbix
        $zabbix_url = ZABBIX_URL ?? 'http://seu-zabbix/zabbix/';
        
        // Obter template de e-mail
        $config = Module::getConfig();
        $template = $config['email_template'];
        
        // Preparar o assunto
        $subject = $template['subject'];
        
        // Preparar o corpo do e-mail
        $body = $template['body'];
        $body = str_replace(
            ['{nome}', '{username}', '{password}', '{url}'],
            [$username, $username, $password, $zabbix_url],
            $body
        );
        
        // Enviar e-mail
        return $this->sendEmail($email, $subject, $body);
    }
    
    /**
     * Envia e-mail de redefinição de senha
     * 
     * @param string $email E-mail do destinatário
     * @param string $username Nome de usuário
     * @param string $password Nova senha
     * 
     * @return bool
     * @throws Exception
     */
    public function sendPasswordReset(string $email, string $username, string $password): bool {
        // Obter o URL do Zabbix
        $zabbix_url = ZABBIX_URL ?? 'http://seu-zabbix/zabbix/';
        
        // Preparar o assunto
        $subject = 'Sua senha do Zabbix foi redefinida';
        
        // Preparar o corpo do e-mail
        $body = "Olá {$username},\n\n".
                "Sua senha do Zabbix foi redefinida.\n\n".
                "Acesse {$zabbix_url} com as seguintes credenciais:\n\n".
                "Usuário: {$username}\n".
                "Senha: {$password}\n\n".
                "Por motivos de segurança, recomendamos que você altere sua senha após o primeiro acesso.\n\n".
                "Atenciosamente,\n".
                "Equipe de Monitoramento";
        
        // Enviar e-mail
        return $this->sendEmail($email, $subject, $body);
    }
    
    /**
     * Envia um e-mail
     * 
     * @param string $to Destinatário
     * @param string $subject Assunto
     * @param string $body Corpo da mensagem
     * 
     * @return bool
     * @throws Exception
     */
    private function sendEmail(string $to, string $subject, string $body): bool {
        if (empty($this->config['server'])) {
            throw new Exception('Configuração de SMTP não definida');
        }
        
        // Validar e-mail de destino
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('E-mail de destino inválido');
        }
        
        // Preparar cabeçalhos do e-mail
        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/plain; charset=UTF-8',
            'From: ' . $this->config['from_name'] . ' <' . $this->config['from'] . '>',
            'Reply-To: ' . $this->config['from'],
            'X-Mailer: PHP/' . phpversion()
        ];
        
        // Tentar enviar usando a função mail do PHP (cenário mais simples)
        // Em um ambiente de produção, seria melhor usar PHPMailer ou similar
        if (@mail($to, $subject, $body, implode("\r\n", $headers))) {
            return true;
        }
        
        // Caso o mail() falhe, vamos tentar implementar uma conexão SMTP básica
        // Nota: Em um ambiente real, é recomendado usar uma biblioteca como PHPMailer
        $smtp = fsockopen(
            $this->config['server'],
            $this->config['port'],
            $errno,
            $errstr,
            30
        );
        
        if (!$smtp) {
            throw new Exception("Falha ao conectar ao servidor SMTP: $errstr ($errno)");
        }
        
        // Verificar resposta do servidor
        $response = fgets($smtp, 515);
        if (substr($response, 0, 3) != '220') {
            throw new Exception("Servidor SMTP não respondeu corretamente: $response");
        }
        
        // Enviar EHLO
        fputs($smtp, "EHLO " . $_SERVER['SERVER_NAME'] . "\r\n");
        $response = fgets($smtp, 515);
        if (substr($response, 0, 3) != '250') {
            throw new Exception("Erro no comando EHLO: $response");
        }
        
        // Iniciar TLS se necessário
        if ($this->config['use_tls']) {
            fputs($smtp, "STARTTLS\r\n");
            $response = fgets($smtp, 515);
            if (substr($response, 0, 3) != '220') {
                throw new Exception("Falha ao iniciar TLS: $response");
            }
            
            stream_socket_enable_crypto($smtp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            
            // Re-enviar EHLO após TLS
            fputs($smtp, "EHLO " . $_SERVER['SERVER_NAME'] . "\r\n");
            $response = fgets($smtp, 515);
            if (substr($response, 0, 3) != '250') {
                throw new Exception("Erro no comando EHLO após TLS: $response");
            }
        }
        
        // Autenticar se necessário
        if (!empty($this->config['username']) && !empty($this->config['password'])) {
            fputs($smtp, "AUTH LOGIN\r\n");
            $response = fgets($smtp, 515);
            if (substr($response, 0, 3) != '334') {
                throw new Exception("Erro na autenticação: $response");
            }
            
            fputs($smtp, base64_encode($this->config['username']) . "\r\n");
            $response = fgets($smtp, 515);
            if (substr($response, 0, 3) != '334') {
                throw new Exception("Erro no usuário SMTP: $response");
            }
            
            fputs($smtp, base64_encode($this->config['password']) . "\r\n");
            $response = fgets($smtp, 515);
            if (substr($response, 0, 3) != '235') {
                throw new Exception("Erro na senha SMTP: $response");
            }
        }
        
        // Definir remetente
        fputs($smtp, "MAIL FROM: <" . $this->config['from'] . ">\r\n");
        $response = fgets($smtp, 515);
        if (substr($response, 0, 3) != '250') {
            throw new Exception("Erro ao definir remetente: $response");
        }
        
        // Definir destinatário
        fputs($smtp, "RCPT TO: <$to>\r\n");
        $response = fgets($smtp, 515);
        if (substr($response, 0, 3) != '250') {
            throw new Exception("Erro ao definir destinatário: $response");
        }
        
        // Iniciar dados
        fputs($smtp, "DATA\r\n");
        $response = fgets($smtp, 515);
        if (substr($response, 0, 3) != '354') {
            throw new Exception("Erro ao iniciar envio de dados: $response");
        }
        
        // Construir mensagem completa com cabeçalhos
        $message = "To: $to\r\n";
        $message .= "Subject: $subject\r\n";
        $message .= implode("\r\n", $headers) . "\r\n\r\n";
        $message .= $body . "\r\n.\r\n";
        
        // Enviar mensagem
        fputs($smtp, $message);
        $response = fgets($smtp, 515);
        if (substr($response, 0, 3) != '250') {
            throw new Exception("Erro ao enviar mensagem: $response");
        }
        
        // Encerrar conexão
        fputs($smtp, "QUIT\r\n");
        fclose($smtp);
        
        return true;
    }
} 