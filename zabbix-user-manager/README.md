# Zabbix User Manager

## Descrição
Módulo para o Zabbix 7 que permite gerenciar usuários de forma simplificada. O módulo possui funcionalidades para criar usuários com senhas aleatórias, enviar credenciais por e-mail e monitorar atividades dos usuários.

## Funcionalidades
- Criação de usuários com senhas aleatórias
- Envio automático de credenciais por e-mail
- Monitoramento de último acesso
- Contagem de acessos realizados
- Registro de alterações feitas pelos usuários
- Restrição de acesso apenas para administradores

## Requisitos
- Zabbix 7.0 ou superior
- PHP 8.1 ou superior
- Acesso de administrador ao Zabbix
- Servidor SMTP configurado para envio de e-mails

## Instalação

### 1. Clone o repositório
```bash
git clone https://github.com/ThomasJPF/modulo-cria-usuario.git
```

### 2. Copie os arquivos do módulo
Copie a pasta `modules/userManager` para o diretório de módulos do Zabbix:
```bash
cp -r zabbix-user-manager/modules/userManager /usr/share/zabbix/modules/
```
Ou, se estiver usando Docker:
```bash
docker cp zabbix-user-manager/modules/userManager zabbix-container:/usr/share/zabbix/modules/
```

### 3. Configure o envio de e-mail
Edite o arquivo de configuração do módulo em:
```
/usr/share/zabbix/modules/userManager/config.php
```
Insira os dados do seu servidor SMTP.

### 4. Ative o módulo no Zabbix
1. Acesse `Administração > Geral > Módulos`
2. Encontre "User Manager" na lista
3. Clique em "Ativar"

## Uso
1. Após ativar o módulo, acesse-o através do menu `Administração > User Manager`
2. Para criar um novo usuário, clique em "Criar Usuário"
3. Preencha o e-mail do usuário e defina os grupos de acesso
4. O sistema criará automaticamente o usuário com senha aleatória e enviará as credenciais por e-mail

## Desenvolvimento
O módulo é construído seguindo princípios de código limpo e boas práticas:
- Arquitetura MVC
- Uso de classes e interfaces
- Tratamento de exceções
- Validação de entrada de dados
- Princípios SOLID

### Estrutura de diretórios
```
modules/userManager/
├── Actions/              # Controladores de ações
├── Models/               # Modelos de dados
├── Services/             # Serviços e lógica de negócios
├── views/                # Templates de interface
├── assets/               # Recursos estáticos (JS, CSS)
├── config.php            # Configurações do módulo
├── Module.php            # Classe principal do módulo
└── manifest.json         # Manifesto do módulo
```

## Contribuição
Contribuições são bem-vindas! Por favor, siga estas etapas:
1. Faça fork do repositório
2. Crie sua branch de recurso (`git checkout -b feature/novo-recurso`)
3. Commit suas alterações (`git commit -m 'Adiciona novo recurso'`)
4. Push para a branch (`git push origin feature/novo-recurso`)
5. Abra um Pull Request

## Licença
Este projeto está licenciado sob a Licença MIT - veja o arquivo LICENSE para detalhes.

## Autor
Seu Nome 