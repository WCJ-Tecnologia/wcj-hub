# Modelo de dados: <Nome do sistema>

Etapa 2 do `playbooks/aplicacao-nova.md`. Escrito por pessoa técnica **antes** de qualquer prompt. Regras em `.ai/core/banco.md`: nomes em inglês, plural, snake_case; toda tabela tem `id`, `created_at` e `updated_at`; data e hora em UTC como `datetime` no MySQL, nunca `TIMESTAMP` (acaba em 2038-01-19); Unix time em `bigint`; nada de `deleted_at`.

Ao lado de cada coluna, o motivo: a regra de negócio (RN-xx), a decisão (ADR-nnn, D-xx) ou a regra da casa (`banco.md §n`). Coluna sem motivo não entra.

**Aprovado por:** <nome>, em <AAAA-MM-DD>.

## users

| Coluna | Tipo | Nulo | Motivo / observação |
|---|---|---|---|
| id | bigint pk | não | |
| user_type_id | fk user_types | não | |
| name | varchar(120) | não | |
| email | varchar(160) | não, unique | |
| password | varchar(255) | não | |
| must_change_password | boolean | não, default true | |
| last_login_at | datetime | sim | quem já entrou não se exclui |
| deactivated_at | datetime | sim | desativar, não excluir: banco.md §4 |
| deactivated_by | fk users, on delete restrict | sim | |
| created_at / updated_at | datetime | não | |

Índices: user_type_id, deactivated_at, deactivated_by.

## user_types (domínio fechado)

| id | slug | label |
|---|---|---|
| 1 | admin | Administrador |
| 2 | supervisor | Supervisor |
| 3 | operator | Operador |

## enterprises

| Coluna | Tipo | Nulo | Motivo / observação |
|---|---|---|---|
| id | bigint pk | não | |
| name | varchar(160) | não | |
| document | varchar(18) | não, unique | CNPJ, só dígitos |
| deactivated_at | datetime | sim | ativa = deactivated_at nulo |
| deactivated_by | fk users, on delete restrict | sim | |
| created_at / updated_at | datetime | não | |

Índices: document (unique), deactivated_at, deactivated_by.

## user_has_enterprises (escopo do usuário)

| Coluna | Tipo | Nulo | Motivo / observação |
|---|---|---|---|
| user_id | fk users, on delete cascade | não | |
| enterprise_id | fk enterprises, on delete cascade | não | |

Chave primária: (user_id, enterprise_id).

## <tabela_do_dominio>

| Coluna | Tipo | Nulo | Motivo / observação |
|---|---|---|---|
| id | bigint pk | não | |
| enterprise_id | fk enterprises | não | toda tabela de negócio tem escopo |
| <coluna> | <tipo> | <sim/não> | <regra que a pede> |
| amount | decimal(15,2) | não | dinheiro nunca é float |
| status_id | fk <x>_statuses | não | |
| created_at / updated_at | datetime | não | |

Índices: enterprise_id, status_id, <colunas de filtro>. Unique: <o que não pode duplicar>.

## <x>_statuses (domínio fechado)

| id | slug | label |
|---|---|---|
| 1 | pending | Pendente |
| 2 | approved | Aprovado |
| 3 | rejected | Rejeitado |

## audit_logs

| Coluna | Tipo | Nulo | Motivo / observação |
|---|---|---|---|
| id | bigint pk | não | |
| user_id | fk users | sim | |
| action | varchar(80) | não | |
| auditable_type / auditable_id | varchar / bigint | não | |
| payload | json | sim | |
| ip | varchar(45) | sim | |
| created_at | datetime | não | |

Índices: (auditable_type, auditable_id), user_id, created_at.

## Decisões

- D-01 <por que X é tabela e não coluna>
- D-02 <o que se desativa e o que se exclui; o que conta como "já usado" para cada tabela>
- D-03 Fuso: banco e aplicação em UTC; exibição e filtro por dia em APP_DISPLAY_TIMEZONE (<America/Sao_Paulo>).

## Perguntas para quem aprova

- Q-1 <ponto em que o modelo admite mais de uma resposta, com a proposta>
