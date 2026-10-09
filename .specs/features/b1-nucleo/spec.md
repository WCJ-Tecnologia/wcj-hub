# B1 Núcleo: usuários, clientes, contatos e auditoria — Specification

Feature `b1-nucleo` · trilho completo (D-GER-25) · fonte: [`docs/funcional/f1-nucleo.md`](../../../docs/funcional/f1-nucleo.md) v1.0 (aprovado em 06/10/2026), [`docs/escopo.md`](../../../docs/escopo.md) B1, [`docs/necessidade.md`](../../../docs/necessidade.md) (G1–G5, G7, N1–N9, N11, N12, IA4, IA6), [`docs/banco.txt`](../../../docs/banco.txt) (`roles`, `users`, `clients`, `contacts`, `contact_channels`, `audit_logs`, `ai_authorizations`).

**Approved by**: Lucas Scholz, 2026-10-09

**Aprovada por Lucas Scholz em 06/10/2026** (inclusive as premissas de reenvio de convite e de revogação de IA na tabela do Hub). Escrita no mesmo dia com IA. **Revisada em 07/10/2026** (ponto do Vitinho, aceito por Lucas Scholz): as Policies não leem `projects`; perguntam ao contrato `Platform\Contracts\ClientManagement` (ver premissas e NUC-06/NUC-08). **Revisada em 08/10/2026** com as decisões de Lucas Scholz na review do PR #5 (07/10): excluir o já usado mostra toast de erro, não 403 (D6); textos da confirmação de desativar cliente e o contrato em lote (D7); aviso de e-mail repetido por papel (D2); telefone como texto (D3); gestor cria cliente também em Administração → Clientes (D1); a WCJ não troca de CNPJ nem de país e a autorização vem antes da validação (achados A1 e A2). **Revisada em 09/10/2026** com as decisões de Lucas Scholz no ciclo 1 da `wcj-code-review` (`code-reviews/b1-nucleo-code-review-ciclo-1.md`): a WCJ também não se exclui (Clientes AC 10, CR-1-04); as listas de escolha ficam sem rota JSON, com a Policy conferida dentro do Service (P2, CR-1-05); a retirada do aviso "ao sair do campo" é decisão dele (D12 do design, CR-1-06); a aprovação acima cobre a revisão de 08/10. Implementa: Cauã, a partir da fase Tasks. Escopo da skill: **Large** (quatro telas, Policies, exclusão de verdade, e-mail, auditoria).

## Problem Statement

O Hub nasce com o template: login, usuários (nome, e-mail, papel, carga, "IA pode escrever") e a tela de auditoria. Falta o que Projetos (B2 e B3) precisa para existir: clientes e contatos com as regras da WCJ, o convite por e-mail, a desativação que derruba sessão e conexão de IA, a exclusão só do que nunca foi usado, a anonimização LGPD e a auditoria com canal e resultado, filtrável e aberta pelo botão *Histórico* de cada ficha. Sem isso, o gestor não cria projeto (P1 exige cliente) e o card de suporte não tem "quem pediu" (P52).

## Goals

- [ ] O Administrador convida uma pessoa por e-mail e ela entra trocando a senha provisória, sem ninguém repassar senha à mão (N1).
- [ ] Cliente e contato cadastrados pelo gabarito do template, com CNPJ alfanumérico único por país, contato opcional e aviso de e-mail repetido (N5, N8).
- [ ] Desativar, reativar, excluir só o nunca usado e anonimizar, tudo auditado com quem, quando, antes e depois (G2, G3, N11, N12).
- [ ] A tela de auditoria responde "quem mexeu nisto, quando e por onde" em menos de 30 segundos para qualquer registro, pelo botão *Histórico* (G3).
- [ ] Toda rota do bloco com Policy e teste "quem não pode: 403 e nada muda" (`especificacao.md` §6).

## Out of Scope

| Feature | Reason |
|---|---|
| Botão *Novo cliente* dentro do formulário do projeto | É tela do B2 (F2 §02). B1 entrega o `ClientService::create` e a `ClientPolicy::create` para o gestor, que o B2 chama |
| Saber quais clientes um gestor gere (ler `projects` e `project_members`) | É dado de Projetos; o Platform não lê tabela de domínio (teste A9, ADR-001). B1 entrega só o contrato `Platform\Contracts\ClientManagement` e a implementação vazia; a real é do B2 |
| Conexões de IA (conectar, revogar pela tela, tokens do Passport) | B8 (F8). B1 só marca `revoked_at` nas autorizações ao desativar, trocar papel ou a marca da IA; a revogação de token do Passport entra no B8, quando houver token |
| Contato sem cliente (pessoa de lead) | v2, CRM (D-GER-24). Na fatia 1 o Service exige cliente |
| Papel Comercial com uso | Sem uso na fatia 1 (C14). Só existe na lista de papéis |
| Exportar auditoria ou qualquer lista | Nenhuma regra pede (G5, G3) |
| Editar ou apagar auditoria | Proibido (G3), exceto a limpeza da anonimização (N12) |
| Convite por outro canal, "lembrar de mim", e-mail verificado, telefone do usuário | Fora do `banco.txt` (seção 9) |
| Painel (dashboard) com contagens novas | O template já tem; nada no funcional pede número no Núcleo (F1 §06) |
| Ordenação e paginação diferentes do gabarito do template | `stacks/ui.md` manda |

---

## Assumptions & Open Questions

Todas as dúvidas do funcional foram respondidas por Lucas Scholz em 06/10/2026 (F1 §10). O que sobra é escolha de implementação, registrada aqui.

| Assumption / decision | Chosen default | Rationale | Confirmed? |
|---|---|---|---|
| Convite por e-mail reaproveita o fluxo do template | `TemporaryPasswordService::issue` + `TemporaryPasswordMail`, com assunto e texto do Hub ("Seu acesso ao WCJ Hub"), sem mostrar a senha na tela | O template já testa "creating a user emails a temporary password"; só o texto e o reenvio mudam (F1 §10.1) | y |
| *Reenviar convite* = o `resetPassword` do template renomeado na tela | Gera senha nova, invalida a anterior, manda o mesmo e-mail; auditado como `password_reset` | Mesmo comportamento que o F1 descreve; evita duas rotas para a mesma coisa | y |
| "Já usado" para excluir usuário | `hasHistory()` do template: alguma FK aponta para a linha ou há `audit_logs` de login dela | É a definição do `banco.txt` §0 e do template | y |
| "Já usado" para excluir cliente | Existe `contacts.client_id` ou `projects.client_id` apontando | F1 §04: "só cliente sem projeto e sem contato"; `isReferenced()` já cobre | y |
| "Já usado" para excluir contato | Existe `tasks.requester_contact_id` apontando | F1 §04: "só contato nunca citado em card de suporte" | y |
| Gestor "dos projetos do cliente" antes de o B2 existir | `ContactPolicy` e `ClientPolicy::update` perguntam ao contrato `Platform\Contracts\ClientManagement` (`manages(User $user, Client $client): bool` e `managedClientIds(User $user): array`). O B1 entrega o contrato e a implementação vazia `Platform\Services\NoClientManagement` (ninguém gere cliente algum), ligada no `AppServiceProvider`; os testes das Policies usam um dublê do contrato. O B2 entrega `Projetos\Services\ProjectClientManagement` (`projects.manager_id` ou gestor alocado em `project_members`, P1/P5) e troca a ligação | O Platform não lê `projects` (teste A9, ADR-001): a regra é a do F1 §09, mas o dado é de Projetos. Revisão do Vitinho em 07/10, aceita por Lucas Scholz | y |
| Desativar usuário derruba conexões de IA | Marca `revoked_at` em todas as `ai_authorizations` ativas da pessoa, na mesma transação; os tokens do Passport são revogados pelo B8, que passa a chamar o mesmo ponto | N4, IA6; o B8 não existe ainda, então a revogação é só na tabela do Hub | y |
| Trocar papel ou "IA pode escrever" derruba conexões | Mesmo mecanismo da desativação | IA6, IA4 (06/10) | y |
| Canal na auditoria do B1 | Sempre "Tela" (`ai_authorization_id` nulo); o label "IA: Claude/Codex/ChatGPT" aparece quando a coluna estiver preenchida (B8) | IA11; a tela já nasce pronta para o canal | y |
| "Último acesso" na lista de usuários | Última linha de `audit_logs` com `action = login` da pessoa | `banco.txt` seção 9: o login mora na auditoria | y |
| Aviso de e-mail repetido no contato | Mensagem na tela depois do envio, sem recusar; a resposta do servidor inclui `warning` quando existe outro contato ativo com o mesmo e-mail. Nome e cliente do outro contato só para o Administrador; quem não é Administrador recebe "Já existe um contato com este e-mail" (D2, 07/10) | N8 "avisa, sem recusar"; F1 §09 (o gestor não vê contato de cliente que não é dele) | y |
| CNPJ alfanumérico | 14 caracteres, letras maiúsculas e números, com os dois últimos dígitos verificadores calculados pela regra do CNPJ alfanumérico (Receita, jul/2026); gravado sem máscara; comparado sem caixa pela colation do banco | N5; evita aceitar lixo e evita recusar o CNPJ novo | y |
| País | Lista ISO 3166-1 alfa-2, rótulo em português; padrão `BR` | `clients.country char(2)` | y |
| Cliente WCJ não desativável | `ClientPolicy::deactivate` recusa o cliente semeado (documento `38614817000122`) com 403; `ClientService::update` recusa trocar o país ou o documento dela com "O CNPJ e o país da WCJ não podem ser alterados" (o nome continua editável) | F1 §10.3 (06/10); A2 da review de 07/10: sem isso, editar o CNPJ tirava a proteção | y |
| Anonimizar limpa `audit_logs.changes` | Numa transação: `name`, `email`, `phone` da linha e os mesmos campos dentro de `changes` de toda linha de `audit_logs` cujo `auditable` é o contato; grava `anonymized` sem dados | N12 e `banco.txt` (contacts) | y |
| Página de 50 na auditoria, período padrão 7 dias | Como o F1 §03 e §05 | Decisão do funcional | y |
| Mensagens de recusa em português, na linguagem do usuário | Textos do F1 §04 e §07 | `especificacao.md` §6 | y |
| Autorização antes da validação | Nos formulários (criar e editar usuário, cliente e contato; anonimizar), a Policy é consultada antes da validação do payload: quem não pode recebe 403 e a negação é auditada, mesmo com payload inválido | A1 da review de 07/10: validar antes revelava e-mail existente e a tentativa não virava `denied` | y |
| Telefone do contato | Texto, até 20, gravado como digitado (só sem as pontas em branco), com o "+"; a busca compara só os dígitos dos dois lados | F1 §02; D3 da review de 07/10 (há cliente fora do Brasil) | y |

**Open questions:** none - all resolved or logged above.

---

## User Stories

### P1: Convidar e gerir usuários ⭐ MVP

**User Story**: As a Administrador, I want convidar pessoas por e-mail, definir papel, carga diária e se a IA delas pode escrever, e desativar quem sai, so that só gente certa entra no Hub e perde o acesso na hora em que sai.

**Why P1**: Sem usuário com papel e carga não há gestor, dev, data prevista nem capacidade (N1–N4, P28, P48).

**Acceptance Criteria**:

1. WHEN o Administrador envia o formulário de convite com nome, e-mail único, papel e carga diária válidos THEN o sistema SHALL criar o usuário com `must_change_password = true` e enviar um e-mail ao endereço informado contendo o endereço do Hub, o login e uma senha provisória (N1).
2. The sistema SHALL nunca exibir a senha provisória na tela nem gravá-la em `audit_logs.changes` (N1, G3).
3. WHEN o Administrador clica em *Reenviar convite* THEN o sistema SHALL gerar uma senha provisória nova, invalidar a anterior e enviar o mesmo e-mail (F1 §04).
4. IF o papel enviado é Desenvolvedor e a carga diária está vazia THEN o sistema SHALL recusar com "Carga diária é obrigatória para Desenvolvedor" e não criar nem alterar o usuário (N3).
5. IF a carga diária é menor ou igual a 0 ou maior que 24 THEN o sistema SHALL recusar com erro de validação no campo (N3, `banco.txt`).
6. WHEN o Administrador troca o papel de um usuário THEN o sistema SHALL gravar o novo papel e marcar `revoked_at` em todas as autorizações de IA ativas desse usuário, na mesma transação (N2, IA6).
7. WHEN o Administrador troca a marca "IA pode escrever" de um usuário THEN o sistema SHALL gravar a marca e marcar `revoked_at` em todas as autorizações de IA ativas desse usuário (IA4, IA6).
8. WHEN o Administrador desativa um usuário THEN o sistema SHALL gravar `deactivated_at` e `deactivated_by`, encerrar todas as sessões dele e marcar `revoked_at` nas autorizações de IA ativas, na mesma transação (N4, N11, IA6).
9. WHILE um usuário está desativado the sistema SHALL recusar o login dele com a mesma mensagem genérica de credencial inválida e rejeitar qualquer sessão existente (N4, template).
10. WHEN o Administrador reativa um usuário THEN o sistema SHALL limpar `deactivated_at` e `deactivated_by` sem recriar autorizações de IA (N11, IA6).
11. WHEN o Administrador exclui um usuário que nunca entrou e não é referenciado por nenhuma tabela THEN o sistema SHALL apagar a linha e gravar `deleted` na auditoria (G2).
12. IF o Administrador tenta excluir um usuário com histórico THEN o sistema SHALL mostrar o toast de erro "Usuário já usado: desative em vez de excluir" e não mudar nada (a linha fica) (G2; D6 de 07/10).
13. The sistema SHALL mostrar na lista de usuários as colunas Nome, E-mail, Papel, Carga diária, Último acesso e Situação, com os desativados escondidos por padrão e visíveis em cinza pelo filtro *Mostrar desativados* (F1 §03).
14. WHEN o Administrador define carga diária, papel ou "IA pode escrever" em um usuário que se define a si mesmo THEN o sistema SHALL aplicar as mesmas regras, exceto que o último Administrador ativo não pode ser rebaixado, desativado nem excluído (template).
15. IF um Gestor de projetos ou Desenvolvedor envia qualquer requisição de criar, editar, desativar, reativar, excluir ou reenviar convite de usuário THEN o sistema SHALL responder 403 e manter a quantidade e os dados dos usuários sem mudança (F1 §09).
16. WHEN qualquer das ações acima é concluída THEN o sistema SHALL gravar uma linha em `audit_logs` com `user_id` do Administrador, `action` (`created`, `updated`, `deactivated`, `reactivated`, `deleted`, `password_reset`), `outcome = success`, `auditable` do usuário e `changes` com antes e depois dos campos alterados, sem senha (G3).

**Independent Test**: Convidar um dev pela tela, receber o e-mail no Mailpit local, entrar com a senha provisória, ser levado à troca de senha; desativar o dev e ver a sessão dele cair e as linhas em *Auditoria*.

---

### P1: Cadastrar clientes ⭐ MVP

**User Story**: As a Administrador (e gestor, pelo Service), I want cadastrar as empresas atendidas com CNPJ ou identificador fiscal so that todo projeto nasça ligado a um cliente real e nenhum CNPJ se repita.

**Why P1**: P1 exige cliente para criar projeto; B2 depende deste Service.

**Acceptance Criteria**:

1. WHEN o Administrador envia nome, país `BR` e um CNPJ válido de 14 caracteres alfanuméricos THEN o sistema SHALL criar o cliente com o documento gravado sem máscara, `deactivated_at` nulo e `created` na auditoria (N5).
2. IF o país é `BR` e o documento está vazio ou não passa na validação do CNPJ alfanumérico THEN o sistema SHALL recusar com erro de validação no campo "CNPJ" e não criar o cliente (N5).
3. IF já existe cliente com o mesmo país e documento, ignorando caixa THEN o sistema SHALL recusar com "Já existe o cliente X com este CNPJ", citando o nome do cliente existente, e não criar nem alterar (N5).
4. WHEN o país não é `BR` THEN o sistema SHALL aceitar o documento vazio e rotular o campo como "Identificador fiscal" (N5).
5. WHEN um Gestor de projetos cria um cliente, em Administração → Clientes ou pelo formulário do projeto (B2) THEN o sistema SHALL criar o cliente com as mesmas validações e auditar com o `user_id` do gestor (N6; D1 de 07/10).
6. WHEN um Gestor de projetos edita um cliente que o contrato `ClientManagement` diz que ele gere THEN o sistema SHALL aplicar a alteração com as mesmas validações (N6, P1).
7. IF um Gestor de projetos edita um cliente que o contrato `ClientManagement` não lista para ele (com a implementação vazia do B1, nenhum), ou tenta desativar, reativar ou excluir qualquer cliente THEN o sistema SHALL responder 403 e manter o cliente sem mudança (F1 §09).
8. IF um Desenvolvedor acessa qualquer rota de clientes THEN o sistema SHALL responder 403 (F1 §09).
9. WHEN o Administrador desativa um cliente THEN o sistema SHALL desativar mesmo com projetos abertos, após a confirmação "O cliente X tem N projetos abertos; eles continuam. Desativar?" (N ≥ 2), "O cliente X tem 1 projeto aberto; ele continua. Desativar?" (N = 1) ou "O cliente X sai das listas de escolha. Desativar?" (N = 0), e tirar o cliente das listas de escolha (N11). N vem do contrato `ClientManagement` pelo método em lote `openProjectsCountByClient`, uma chamada por página da lista (D7 de 07/10).
10. IF o Administrador tenta desativar o cliente WCJ (documento `38614817000122`) THEN o sistema SHALL responder 403 com "A WCJ não pode ser desativada" (N7); IF alguém edita a WCJ trocando o país ou o documento THEN o sistema SHALL recusar com "O CNPJ e o país da WCJ não podem ser alterados" e não mudar nada (N7; A2 de 07/10); IF alguém tenta excluir o cliente WCJ, pela tela ou chamando `ClientService::delete` THEN o sistema SHALL recusar com "A WCJ não pode ser excluída" e não mudar nada, mesmo sem contato nem projeto (N7; CR-1-04, decisão de 09/10).
11. WHEN o Administrador exclui um cliente sem contato e sem projeto THEN o sistema SHALL apagar a linha e gravar `deleted` na auditoria (G2).
12. IF o Administrador tenta excluir um cliente com contato ou projeto THEN o sistema SHALL mostrar o toast de erro "Cliente já usado: desative em vez de excluir" e não mudar nada (a linha fica) (G2; D6 de 07/10).
13. The sistema SHALL listar clientes com Nome, País, CNPJ / identificador e Situação, busca por nome e documento, filtros País e Situação (ativos por padrão), e o estado vazio "Nenhum cliente ainda" com o botão *Novo cliente* (F1 §03, §05).
14. The sistema SHALL ter o cliente WCJ semeado (nome "WCJ Tecnologia", país `BR`, documento `38614817000122`) em toda instalação (N7).

**Independent Test**: Criar um cliente com CNPJ alfanumérico, tentar repetir o mesmo CNPJ em minúsculas e ser recusado, desativar e ver sumir da lista de escolha; tentar desativar a WCJ e receber 403.

---

### P1: Contatos do cliente ⭐ MVP

**User Story**: As a Administrador ou gestor, I want registrar as pessoas de cada cliente, opcionalmente, so that a equipe lembre com quem fala e o card de suporte tenha "quem pediu".

**Why P1**: P52 (card de suporte) precisa do contato no B3; N8 e N12 são regras do Núcleo.

**Acceptance Criteria**:

1. WHEN o Administrador cria um contato na aba *Contatos* de um cliente com nome válido THEN o sistema SHALL criar o contato ligado àquele cliente, com e-mail e telefone opcionais (telefone é texto, gravado como digitado, com o "+"; D3 de 07/10), e gravar `created` na auditoria (N8).
2. IF o nome está vazio ou o cliente não está ativo THEN o sistema SHALL recusar com erro de validação e não criar (N8, N11).
3. WHEN o e-mail informado já existe em outro contato ativo THEN o sistema SHALL salvar mesmo assim e devolver o aviso na resposta: "Já existe Fulano (Cliente X) com este e-mail" para o Administrador e "Já existe um contato com este e-mail" para quem não é Administrador (N8, F1 §09; D2 de 07/10).
4. WHEN um Gestor de projetos cria ou edita um contato de um cliente que o contrato `ClientManagement` diz que ele gere THEN o sistema SHALL aplicar com as mesmas regras do Administrador (N8).
5. IF um Gestor de projetos cria ou edita contato de cliente que o contrato `ClientManagement` não lista para ele, ou tenta desativar, excluir ou anonimizar qualquer contato THEN o sistema SHALL responder 403 e manter o contato sem mudança (F1 §09).
6. IF um Desenvolvedor acessa as rotas de contatos THEN o sistema SHALL responder 403 (F1 §09; o nome em "quem pediu" chega pelo card, no B3).
7. WHEN o Administrador desativa um contato THEN o sistema SHALL gravar `deactivated_at` e `deactivated_by` e tirá-lo das listas de escolha, mantendo-o onde já é citado (N11).
8. WHEN o Administrador exclui um contato nunca citado em card de suporte THEN o sistema SHALL apagar a linha e gravar `deleted` (G2).
9. IF o Administrador tenta excluir um contato citado em `tasks.requester_contact_id` THEN o sistema SHALL mostrar o toast de erro "Contato já usado: desative em vez de excluir" e não mudar nada (G2; D6 de 07/10).
10. WHEN o Administrador confirma a anonimização digitando `ANONIMIZAR` THEN o sistema SHALL, numa só transação, trocar o nome por "removido a pedido do titular", apagar e-mail e telefone, apagar os mesmos campos dentro de `changes` de toda linha de `audit_logs` daquele contato e gravar `anonymized` sem dados pessoais (N12).
11. IF a confirmação da anonimização não é exatamente `ANONIMIZAR` THEN o sistema SHALL recusar e não alterar nada (F1 §04).
12. WHILE um contato está anonimizado the sistema SHALL mostrá-lo com situação "Anonimizado", sem botões de editar, reativar ou anonimizar de novo (N12).
13. The sistema SHALL ter a lista geral *Administração → Contatos* com Nome, E-mail, Telefone, Cliente e Situação, busca por nome, e-mail e telefone (pelos dígitos), filtros Cliente e Situação, para o Administrador e, nos clientes que `managedClientIds` devolve, para o gestor (F1 §03).

**Independent Test**: Criar dois contatos com o mesmo e-mail em clientes diferentes e ver o aviso sem recusa; anonimizar um e conferir que a auditoria dele não tem mais o nome.

---

### P1: Auditoria com canal, resultado e histórico por ficha ⭐ MVP

**User Story**: As a Administrador, I want ver quem alterou o quê, quando, por onde e com que resultado, filtrando por registro, so that qualquer dúvida sobre uma mudança se resolva sem perguntar a ninguém.

**Why P1**: G3 é regra fixa e todas as outras histórias gravam aqui; B3 e B8 dependem do canal e do resultado.

**Acceptance Criteria**:

1. The sistema SHALL gravar em `audit_logs` toda escrita do Núcleo com `user_id`, `ai_authorization_id` (nulo = Tela), `action`, `outcome`, `auditable_type` (alias do morph map), `auditable_id`, `changes` `{campo: [antes, depois]}` e `ip_address` (G3).
2. WHEN uma requisição é negada por Policy em qualquer rota do Núcleo THEN o sistema SHALL gravar uma linha com `outcome = denied`, o `user_id` de quem tentou, a ação pretendida e o `auditable_id` quando conhecido (G3), inclusive quando o payload também é inválido: a Policy vem antes da validação e quem não pode recebe 403, nunca o erro de validação (A1 de 07/10). Ação sem rótulo conhecido grava o nome da ability e aparece na tela como "Outra ação".
3. The sistema SHALL listar a auditoria com Data e hora (fuso local), Quem, Canal (Tela ou IA: nome do assistente), Ação, Registro (tipo e identificação legível, ex.: "Usuário Maria", "Cliente WCJ Tecnologia"), Resultado (ok / negado), do mais recente para o mais antigo, em páginas de 50, com as linhas negadas em destaque (F1 §03).
4. WHEN o Administrador clica numa linha THEN o sistema SHALL mostrar o detalhe com cada campo alterado, o valor anterior e o novo (G3).
5. The sistema SHALL oferecer os filtros Período (padrão últimos 7 dias), Quem, Canal, Ação, Tipo de registro, Resultado e a busca pela identificação do registro (F1 §05).
6. WHEN o Administrador clica em *Histórico* na ficha de um usuário, cliente ou contato THEN o sistema SHALL abrir a auditoria já filtrada por `auditable_type` e `auditable_id` daquele registro, sem limite de período (F1 §01).
7. IF um Gestor de projetos ou Desenvolvedor acessa a rota da auditoria THEN o sistema SHALL responder 403 (F1 §09, decisão de 06/10).
8. The sistema SHALL não expor nenhuma rota de editar ou apagar `audit_logs` (G3).
9. WHEN um contato é anonimizado THEN o sistema SHALL ser a única exceção à regra anterior, alterando só os campos pessoais dentro de `changes` das linhas daquele contato (N12).
10. The sistema SHALL mostrar "Nenhum registro com esses filtros" quando a consulta não devolve linha (F1 §03).

**Independent Test**: Editar um cliente, abrir *Histórico* na ficha e ver a linha com antes e depois; tentar a rota como dev e ver 403, e a tentativa aparecer negada na lista do Administrador.

---

### P2: Lista de pessoas ativas para quem não é Administrador

**User Story**: As a Gestor de projetos, I want ver os nomes dos usuários ativos e dos clientes ativos so that eu escolha gestor, pessoas alocadas e cliente ao montar um projeto no B2.

**Why P2**: Não é tela própria do B1 nem rota JSON: são os métodos `UserService::choiceList` e `ClientService::choiceList`, que o B2 e o B3 entregam como props das páginas deles (D6 do design, aceita por Lucas Scholz em 09/10, D13; CR-1-05).

**Acceptance Criteria**:

1. WHEN um Gestor de projetos pede a lista de escolha de usuários THEN o sistema SHALL devolver só `id`, `name` e `role` dos usuários ativos, sem e-mail, carga ou marca da IA (F1 §09).
2. WHEN um Gestor de projetos pede a lista de escolha de clientes THEN o sistema SHALL devolver só `id` e `name` dos clientes ativos (F1 §09).
3. IF um Desenvolvedor, ou qualquer papel sem `viewChoiceList` na Policy, chama `choiceList()` de usuários ou de clientes THEN o sistema SHALL recusar com `AuthorizationException` (403 quando a chamada vem de uma rota) sem devolver nenhum registro, e gravar a negação em `audit_logs` com `outcome = denied` (F1 §09; a Policy é conferida dentro do Service, por isso nenhum chamador do B2 ou do B8 a pula; o B3 entrega ao dev só os alocados do projeto).

**Independent Test**: Como gestor, `choiceList()` devolve só nome e papel; como dev, lança a exceção e a auditoria ganha a linha negada.

---

### P3: Estados de tela e textos

**User Story**: As a usuário do Hub, I want toda lista com estado vazio, erro e carregando e mensagens em português so that a tela nunca pareça quebrada.

**Why P3**: Exigência do padrão para toda tela (`especificacao.md` §6), não regra de negócio.

**Acceptance Criteria**:

1. The sistema SHALL mostrar, em cada lista do bloco, um estado vazio em uma frase com o botão de criar, um estado de erro e um estado de carregando (`stacks/ui.md`).
2. The sistema SHALL apresentar toda mensagem de recusa e aviso em português, com os textos do F1 §04 e §07.

---

## Edge Cases

- IF dois Administradores convidam o mesmo e-mail ao mesmo tempo THEN o sistema SHALL criar um só usuário e responder ao segundo com o erro de e-mail repetido (unique do banco).
- IF o e-mail do convite falha ao ser enviado THEN o sistema SHALL manter o usuário criado, registrar a falha no log do servidor e mostrar "Usuário criado; o e-mail não pôde ser enviado. Use Reenviar convite." (falha parcial).
- IF o CNPJ vem com máscara (`12.ABC.345/01DE-35`) THEN o sistema SHALL aceitar, gravar sem máscara e comparar sem máscara (N5).
- WHEN o país muda de `BR` para outro num cliente com CNPJ THEN o sistema SHALL manter o documento como identificador fiscal e deixar de validá-lo como CNPJ (N5).
- IF o Administrador desativa a si mesmo e é o último Administrador ativo THEN o sistema SHALL recusar com a mensagem do template (template).
- IF a anonimização falha no meio THEN o sistema SHALL desfazer tudo (transação) e manter o contato como estava (N12).
- WHEN a lista de auditoria recebe um filtro de período maior que 1 ano THEN o sistema SHALL aceitar e paginar; não há limite de retenção (G3).
- IF `auditable_id` da negação é desconhecido (registro não encontrado antes da Policy) THEN o sistema SHALL gravar a negação com `auditable_id` nulo (`banco.txt`).

---

## Implicit-Requirement Dimensions (sweep, Large)

| Dimension | Resolution |
|---|---|
| Input validation & bounds | CNPJ alfanumérico 14 com dígito; país ISO; carga 0 < h ≤ 24; nomes até 120/160; telefone texto até 20, gravado como digitado; e-mail formato e 160 (FormRequests) |
| Failure / partial-failure | E-mail de convite falha sem desfazer o usuário (edge case); anonimização e desativação em transação |
| Idempotency / retry / duplicate | Unique de e-mail de usuário e de (país, documento); reenviar convite sempre gera senha nova; aviso de e-mail de contato repetido sem recusar |
| Auth boundaries & rate limits | Policies por papel (F1 §09); limitador de login do template; N/A rate limit nas telas do admin because são 14 usuários internos autenticados |
| Concurrency / ordering | Exclusão só com a linha travada (`TravaDeleteIfUnusedTest` do template, `banco.md` §4); convite simultâneo resolve pelo unique |
| Data lifecycle / expiry | Desativar / reativar / excluir só o nunca usado; anonimizar; auditoria sem expiração (G3); senha provisória expira pela regra do template |
| Observability | Tudo em `audit_logs` com canal e resultado; falha de e-mail no log do Laravel |
| External-dependency failure | SMTP indisponível: edge case acima; não há outra dependência externa no bloco |
| State-transition integrity | Ativo ↔ Desativado (reversível); Anonimizado é terminal (sem editar, reativar ou repetir); excluído só a partir de "nunca usado" |

---

## Requirement Traceability

| Requirement ID | Story | Phase | Status |
|---|---|---|---|
| NUC-01 | P1: Usuários — convite por e-mail (AC 1–3) | Design | Done |
| NUC-02 | P1: Usuários — carga, papel, IA pode escrever e revogação (AC 4–7, 14) | Design | Done |
| NUC-03 | P1: Usuários — desativar, reativar, excluir (AC 8–12) | Design | Done |
| NUC-04 | P1: Usuários — lista, Policy e auditoria (AC 13, 15, 16) | Design | Done |
| NUC-05 | P1: Clientes — criar e validar CNPJ (AC 1–4, 14) | Design | Done |
| NUC-06 | P1: Clientes — gestor cria e edita pelo Service; contrato `ClientManagement` + implementação vazia (AC 5–8) | Design | Done |
| NUC-07 | P1: Clientes — desativar, WCJ, excluir, lista (AC 9–13) | Design | Done |
| NUC-08 | P1: Contatos — criar, aviso de e-mail, gestor (AC 1–6, 13) | Design | Done |
| NUC-09 | P1: Contatos — desativar, excluir (AC 7–9) | Design | Done |
| NUC-10 | P1: Contatos — anonimizar (AC 10–12) | Design | Done |
| NUC-11 | P1: Auditoria — gravação com canal e resultado, negações (AC 1, 2, 8, 9) | Design | Done |
| NUC-12 | P1: Auditoria — tela, filtros, Histórico por ficha (AC 3–7, 10) | Design | Done |
| NUC-13 | P2: Listas de escolha para gestor (AC 1–3) | - | Done |
| NUC-14 | P3: Estados de tela e textos (AC 1–2) | - | Done |

**Coverage:** 14 total, 14 mapped to tasks (T1–T50), 0 unmapped

---

## Success Criteria

- [ ] Convite completo (criar → e-mail → primeiro acesso → troca de senha) em menos de 2 minutos no ambiente local, sem ninguém digitar senha.
- [ ] Zero rotas do bloco sem Policy (`RotasAutorizadasTest` verde) e um teste "403 e nada muda" por ação e papel.
- [ ] Toda escrita do bloco tem linha em `audit_logs` com `changes` preenchido; nenhuma linha contém senha.
- [ ] CNPJ repetido, inclusive em caixa diferente, nunca entra; CNPJ alfanumérico válido sempre entra.
- [ ] Verifier da skill com PASS e sensor de mutação sem sobrevivente nas regras de permissão, exclusão e anonimização.
