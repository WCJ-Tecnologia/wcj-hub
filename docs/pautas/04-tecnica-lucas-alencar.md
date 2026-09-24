# Pauta 04: Técnica (banco, migração do funil e interface de IA)

| | |
|---|---|
| Com | Lucas Alencar (pessoa técnica que aprova o banco, D-GER-01) |
| Conduz | Lucas Scholz |
| Duração | 60 min |
| Base | `docs/necessidade.md` inteiro, com foco em Núcleo, Migração do funil do DaaS e Interface de IA (MCP) |
| Saída | Ata em `docs/atas/AAAA-MM-DD-tecnica-lucas-alencar.md`; plano da etapa 2 (banco) e da prova de conceito MCP |

Esta reunião não fecha o banco. Fecha **como** o banco vai ser feito e a prova de conceito que tira o risco do ChatGPT (R-IA-01). Pode acontecer em paralelo às reuniões de Projetos e Chamados.

## Antes da reunião

- [ ] Lucas Alencar lê o `necessidade.md`, principalmente Núcleo, Interface de IA e RN-CRM-06 a 09.

## Roteiro

### 1. Stack e estrutura (10 min)
- Confirmar o template da casa (`stacks/laravel-inertia.md`).
- Estrutura decidida: **por domínio desde o início** (D-GER-15): `app/Platform` (Núcleo) + `app/Domains/{Projetos,Crm,Chamados}`, cada domínio com suas ferramentas MCP. Só validar com ele.
- PR: fluxo atual é dev abre PR → Tech Lead aprova (D-PRJ-09). Tech Lead é definido por projeto (D-PRJ-10). Confirmar se a v1 lê o status do PR do GitHub ou só guarda o link (F-PRJ-06).
- Hospedagem decidida: VPS do DaaS (D-GER-14). Confirmar capacidade (memória, disco), subdomínio, e banco do Hub separado do Supabase do DaaS (instância própria ou banco separado).

### 1b. Fluxo de desenvolvimento (dele, D-PRJ-11)
As 5 perguntas abertas no fim de [`levantamento/fluxo-desenvolvimento.md`](../levantamento/fluxo-desenvolvimento.md), todas respondidas (D-PRJ-12 a 16).

### 2. Núcleo e banco (15 min)
- Entidades do núcleo: usuários, papéis, clientes (CNPJ único), contatos, contratos.
- Trilha de auditoria em **tabela única** para todos os módulos, com canal (RN-NUC-04, D-GER-16).
- Histórico de etapa das tarefas (RN-PRJ-11): é dele que saem retrabalho e tempo por etapa.
- Numeração de chamado por sequência do banco (RN-CHM-02).
- Contato externo só vê o próprio cliente (RN-NUC-03): Policy por cliente em toda rota com ID.
- Etapa 2 (D-GER-16): IA propõe o `banco.txt`, Lucas Alencar revisa e aprova, validação final no Comitê.

### 3. DaaS
Sem migração de dados (D-CRM-03). Só combinar: o DaaS passa a consultar contratos no Hub (D-CRM-04) e o funil sai do DaaS quando o CRM do Hub estiver no ar (RN-CRM-09).

### 4. Interface de IA (MCP) (20 min)
- Servidor MCP com `laravel/mcp` e login por OAuth 2.1 com Passport (D-IA-07): validar no teste técnico.
- OAuth 2.1 com registro dinâmico de cliente (Passport?). Escopos por módulo e tipo de acesso.
- Toda ferramenta chama a mesma camada de serviço da tela (sem regra só no MCP).
- Limite por token (RN-IA-06): leitura 60/min, escrita 20/min, ajustáveis (D-IA-07). Validar.
- Conteúdo de cliente delimitado como dado (RN-IA-09): como devolver na resposta da ferramenta.
- **Prova de conceito MCP** (validação técnica; a dúvida de plano do ChatGPT já foi resolvida: escrita só no Business, Pro só lê, Plus não conecta):

| Item | Definição |
|---|---|
| Escopo | Servidor MCP mínimo, HTTPS público, OAuth, 1 ferramenta de leitura + 1 de escrita |
| Testes | Claude (app); Codex no app do ChatGPT (Plus) e no CLI; Claude Code; ChatGPT Pro só leitura. Em paralelo: GPT com Actions + OAuth (comercial) |
| Perguntas a responder | OAuth e registro dinâmico funcionam nos quatro clientes? Token só de leitura para o ChatGPT Pro funciona? Codex e Claude Code escrevem sem restrição de plano? |
| Quem faz | [definir] |
| Prazo | [definir] |
| Resultado vai para | Pauta 05 (comitê) |

### 5. Fechamento (5 min)
- Ler em voz alta as decisões e pendências.
- Proposta de `core/mcp.md` no `ai-systems`: as regras do MCP viram padrão da casa para todo sistema futuro? Quem escreve?

## O que precisa sair da reunião

- [ ] Stack e ambiente de publicação confirmados.
- [ ] Dinâmica e prazo da etapa 2 (banco).
- [ ] Plano da migração do funil do DaaS.
- [ ] Prova de conceito MCP com responsável e prazo.
- [ ] Decisão sobre `core/mcp.md` no padrão.
