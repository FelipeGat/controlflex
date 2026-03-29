# Documentação Técnica — Sistema ControleFlex (Laravel)

## 1. Estrutura do Projeto

- **MVC padrão Laravel**: separação clara entre Models, Controllers, Policies, Requests e Views.
- **Principais diretórios**:
  - `app/Models`: entidades do domínio (Usuário, Despesa, Receita, Banco, Investimento, Categoria, Familiar, Fornecedor).
  - `app/Http/Controllers`: lógica de controle para cada entidade.
  - `app/Policies`: regras de autorização por entidade.
  - `database/migrations`: scripts de criação das tabelas.
  - `routes/web.php`: definição das rotas web (CRUDs, dashboard, autenticação).
  - `config/`: arquivos de configuração (auth, services, app, etc).

## 2. Entidades Principais

### Usuário (`User`)
- Autenticação padrão Laravel.
- Relacionamento: possui muitos Familiares.

### Familiar
- Campos: nome, foto, salário, limites financeiros.
- Relacionamentos: pertence a um usuário, possui despesas, receitas e bancos.

### Despesa
- Campos: usuário, quem comprou (familiar), onde comprou (fornecedor), categoria, forma de pagamento (banco), valor, datas, recorrência.
- Relacionamentos: pertence a usuário, familiar, fornecedor, categoria, banco.

### Receita
- Campos: usuário, quem recebeu (familiar), categoria, forma de recebimento (banco), valor, datas, recorrência.
- Relacionamentos: pertence a usuário, familiar, categoria, banco.

### Banco
- Campos: usuário, titular (familiar), nome, tipo, saldos, limites.
- Relacionamentos: pertence a usuário e familiar, possui investimentos.

### Investimento
- Campos: usuário, banco, nome do ativo, tipo, data, valor, cotas.
- Relacionamentos: pertence a usuário e banco.

### Categoria
- Campos: usuário, nome, tipo (RECEITA/DESPESA), ícone.
- Relacionamentos: possui receitas e despesas.

### Fornecedor
- Campos: usuário, nome, contato, cnpj, telefone, observações.
- Relacionamentos: possui despesas.

## 3. Funcionalidades Principais

- **Dashboard**: KPIs financeiros do período (receitas, despesas, saldo, comparativos mensais).
- **CRUD completo** para todas as entidades principais.
- **Gestão de recorrências** em receitas e despesas.
- **Relacionamentos ricos** entre entidades (ex: despesas ligadas a familiares, fornecedores, categorias, bancos).
- **Paginação e ordenação** nas listagens.
- **Upload de imagens** para familiares.
- **Validações robustas** nos formulários.

## 4. Fluxos de Dados

- **Autenticação**: Middleware `auth` protege rotas principais.
- **Rotas agrupadas** por entidade, com métodos RESTful (index, store, update, destroy).
- **Controllers**: cada entidade possui controller dedicado, responsável por buscar dados, validar, autorizar e retornar views.
- **Views**: seguem convenção Laravel (ex: `despesas.index`, `familiares.index`).

## 5. Integrações

- **Serviços externos**: configuração para e-mail (Postmark, SES), Slack, etc. via `config/services.php`.
- **Envio de notificações**: suporte a notificações via Slack e e-mail.
- **Armazenamento de arquivos**: upload de imagens para familiares usando storage público.

## 6. Pontos de Customização

- **Policies**: cada entidade sensível possui Policy para garantir que apenas o dono (usuário) pode alterar/excluir seus próprios dados.
- **Validações customizadas**: uso de `Rule::exists` para garantir integridade referencial (ex: titular de banco deve ser familiar do usuário).
- **Soft Deletes**: implementado em Despesa, Receita, Investimento (recuperação de registros excluídos).

## 7. Políticas de Segurança

- **Autorização**: Policies por entidade, checando se o `user_id` do registro pertence ao usuário autenticado.
- **Autenticação**: padrão Laravel, provider Eloquent, proteção por middleware.
- **Validação de dados**: validação rigorosa em todos os formulários.
- **Proteção de arquivos**: uploads restritos a imagens, limites de tamanho e tipo.

## 8. Padrões e Boas Práticas

- **Uso extensivo de Eloquent** para relacionamentos e queries.
- **Controllers enxutos**, delegando regras de negócio para Models e Policies.
- **Migrações bem definidas**, com chaves estrangeiras e integridade referencial.
- **Configuração centralizada** para serviços externos.
- **Paginação e ordenação** padrão Laravel.
- **Soft deletes** para evitar perda acidental de dados.

## 9. Resumo das Tabelas

- **Usuários**: autenticação, dono dos dados.
- **Familiares**: membros da família do usuário.
- **Despesas/Receitas**: movimentações financeiras, com recorrência.
- **Bancos**: contas e cartões.
- **Investimentos**: aplicações financeiras.
- **Categorias**: classificação de receitas/despesas.
- **Fornecedores**: onde as despesas são realizadas.

---


---

## 10. Histórico de Manutenção (mar/2026)

### 29/03/2026
- Correção de conexão do banco de dados no Docker: alinhamento das variáveis de ambiente do app e do serviço db.
- Reset completo do banco de dados e seeders aplicados para ambiente limpo.
- Correção de permissões e criação do diretório `storage/framework/sessions` para evitar erros de sessão.
- Otimização de performance Docker no Windows: uso de volumes nomeados para `storage` e `vendor`, bind mount apenas para código-fonte.
- Limpeza e otimização de cache de configuração e views do Laravel.
- Testes de login, seed e performance realizados.

*Documentação atualizada automaticamente em 29/03/2026.*
