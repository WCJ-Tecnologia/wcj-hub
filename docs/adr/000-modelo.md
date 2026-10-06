# ADR-NNN: <título curto da decisão>

Um arquivo por decisão em `docs/adr/NNN-<assunto>.md`. Decisão técnica relevante ou mudança crítica (`playbooks/manutencao.md`, fase Specify) vira ADR antes de ser implementada.

Aprovado este ADR, registre no `.specs/STATE.md`, seção Decisions, um `AD-NNN` que cite este arquivo, mesmo que a skill `tlc-spec-driven` pule o Design. É por esse registro que ela lê as decisões ativas antes de desenhar (`.ai/core/especificacao.md`, seção 5).

**Data:** <AAAA-MM-DD>
**Estado:** proposta | aprovada | substituída por ADR-NNN
**Decidido por:** <quem aprovou>

## Contexto

<O problema ou a mudança pedida, e por que precisa de decisão. Fatos, não opinião.>

## Decisão

<O que foi decidido, em uma ou duas frases imperativas.>

## Alternativas descartadas

- **<Alternativa A>:** <por que não>
- **<Alternativa B>:** <por que não>

## Consequências

- <O que fica mais fácil>
- <O que fica mais difícil ou passa a exigir cuidado>
- <O que precisa mudar em código, banco, deploy ou documentação>

## Como verificar

<Como saber que a decisão foi aplicada. Teste, comando, tela.>
