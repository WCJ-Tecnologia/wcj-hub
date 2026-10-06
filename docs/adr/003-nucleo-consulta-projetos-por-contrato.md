# ADR-003: O Núcleo consulta Projetos por um contrato

**Data:** 2026-10-06
**Estado:** aceita por Cauã em 06/10/2026, a confirmar pelo Lucas Scholz no PR do B1
**Decidido por:** Cauã (design do B1, decisão D1)

## Contexto

As Policies de clientes e contatos do B1 precisam saber se um gestor gere algum projeto de um cliente (F1 §09, P1, P5). A desativação do cliente precisa da contagem de projetos abertos para a confirmação (N11). As duas respostas moram em `projects` e `project_members`, tabelas de `App\Domains\Projetos`.

O ADR-001 e a regra A9 do `tests/Arch/ArquiteturaTest.php` proíbem o Platform de usar `App\Domains\` e de citar tabela de domínio. O `.ai/stacks/laravel-inertia.md` §5 só aceita interface com mais de uma implementação real.

## Decisão

O Platform declara a interface `App\Platform\Contracts\ClientProjects` (`managedBy`, `manages`, `openProjectsCount`). Projetos a implementa em `App\Domains\Projetos\Services\ProjectClientProjects`. O `AppServiceProvider`, fora dos dois, liga uma à outra.

A interface tem uma implementação só. É exceção consciente à regra das duas implementações: sem ela, o Núcleo leria tabela de módulo e quebraria o A9.

## Alternativas descartadas

- **Policy do Núcleo lendo `projects` direto:** quebra o A9 e faz o Núcleo depender de módulo, contra a direção das FKs (`banco.txt` 6.2).
- **Mover a regra do gestor para uma Policy em Projetos:** a `ClientPolicy` e a `ContactPolicy` são do Núcleo; dividir a mesma ability entre dois domínios espalha a regra.
- **Registrar a dívida no `divida.php`:** dívida nova pede aprovação e só pode diminuir; a interface resolve sem dívida.

## Consequências

- O Núcleo continua sem conhecer módulo; o A7 e o A9 seguem verdes.
- Toda pergunta nova do Núcleo sobre projetos entra como método desse contrato, implementado em Projetos.
- Nos testes do Núcleo, o contrato resolve pelo container; os projetos dos cenários se criam pelos Models de Projetos.

## Como verificar

- `php artisan test --filter=ClientProjectsTest`: gestor responsável e gestor alocado contam, dev alocado não, projeto de outro cliente não, e só projeto sem `finished_at` entra na contagem.
- `php artisan test --filter=Arch`: A7 e A9 verdes.
