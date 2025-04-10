# Módulo de Gerenciamento de Usuários para Zabbix 7

Este módulo para Zabbix 7 permite a criação e gerenciamento eficiente de usuários, com funcionalidades de envio automático de credenciais por e-mail e monitoramento detalhado de atividades dos usuários.

## Funcionalidades

- Criação de usuários com senha aleatória segura
- Envio automático de credenciais por e-mail
- Monitoramento de acessos dos usuários (último acesso, número de logins)
- Rastreamento de atividades dos usuários (logins, logouts, alterações)
- Widget para criação rápida de usuários no dashboard
- Widget para monitoramento de usuários no dashboard

## Requisitos

- Zabbix 7.0 ou superior
- PHP 7.4 ou superior
- Servidor de e-mail configurado no Zabbix

## Instalação

1. Faça o download do módulo e descompacte-o:
```bash
cd /tmp
wget https://github.com/seu-usuario/zabbix-user-manager/archive/refs/heads/main.zip
unzip main.zip
```

2. Crie o diretório de módulos no Zabbix (se não existir):
```bash
mkdir -p /usr/share/zabbix/modules
```

3. Copie o módulo para o diretório de módulos do Zabbix:
```bash
cp -r /tmp/zabbix-user-manager/modules/userManager /usr/share/zabbix/modules/
```

4. Defina as permissões corretas:
```bash
chown -R zabbix:zabbix /usr/share/zabbix/modules/userManager
```

5. Crie a tabela de atividades de usuários no banco de dados do Zabbix:
```sql
CREATE TABLE user_activity (
    id BIGSERIAL PRIMARY KEY,
    userid BIGINT NOT NULL,
    action VARCHAR(50) NOT NULL,
    description TEXT,
    ip_address VARCHAR(64),
    timestamp INTEGER NOT NULL,
    FOREIGN KEY (userid) REFERENCES users (userid) ON DELETE CASCADE
);
CREATE INDEX user_activity_userid_idx ON user_activity (userid);
CREATE INDEX user_activity_timestamp_idx ON user_activity (timestamp);
```

6. Acesse a interface web do Zabbix como administrador e navegue para:
   - Administração > Geral > Módulos

7. Clique em "Verificar atualizações" para atualizar a lista de módulos disponíveis

8. Localize o módulo "Gerenciador de Usuários" e clique em "Ativar"

## Configuração

1. Certifique-se de que o servidor de e-mail está corretamente configurado no Zabbix:
   - Administração > Mídia de Alerta > E-mail

2. Verifique se o usuário de serviço do Zabbix tem permissão para enviar e-mails.

## Uso

### Criação de Usuários

1. No menu principal, acesse:
   - Administração > Módulos > Gerenciador de Usuários > Criar Usuário

2. Preencha os campos:
   - E-mail (obrigatório)
   - Nome (opcional)
   - Sobrenome (opcional)
   - Grupos de usuários (obrigatório)

3. Clique em "Criar" para criar o usuário e enviar as credenciais por e-mail

### Monitoramento de Usuários

1. No menu principal, acesse:
   - Administração > Módulos > Gerenciador de Usuários > Lista de Usuários

2. Visualize informações como:
   - Nome de usuário
   - Nome completo
   - E-mail
   - Último acesso
   - Total de acessos
   - Total de alterações

3. Clique em "Atividades" para ver detalhes das atividades de um usuário específico

### Widgets

O módulo fornece dois widgets que podem ser adicionados ao dashboard:

1. **Widget de Criação de Usuário**: Permite criar rapidamente novos usuários diretamente do dashboard

2. **Widget de Monitoramento de Usuários**: Exibe uma lista dos usuários com suas estatísticas de acesso

Para adicionar os widgets ao dashboard:
   - Clique em "Editar dashboard"
   - Clique em "Adicionar"
   - Selecione "Criar Usuário" ou "Monitoramento de Usuários" na lista de widgets
   - Configure as opções do widget
   - Clique em "Adicionar"

## Solução de Problemas

### E-mails não estão sendo enviados

1. Verifique a configuração do servidor de e-mail no Zabbix
2. Verifique se o usuário do Zabbix tem permissão para enviar e-mails
3. Confira os logs de erro do PHP em busca de mensagens relacionadas ao envio de e-mail

### Permissões de Acesso

1. O módulo está disponível apenas para usuários administradores
2. Verifique se o usuário tem a permissão "Gerenciamento de usuários" habilitada no perfil

### Registro de Atividades Não Funciona

1. Verifique se a tabela `user_activity` foi criada corretamente no banco de dados
2. Confira se o usuário do banco de dados tem permissões de escrita na tabela

## Contribuição

Contribuições são bem-vindas! Para contribuir com este módulo:

1. Faça um fork do repositório
2. Crie uma branch para sua feature (`git checkout -b feature/nova-funcionalidade`)
3. Commit suas mudanças (`git commit -m 'Adiciona nova funcionalidade'`)
4. Push para a branch (`git push origin feature/nova-funcionalidade`)
5. Abra um Pull Request

## Licença

Este módulo é distribuído sob a licença GNU General Public License v2. 