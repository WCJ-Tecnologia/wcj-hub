# Skills do padrão

Skill que o padrão WCJ adota inteira mora aqui, como cópia fiel do repositório de origem. O sync a leva aos projetos junto com o resto do `.ai/`, e o `instalar.mjs` a copia para a pasta que cada ferramenta de IA lê. Como a WCJ usa a skill: `.ai/core/especificacao.md`.

## O que tem aqui

| Skill | Versão | Origem | Licença |
|---|---|---|---|
| `tlc-spec-driven` | 3.3.0 | [tech-leads-club/agent-skills](https://github.com/tech-leads-club/agent-skills/tree/916a86748ff5600a86839007cca7bed8da19d462/packages/skills-catalog/skills/%28development%29/tlc-spec-driven), commit `916a867` de 03/08/2026. Autor: Felipe Rodrigues, Tech Leads Club | [CC-BY-4.0](https://creativecommons.org/licenses/by/4.0/), declarada na skill; MIT, a do repositório (`tlc-spec-driven/LICENSE`). Usada sem modificação |

Adotada inteira pelo time em 29/09/2026.

## Regras

- **Não edite a cópia.** Os 18 arquivos são iguais byte a byte aos do commit acima. O único acréscimo é o `LICENSE` do repositório de origem, que a licença MIT manda levar junto. O que a WCJ muda no uso fica em `.ai/core/especificacao.md` e no `conferir-specs.mjs`, nunca aqui dentro.
- Projeto derivado também não edita: o sync sobrescreve. Achou problema, abra no ai-systems.
- Skill própria do projeto não mora aqui, porque o sync apaga `.ai/skills/` inteira a cada versão. Ela vai direto em `.claude/skills`, `.cursor/skills` ou `.codex/skills`.
- A cópia que as ferramentas leem (`.claude/skills`, `.cursor/skills`, `.codex/skills`) é gerada pelo `instalar.mjs`. O `npm run test:skills` confere que ela bate com o modelo.

## Arquivos da WCJ

| Arquivo | Para quê |
|---|---|
| `instalar.mjs` | copia cada skill para `.claude/skills`, `.cursor/skills` e `.codex/skills`; com `--check`, só confere. Recusa pasta de ferramenta que seja link para fora do projeto |
| `conferir-specs.mjs` | portão do CI e da aprovação: roda os validadores da skill em cada feature do `.specs/` e cobre o que eles deixam passar (`.ai/core/especificacao.md`, seção 2) |
| `*.test.mjs` | testes dos dois (`npm run test:skills`, ou `node --test .ai/skills/*.test.mjs`) |

Cópia, e não link simbólico, porque link no git não funciona no Windows sem configuração. O próprio instalador do Tech Leads Club copia quando não consegue criar o link.

## Atualizar a skill

Só no ai-systems, por PR, como qualquer mudança do padrão:

1. Escolha o commit novo no repositório de origem e leia o diff inteiro da pasta da skill.
2. Baixe os arquivos desse commit para `tlc-spec-driven/` e confira o hash de cada um contra a árvore do commit (`git hash-object <arquivo>` contra `gh api repos/tech-leads-club/agent-skills/git/trees/<commit>?recursive=1`).
3. Leia os scripts Python por inteiro: eles rodam em todo projeto da WCJ. Se aparecer rede, subprocesso ou escrita fora do `.specs/`, não atualize.
4. Confira se as regras do `conferir-specs.mjs` ainda fazem sentido: a normalização dos critérios, as fases, a aprovação e a escolha da feature concluída. Os testes que rodam o validador da skill sozinho avisam quando ele muda de comportamento.
5. Rode `node .ai/skills/instalar.mjs` e `npm run test:skills`. `.ai/VERSION` e `CHANGELOG.md` sobem no mesmo PR. Depois vem o sync nos derivados.

## Pré-requisito

Python 3 no PATH como `python3`. Os validadores da skill são scripts Python que só usam a biblioteca padrão. O Mac e o CI têm; no Windows, confira `python3 --version` antes de usar a skill.
