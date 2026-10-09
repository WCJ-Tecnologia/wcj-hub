# ADR-004: Ficha do cliente em página própria

**Data:** 2026-10-06
**Estado:** aceita por Cauã em 06/10/2026, a confirmar pelo Lucas Scholz no PR do B1
**Decidido por:** Cauã (design do B1, decisão D7)

## Contexto

O gabarito de CRUD (`.ai/stacks/ui.md` §4) cria e edita em modal, na própria lista. Página separada só para registro com muitos blocos, e então com ADR. O cliente tem mais de um bloco: os dados e os contatos (F1 §01: "Na ficha do cliente, aba *Contatos*"; F1 §03: os contatos do cliente ficam "dentro da ficha do cliente"). O escopo do B1 lista a rota da ficha com os contatos (`docs/escopo.md`, B1). Na fase 4 do B1, *Novo contato*, editar e as ações do contato entram nessa ficha.

O template não tem componente de abas, e o projeto não acrescenta dependência de UI sem ADR (`.ai/stacks/laravel-inertia.md` §1).

## Decisão

A ficha do cliente é a página `resources/js/pages/admin/clients/show.tsx`, na rota `GET /admin/clients/{client}` (`ClientController::show`), aberta pelo nome do cliente na lista. Ela mostra dois blocos em seções da mesma página, cada uma com o seu título: **Dados** (país, CNPJ ou identificador fiscal, situação) e **Contatos** (Nome, E-mail, Telefone, Situação). O "aba Contatos" do F1 é a seção Contatos. O botão *Histórico* abre a auditoria filtrada no cliente (`/admin/audit-logs?type=client&id=<id>`).

Criar e editar o cliente continuam no modal da lista. Na fase de clientes, os contatos da ficha são só leitura.

## Alternativas descartadas

- **Contatos num modal da linha do cliente:** o modal já é o formulário do cliente; um segundo modal com lista e ações de contato dentro dele empilha diálogos e não cabe no celular.
- **Abas de verdade (Radix Tabs):** dependência nova para dois blocos. Volta à mesa quando a ficha tiver um terceiro bloco (por exemplo, os projetos do cliente).
- **Só a lista geral de contatos, sem ficha:** o F1 pede criar o contato a partir do cliente, com o cliente fixo no formulário; sem ficha, o gestor perderia esse caminho.

## Consequências

- O cliente tem duas telas: a lista (com o modal de criar e editar) e a ficha. Quem procura um contato sem saber a empresa usa a lista geral de contatos (F1 §03, fase 4).
- Os contatos na ficha aparecem para o Administrador e para o gestor dos projetos do cliente (F1 §09). Até a `ContactPolicy` existir, o `ClientController::show` decide pela mesma regra de editar o cliente; a `ContactPolicy` assume essa decisão na fase 4.
- A ficha não tem editar nem desativar: ficam na lista, onde já estão.

## Como verificar

- `php artisan test --filter=ClientControllerTest`: a ficha traz os dados e os contatos do cliente em ordem de nome, e esconde os contatos de quem não os vê.
- `npx playwright test e2e/smoke.spec.ts`: a ficha, aberta pela lista, renderiza sem erro e passa no axe nos dois temas.
