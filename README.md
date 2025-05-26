# FLOWTRACK - Sistema de Gestão de Tarefas e Usuários

## Visão Geral

O FLOWTRACK é um sistema web desenvolvido para auxiliar na gestão de tarefas e usuários dentro de uma organização. Ele oferece funcionalidades para cadastrar, listar, detalhar, alterar o status de tarefas, além de gerenciar os usuários do sistema, incluindo a adição, eliminação e alteração de seus cadastros. O sistema também possui uma funcionalidade para geração de relatórios, permitindo o acompanhamento do progresso das tarefas.

## Funcionalidades Principais

* **Gestão de Tarefas:**
    * Listagem de tarefas cadastradas com informações como data, tema, descrição, prioridade e status.
    * Visualização detalhada de uma tarefa específica, incluindo todas as suas informações.
    * Alteração do status de uma tarefa (a fazer, em andamento, concluída).
    * Adição de novas tarefas ao sistema.
    * Filtragem de tarefas (funcionalidade planejada).
* **Gestão de Usuários:**
    * Listagem de usuários cadastrados com informações como ID, nome, função e tipo de acesso.
    * Adição de novos usuários ao sistema.
    * Eliminação de usuários existentes.
    * Alteração de informações de usuários.
* **Relatórios:**
    * Geração de relatórios com base em diferentes critérios como data e status.
    * Visualização da porcentagem de tarefas concluídas.
* **Autenticação:**
    * Sistema de login para acesso seguro ao sistema.

## Estrutura do Projeto

O projeto segue uma estrutura com separação entre o frontend (interface do usuário) e o backend (lógica do servidor e acesso aos dados).

FLOWTRACK/
├── backend/
│   ├── config/
│   │   └── database.php
│   ├── atualizar-status-tarefa.php
│   ├── listar-tarefas.php
│   ├── listar-usuarios.php
│   ├── processar_cadastro_usuario.php
│   ├── processar_login.php
│   └── processar_adm-tarefa.php
├── Frontend/
│   ├── adicionar-tarefas/
│   │   ├── adicionar-tarefa.css
│   │   └── adicionar-tarefa.php
│   ├── dashboard-gestao/
│   │   ├── dashboard-styles.css
│   │   └── dashboard.php
│   ├── dashboard-relatorios/
│   │   ├── relatorio.css
│   │   └── relatorio.php
│   ├── detalhe-tarefas/
│   │   ├── tarefa.css
│   │   └── tarefa.php
│   ├── gerir-usuarios/
│   │   ├── cadastrar-usuarios.css
│   │   ├── cadastrar-usuarios.php
│   │   ├── usuarios.css
│   │   └── usuarios.php
│   ├── lista-tarefa/
│   │   ├── detalhe.css
│   │   └── detalhe.php
│   ├── pagina-login/
│   │   ├── index.php
│   │   └── styles.css
└── README.md


## Tecnologias Utilizadas

* **Frontend:** HTML, CSS, JavaScript
* **Backend:** PHP
* **Banco de Dados:** MySQL
* **Bibliotecas/Frameworks:**
    * Font Awesome (para ícones)

## Pré-requisitos

Para executar o FLOWTRACK, você precisará ter o seguinte instalado:

* Um servidor web (como Apache ou Nginx) com suporte a PHP.
* PHP 7.0 ou superior.
* MySQL instalado e configurado.
* As configurações de conexão com o banco de dados devem estar corretamente definidas no arquivo `backend/config/database.php`.

## Instalação

1.  Clone o repositório do projeto para o seu servidor web.
2.  Configure um virtual host (se estiver usando Apache ou Nginx) apontando para o diretório `Frontend/`.
3.  Importe o schema do banco de dados (arquivo `.sql` fornecido separadamente, se houver) para o seu servidor MySQL.
4.  Edite o arquivo `backend/config/database.php` com as suas credenciais de acesso ao banco de dados MySQL.
5.  Certifique-se de que as permissões de escrita nas pastas necessárias (se houver upload de arquivos, etc.) estejam configuradas corretamente.

## Configuração

* **Banco de Dados:** Ajuste as configurações de host, nome do banco de dados, usuário e senha no arquivo `backend/config/database.php`.
* **Paths:** Verifique se os paths nos arquivos PHP e JavaScript estão corretos para a sua estrutura de diretórios no servidor.

## Execução

1.  Abra o seu navegador web.
2.  Acesse o URL configurado para o seu projeto (ex: `http://localhost/FLOWTRACK/Frontend/pagina-login.php`).
3.  Faça login com as credenciais de usuário configuradas no banco de dados.

## Contribuição

Contribuições para o projeto são bem-vindas. Se você tiver sugestões de melhorias ou encontrar bugs, por favor, abra uma issue neste repositório. Pull requests com melhorias e correções são encorajados.

## Licença

[Adicionar informações sobre a licença, se aplicável]

## Notas

Este README fornece uma visão geral da estrutura e do funcionamento do projeto FLOWTRACK com base na análise dos arquivos fornecidos. Detalhes específicos sobre a lógica de cada funcionalidade podem ser encontrados nos arquivos de código correspondentes.