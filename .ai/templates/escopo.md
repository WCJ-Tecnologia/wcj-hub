# Escopo: <Nome do sistema>

Etapa 4 do `playbooks/aplicacao-nova.md`. Gerado com apoio de IA a partir de `necessidade.md` e `banco.txt`, e **validado inteiro por uma pessoa** (etapa 5). Depois de aprovado, é a referência: a IA não desenvolve o que não está aqui.

Validado por: <nome> em <AAAA-MM-DD>.

## 1. Contexto em uma página

<Para quem, para quê, restrições estruturais que governam tudo (ex.: multiempresa, white label, módulos ligáveis).>

## 2. Fronteira: o que já existe

<Escrito para ninguém reconstruir o pronto. No projeto novo: "o template fornece autenticação, papéis, abilities, shell, CRUD de usuários e empresas, auditoria, headers de segurança, suíte e2e. Nada disso se reescreve.">

## 3. Regras que valem para tudo

- Autorização em duas camadas e escopo por empresa em toda rota (`.ai/core/seguranca.md`).
- Regra de negócio no servidor. Tela só reflete.
- CRUD segue o gabarito (`.ai/stacks/ui.md`).
- <Regras transversais deste produto: fuso, moeda, idioma, módulos.>

## 4. Papéis e acesso

| Papel | Vê | Faz | Não faz |
|---|---|---|---|

## 5. Blocos de trabalho

Um bloco = uma feature na skill `tlc-spec-driven` (`.ai/core/especificacao.md`). Aqui o bloco tem objetivo, dados, telas, regras e permissão. Os requisitos com ID e os critérios de aceite nascem na spec da feature, quando o bloco começa. A IA implementa um bloco por vez, na ordem da seção 8.

### B1. <Nome do bloco>

**Objetivo.** <uma frase>

**Dados.** Tabelas de `banco.txt`: `<x>`, `<y>`. Campos novos, se houver, com justificativa.

**Telas.**
- `/admin/<recurso>`: listagem com busca por <campo>, filtros <a, b>, colunas <...>. Ações: <criar, editar, desativar>.
- Modal de criar/editar com os campos: <lista exata; nada além disto>.

**Regras.** RN-<n>, RN-<m> (de `necessidade.md`), aplicadas em `<Service::metodo>`.

**Permissão.** Ability `<manage_x>`; escopo por `enterprise_id`.

**Especificação.** `.specs/features/b1-<nome>/spec.md`, criada pela skill quando o bloco começa e aprovada por humano. Além do que a skill levantar, entram os critérios de piso de `.ai/core/especificacao.md`, seção 6: permissão, escopo por empresa, regra recusada e auditoria.

Todo bloco tem ainda: estados vazio, erro e carregando; teste de feature por critério; spec e2e de smoke; print da tela no PR.

### B2. <...>

## 6. O que não pode

<Lista explícita do que a IA tende a criar e não deve. Copiada e ampliada de `necessidade.md`.>

## 7. Travas

<O que para o desenvolvimento e exige humano: decisões pendentes, dependências de terceiro, dado que falta.>

## 8. Ordem de execução

1. B1 <...> (depende de nada)
2. B2 <...> (depende de B1)
3. Bloco de segurança (`.ai/core/seguranca.md`, seção 11) antes da primeira publicação
4. <...>

## 9. Fora deste escopo

<O que ficou para depois, para não ser construído por engano.>

## 10. Perfil de uso e testes técnicos

Decide quais testes técnicos são obrigatórios (`.ai/core/testes.md`, seção 11). Números medidos quando houver; estimados, dito assim.

- **Volume:** <tabelas que mais crescem e quantos registros em 12 meses>
- **Usuários:** <média e pico de simultâneos; quem são (internos, franqueados, clientes finais)>
- **Picos:** <janela de pedido, fechamento de mês, campanha, horário de almoço>
- **SLA:** <disponibilidade ou tempo de resposta combinado, ou "nenhum">
- **Motor de cálculo, importação ou relatório pesado:** <quais>

| Teste | Vale? | Por quê |
|---|---|---|
| Arquitetura | sim | todo projeto |
| Banco de verdade | <sim/não> | <banco próprio?> |
| Benchmark | <sim/não> | <critério do perfil que se aplica> |
| Carga | <sim/não> | <critério do perfil que se aplica> |

