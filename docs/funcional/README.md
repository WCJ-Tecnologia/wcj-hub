# Documentos funcionais: WCJ Hub, fatia 1

Um documento por bloco, em linguagem de quem usa, descrevendo tela, campos, listagem, ações, filtros, indicadores, avisos e permissões. É o que o dono do módulo aprova antes de a equipe desenvolver (D-GER-28). Criados em 06/10/2026, depois da aprovação do `banco.txt` por Lucas Alencar (D-GER-23) e da decisão de fatiar a v1 (D-GER-24).

**Escopo desta pasta:** a **fatia 1** = Núcleo mínimo + Projetos + conexões de IA com 6 ferramentas. CRM e GPT "WCJ Hub" são a v2 e terão os próprios funcionais quando a hora chegar.

## Como este documento se encaixa no padrão WCJ

| Documento | Para quem | O que traz |
|---|---|---|
| `docs/necessidade.md` | WCJ e IA | As regras (G, N, P, IA), a premissa e as decisões. Fonte única das regras. |
| `docs/banco.txt` | Pessoa técnica | Todas as tabelas e colunas, aprovadas (etapa 2). |
| **`docs/funcional/Fn-*.md`** | Rafael (Projetos), Lucas Scholz (Núcleo e IA), Cauã e Vitinho | Como cada bloco funciona na tela, campo a campo. Aprovado antes do código. |
| `docs/escopo.md` | IA e devs | Os mesmos blocos em forma técnica: tabelas, rotas, Policies, ordem (etapa 4). Aponta para o funcional de cada bloco. |
| `docs/plano-de-desenvolvimento.md` | Cauã, Vitinho e Lucas Scholz | Quem faz o quê, em que ordem, com que trilho, e como o PR anda até o merge. |
| `.specs/features/<bloco>/` | IA e Verifier | A spec em EARS de cada bloco, gerada pela skill `tlc-spec-driven` a partir do funcional aprovado. |

Um funcional aprovado vira uma feature na skill. Nada que não esteja no funcional entra na spec; nada que não esteja no `banco.txt` entra no funcional.

## Fluxo de aprovação

| Passo | Quem | O quê |
|---|---|---|
| 1 | WCJ (Lucas Scholz, com IA) | Escreve o funcional a partir da `necessidade.md` e do `banco.txt`. |
| 2 | Rafael Garcia (Projetos) / Lucas Scholz (Núcleo e IA) | Lê, aprova ou devolve com correções. Os "pontos a validar" (seção 10) são respondidos aqui. |
| 3 | Cauã ou Vitinho, com IA | Spec na skill (aprovada por Lucas Scholz), código, testes, PR. |
| 4 | Lucas Scholz | Revisa o PR contra o funcional e a spec; QA cruzado entre os dois devs. |
| 5 | Rafael e a equipe | Usam no ambiente de teste (bugbash) antes de trocar o Trello. |

## Seções fixas de todo funcional

Mesmo modelo dos funcionais do Gestor PTA e do Projeto Oficinas. Seção que não se aplica ao bloco fica escrita como "Não se aplica", com o motivo em uma linha, para ninguém achar que faltou.

| Nº | Seção | O que traz |
|---|---|---|
| 01 | Fluxo funcional | O caminho que a pessoa percorre, passo a passo, do menu ao fim da tarefa. |
| 02 | Telas e campos | Cada tela e cada campo: nome, tipo, obrigatório ou não, valor padrão, de onde vem e a regra que o governa. |
| 03 | Listagem | Colunas, ordem padrão e o que aparece em cada linha ou card. |
| 04 | Ações | Botões, arrastar e soltar, e o que cada ação faz, com confirmações e avisos. Quando a mesma ação existe pela IA, a ferramenta aparece aqui. |
| 05 | Filtros e busca | Filtros rápidos, busca e filtro avançado. |
| 06 | Indicadores | Os números na tela e o que cada um representa. |
| 07 | Avisos e documentos gerados | Avisos no sistema, e-mails e qualquer documento que saia da tela: conteúdo, quando e para quem. |
| 08 | Exportação | O que exporta e em que formato. Na fatia 1 nenhuma regra pede exportação; a seção existe para dizer isso. |
| 09 | Permissionamento | Quem vê e quem faz, por papel: Administrador, Gestor de projetos, Desenvolvedor. O Comercial não entra em Projetos (C14). A IA do usuário tem exatamente o que ele tem (G4). |
| 10 | Pontos a validar | Perguntas que surgiram ao escrever e que o dono do módulo responde na aprovação. Não alteram o escopo. |

Cada frase que vem de uma regra termina com o código dela entre parênteses, por exemplo "(P45)". Cada funcional começa com um cabeçalho fixo: objetivo, regras que alimentam, tabelas do `banco.txt`, depende de, quem aprova.

## Os blocos

| Bloco | Título | Regras que alimentam | Tabelas | Depende de | Aprova |
|---|---|---|---|---|---|
| [F1](f1-nucleo.md) | Núcleo: usuários, clientes, contatos e auditoria | G1–G5, G7, N1–N9, N11, N12 | `roles`, `users`, `clients`, `contacts`, `contact_channels`, `audit_logs` | template (login, troca de senha) | Lucas Scholz |
| [F2](f2-projeto-e-configuracao.md) | Projeto e configuração do quadro | G1, G7, P1–P3, P5, P7, P8, P10–P12, P15, P18, P40, P46 | `projects`, `project_members`, `stage_roles`, `board_stages`, `project_areas`, `task_types`, `rework_reasons` | F1 | Rafael Garcia |
| [F3](f3-quadro-e-card.md) | Quadro e card | P13–P26, P39, P40, P52, P56, premissa | `tasks`, `task_stage_movements`, `task_priorities`, `task_acceptance_criteria`, `task_pull_requests`, `task_comments`, `task_attachments`, `validation_types` | F2 | Rafael Garcia |
| [F4](f4-teste-e-qualidade.md) | Teste cruzado e roteiro de QA | P32–P39 | `tasks` (teste cruzado), `task_acceptance_criteria`, `test_results`, `task_attachments` | F3 | Rafael Garcia |
| [F5](f5-sprint-e-horas.md) | Sprint, horas e data prevista | N3, P27–P31, P45–P51 | `sprints`, `tasks` (sprint, horas previstas, data prevista), `time_entries` | F3 | Rafael Garcia |
| [F6](f6-pacote.md) | Pacote (versão) | P41–P44 | `packages`, `tasks` (pacote) | F3 | Rafael Garcia |
| [F7](f7-indicadores.md) | Indicadores e histórico | P14, P15, P25, P53, P54 (só o que a v1 mostra) | leitura de `task_stage_movements`, `time_entries`, `tasks` | F3, F5 | Rafael Garcia |
| [F8](f8-conexoes-de-ia.md) | Conexões de IA e as 6 ferramentas | G4, IA1–IA14 | `ai_assistants`, `ai_authorizations`, `audit_logs`, tabelas do Passport | F1, F3, F5 | Lucas Scholz |

Autenticação, primeiro acesso com senha provisória e troca de senha vêm do template `ai-systems` e não têm funcional próprio. O bloco de segurança do padrão (`.ai/core/seguranca.md` §11, incluindo `core/mcp.md`) entra antes da primeira publicação e também não tem funcional.

## Ordem de escrita e de construção

1. **F1 Núcleo**: cadastros base; depende só do template.
2. **F2 Projeto e configuração** e **F3 Quadro e card**: o Trello da WCJ. Com F1 a F3 no ar já dá para usar no quadro Wpay.
3. **F5 Sprint e horas** e **F4 Teste e QA**: o que o Trello não tem.
4. **F6 Pacote** e **F7 Indicadores**: fecham o que o Rafael pediu.
5. **F8 Conexões de IA**: depende da prova de conceito do MCP (D-GER-17) e entra por cima dos Services já prontos.

## Formato de entrega

O `.md` deste repositório é a fonte. Quando o Rafael for ler, sai um PDF no padrão WCJ pelo mesmo gerador dos funcionais da PTA e do Oficinas (`wcj-identidade-office`), um por bloco ou um só com os oito.
