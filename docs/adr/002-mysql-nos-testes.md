# ADR-002: Testes em MySQL 8, nunca SQLite

**Data:** 2026-10-06
**Estado:** aprovada
**Decidido por:** Lucas Scholz (D-GER-22, T6 do `docs/banco.txt`); aplicada no B0

## Contexto

O template `ai-systems` 0.6.0 roda o PHPUnit em SQLite em memória (`phpunit.xml`, `.env.testing`, job `php` do CI) e oferece o banco de verdade como opção: `composer test:banco` sobe um MySQL 8.4 descartável pelo Testcontainers (`tests/Support/BancoDeVerdade.php`), e um job do CI repete a suíte nele. O e2e também usava SQLite (`database/e2e.sqlite`).

O banco do Hub depende do que só o MySQL tem: colunas geradas (`board_stages.single_role_key` e `ai_authorizations.active_assistant_id` STORED, `task_acceptance_criteria.parent_key` VIRTUAL com `IF`/`IFNULL`), `CHECK`, o contador atômico com `LAST_INSERT_ID(expr)` (T4), o unique que ignora acento (`utf8mb4_0900_ai_ci`) e a trava de linha (`lockForUpdate`) que o `deleteIfUnused` e as regras de "uma só aberta" usam. Em SQLite metade das migrações nem roda, e o que roda prova outra coisa.

## Decisão

Toda a suíte roda em MySQL 8, local e no CI, sempre:

- **Local:** o MySQL do `docker-compose.yml` (`mysql:8.4`, colação `utf8mb4_0900_ai_ci`). O SQL de início (`configs.mysql_init`, no próprio compose, sem bind mount) cria `wcj_hub_test` e `wcj_hub_e2e` ao lado do banco de dev, com permissão para o mesmo usuário. `phpunit.xml` aponta `DB_CONNECTION=mysql` e `DB_DATABASE=wcj_hub_test`; host, usuário e senha vêm do `.env.testing` (os valores locais do docker, que não são segredo).
- **CI:** o job `php` tem um serviço `mysql:8.4` com `wcj_hub_test`, e um passo final confere no próprio banco que a suíte migrou ali. O job `e2e` tem o próprio serviço com `wcj_hub_e2e`.
- **e2e:** `npm run e2e:prepare` recusa `.env.e2e` que não seja MySQL ou cujo banco não termine em `_e2e` (para nunca apagar o banco de dev).
- O `config/database.php` passa a ter `mysql` como conexão padrão e `utf8mb4_0900_ai_ci` como colação padrão.
- O Testcontainers continua disponível (`composer test:banco`), para rodar sem o docker-compose de pé; o job `database` do CI saiu, porque o job `php` já roda no MySQL.

## Alternativas descartadas

- **SQLite na suíte e MySQL só num job (como o template):** a maior parte dos testes que importam (coluna gerada, `CHECK`, contador, trava) seria pulada ou daria verde sem provar nada no dia a dia; o dev só descobre no CI.
- **Só Testcontainers, também local:** funciona, mas cada rodada sobe um container (10 a 20 s a mais) e pede o Docker exposto no socket certo; o MySQL do docker-compose já está de pé para o dev.

## Consequências

- Quem desenvolve precisa do MySQL de pé (`docker compose up -d mysql`) para rodar `php artisan test`. O `composer setup` sobe ele.
- Os testes com `UsaBancoDeVerdade` (trava, corrida) deixam de ser pulados localmente.
- A suíte fica alguns segundos mais lenta que em SQLite em memória (migra 1 vez por rodada; cada teste roda numa transação desfeita).
- Os valores do banco local (`wcj_hub`/`wcj_hub_local`) aparecem em `.env.example`, `.env.testing`, `.env.e2e.example` e no CI. Não são segredo: valem só para o container local e para o serviço descartável do CI. Produção usa outro usuário e outra senha, guardados no servidor (`docs/DEPLOY.md`).

## Como verificar

- `php artisan test` com o MySQL parado falha na conexão (não cai em SQLite).
- No CI, o passo "Proof that the suite ran on MySQL" imprime a versão do MySQL e a contagem de migrações.
- `grep -rn sqlite phpunit.xml .env.testing .env.e2e.example .github/workflows/ci.yml` não acha nada.
