# ADR-001: Código por domínio desde o primeiro commit

**Data:** 2026-10-06
**Estado:** aprovada
**Decidido por:** Lucas Scholz (D-GER-15, na `docs/necessidade.md`); aplicada no B0

## Contexto

O template `ai-systems` organiza o código por camada técnica (`app/Http/Controllers`, `app/Models`, `app/Services`…). O `.ai/stacks/laravel-inertia.md` §2 manda passar para "por domínio" a partir de uns cinco módulos de negócio, como refatoração estrutural com ADR. O Hub nasce com dois (Núcleo e Projetos) e já tem os próximos marcados: CRM na v2 e Chamados depois (D-GER-12, D-GER-24). Fazer a mudança depois, com Cauã e Vitinho trabalhando em paralelo, custaria um PR de movimentação que conflita com todos os abertos.

## Decisão

Organizar por domínio desde o B0: `app/Platform/` (o Núcleo: usuários, papéis, clientes, contatos, auditoria, conexões de IA) e `app/Domains/Projetos/`, cada um com `Controllers`, `Models`, `Policies`, `Requests`, `Services` e `Mcp`. CRM e Chamados entram como `app/Domains/Crm/` e `app/Domains/Chamados/`.

O que é infraestrutura, e não domínio, fica onde o template põe: `app/Http/Controllers/Controller.php` (base), `app/Http/Middleware`, `app/Http/Responses`, `app/Console`, `app/Mail`, `app/Providers`, `app/Rules`, `app/Support`.

## Regras que vêm junto

- FK só numa direção: `Domains/* → Platform` (`banco.md` 6.2). O Núcleo não conhece módulo.
- Um domínio não usa Model de outro domínio (regra A7 do `tests/Arch/ArquiteturaTest.php`) nem lê ou grava tabela de outro por SQL (regra A9, no mesmo arquivo). O Platform não usa nada de `App\Domains\`. Precisa do dado de outro domínio: chama o Service dele.
- Um domínio pode ler os Models do Platform (o dev da tarefa é um `User`), na direção das FKs. Escrita em tabela do Platform passa pelo Service do Platform.
- O Platform precisa de um dado de módulo (ex.: "quais clientes este gestor gere", que mora em `projects`): declara uma interface em `app/Platform/Contracts/`, com uma implementação vazia no próprio Platform, e o módulo entrega a implementação real em `app/Domains/<X>/Services/`, ligada no `AppServiceProvider` (que fica fora do que A9 varre). O Platform continua sem conhecer `App\Domains\`. Primeiro uso: `ClientManagement` (B1 e B2, revisão de 07/10).
- Lista geral que só um módulo usa (tipos de tarefa, motivos de retrabalho) é Model e Service desse módulo, mesmo que a tela fique em *Administração*.
- Policy fora de `app/Policies` é registrada com `Gate::policy()` no `AppServiceProvider`.
- Model fora de `App\Models` declara `newFactory()`: o Laravel acha a factory pelo nome do namespace e erraria.
- O morph map (`Relation::enforceMorphMap`, no `AppServiceProvider`) dá o alias de cada Model em `audit_logs.auditable_type` e `notifications.notifiable_type` (`banco.md`, audit_logs: "alias do morph map"). Model novo entra no mapa no mesmo commit.

## Alternativas descartadas

- **Manter por camada até o 5º módulo:** a mudança viria no meio da fatia 1 ou da v2, com os dois devs em paralelo; o PR de movimentação conflita com todo PR aberto.
- **Um pacote Composer por domínio:** separa mais, mas pede versionamento e autoload próprios sem ganho para 14 usuários e uma equipe de dois.

## Consequências

- O agente acha o arquivo pelo assunto (`stacks/laravel-inertia.md` §2: ~95% contra ~75% por camada).
- Os testes de arquitetura do template olhavam `app/Services` e `app/Http/Controllers`. No Hub, A4 (Service sem HTTP) e A6 (tamanho) olham também `app/Platform/<camada>` e `app/Domains/*/<camada>`, e as chaves de `tests/Arch/divida.php` mudaram de caminho junto (a dívida é a mesma do template, só mudou de pasta).
- O `DateTimeConventionsTest` procura `SoftDeletes` em `app/Platform/Models` e `app/Domains/*/Models`.
- Cada domínio nasce com `Controllers`, `Policies`, `Requests`, `Services` e `Mcp` vazios (`.gitkeep`) até o bloco que os usa.

## Diferenças entre a seção 9 do `banco.md` e o template 0.6.0

A seção 9 foi escrita sobre o template 0.4.0. Conferida contra o 0.6.0 (`ai-systems` `cddfdab`) no B0:

| Item | Seção 9 diz | O 0.6.0 traz | O que o B0 fez |
|---|---|---|---|
| Tabela de papéis | `user_types` (id, slug, label) vira `roles` | `user_types` com `name` (não `label`), `slug` unique, `datetimes()`; enum `UserTypeEnum` admin/supervisor/financial | `roles` com `slug` e `label` (como o `banco.md`), 4 papéis (`RoleEnum`). O unique em `slug` não está no `banco.md` e não entrou (ver PR) |
| `users.email`, `users.password` | "de null para not null" | `email` e `password` nulos, `email` unique | not null os dois; `email` varchar(160) unique, `name` varchar(120) |
| `users.must_change_password` | "fica (já no template 0.4.0)" | vem numa **migração separada** (`add_must_change_password_to_users_table`) com **default false** | entra na migração de `users`, com **default true**, como o `banco.md` |
| Colunas que saem de `users` | document, phone, email_verified_at, last_login_at, remember_token | todas existem | saíram; `remember_token` saiu também do model (`$rememberTokenName = ''`) e o login não oferece mais "lembrar" |
| Login em `hasHistory()` | passa a ler o login de `audit_logs` | lia `last_login_at` | lê `audit_logs` (`action = login` ou qualquer autoria) |
| `user_has_enterprises`, `enterprises` | saem | vêm na mesma migração (`create_enterprises_tables`), com `EnterpriseController`, `EnterprisePolicy`, `Scoping`, tela, e2e e testes | saíram inteiros; `TravaDeleteIfUnusedTest` passou a usar `Client` |
| `audit_logs.action` | varchar(40) | varchar(60) | varchar(40) |
| `audit_logs.auditable_type` | varchar(120), alias do morph map | varchar(160), nome da classe | varchar(120), alias do morph map |
| `audit_logs.created_at` | datetime not null | datetime **null** | not null |
| Índices de `audit_logs` | (auditable_type, auditable_id, created_at), user_id, ai_authorization_id | (auditable_type, auditable_id), (action, created_at), user_id | os do `banco.md`; o (action, created_at) saiu (ver PR) |
| `audit_logs.before_data`/`after_data` | viram `changes` {"campo": [antes, depois]} | `before_data` e `after_data` | `changes`; o `AuditService` monta o par antes/depois só dos campos que mudaram |
| `audit_logs.user_agent` | sai | existe (512) | saiu |
| `password_reset_tokens`, `sessions`, `cache`, `jobs`… | "ficam como estão" | `password_reset_tokens` e `sessions` vêm **dentro da migração de users** | ficaram como estão, numa migração própria do framework, para a de `users` ser só `users` |
| Testes | SQLite → MySQL 8 | SQLite em memória no `phpunit.xml` e no CI; já traz `tests/Support/BancoDeVerdade.php` (Testcontainers, `DB_TESTE=container`) e um job de CI com ele | MySQL 8 em tudo (ADR-002); o Testcontainers continua disponível em `composer test:banco` |
| Perfil (`/admin/profile`) | — | edita `phone` | o campo telefone saiu da tela (a coluna saiu) |
| Painel | — | conta empresas no escopo do usuário (`Scoping`) | conta só usuários; o resto do painel é de outro bloco |

## Como verificar

- `php artisan test --testsuite=Arquitetura`: A7 e A9 verdes.
- `find app -maxdepth 1` mostra `Domains` e `Platform`, e nenhum `Models`, `Services`, `Policies` ou `Enums` na raiz de `app/`.
