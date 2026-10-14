# ADR-005: Identidade visual da WCJ no Hub

**Data:** 2026-10-08
**Estado:** aprovada
**Decidido por:** Lucas Scholz, 2026-10-09 (comentário no PR #8)

## Contexto

O Hub é sistema interno da WCJ, então a marca entra inteira (`docs/brand/README.md`). O B0 já tinha copiado os tokens de cor do `tokens.wcj.json`, as fontes e o ícone. Faltavam:

- **Logo:** o `public/brand/logo.svg` era o provisório do template.
- **Primária:** um token só, `#0F4FC4`. O azul da marca, `#1A6CF3`, dá 4,37:1 como texto sobre `#F5F7FB` e reprova no AA, então o B0 usou o profundo em tudo, inclusive nos botões, que ficaram mais escuros que a marca.
- **Cantos:** 12 px. O Brand Board pede 5 px em tela de produto.
- **Login:** círculos desfocados, e a marca proíbe brilho e desfoque.
- **Tipografia:** pesos sem arquivo (500 caía no 400, 800 virava negrito sintético). Eyebrow e números não seguiam o Brand Board.
- **E-mails:** cinzas genéricos.
- **Manifest:** não existia.

## Decisão

1. **Dois azuis.**
   - `brand.primary` é o da marca: `#1A6CF3` no claro, com texto branco (4,68:1), e `#4D90FF` no escuro. É usado em fundo de botão, foco, seleção e checkbox.
   - Token novo `brand.primaryText`, que gera `--primary-text` e a classe `text-primary-text`: `#0F4FC4` no claro (6,67:1 sobre `#F5F7FB`) e `#4D90FF` no escuro. É o azul de **texto** (link, eyebrow, número de indicador), como manda a regra de contraste do Brand Board.
2. **Cantos de 8 px** (revisto por Lucas Scholz em 14/10/2026, ao ver o quadro no Storybook: os 5 px do Brand Board deixavam tudo "muito reto" em tela de produto). `radius` é `0.5rem`, e a escala do `build-theme.mjs` fica `sm` 6 px, `md` 8 px, `lg` 10 px, `xl` 12 px e `2xl` 16 px. Botão e campo ficam em 8 px; cartão e modal, um pouco mais. O card do quadro usa `md` no corpo e `xl` na aba da prioridade. Até 14/10 valia 5 px (`0.3125rem`; `sm` 3, `md`/`lg` 5, `xl` 8, `2xl` 10).
3. **Logo pelos arquivos oficiais, sem configuração.**
   - Os SVG de `docs/brand/logo/` são copiados sem alteração para `public/brand/` e escolhidos pelo `WBrandLogoAtom` (`variant`: full, horizontal ou symbol; `surface`: light, dark ou auto).
   - Sai o `APP_BRAND_LOGO` / `app.brand.logo` do template, porque o Hub é só da WCJ.
   - A regra de tamanho da marca vale: completo a partir de 40 px de altura, horizontal entre 16 e 40 px, símbolo abaixo de 16 px. A sidebar aberta usa o horizontal, a recolhida usa o símbolo e o painel do login usa o completo.
4. **Padrão de nós só no login.** O recorte (`wcj-recorte-fundo-escuro.svg`) fica no canto livre do painel, no lugar dos círculos desfocados. A trilha não veio do mestre, e os estados vazios ficam sem nós.
5. **Tipografia.**
   - Faixas de peso no `@font-face`, para nunca haver negrito sintético.
   - Eyebrow em Space Mono, maiúsculo e azul, com o fio.
   - Rótulos (cabeçalho de tabela, grupos do menu) em Space Mono.
   - Número do indicador em Space Mono azul.
6. **Sidebar sempre em ink (`#0C0E13`), nos dois temas.** No escuro, o fundo da página também é ink, no lugar do navy `#070B16` do `tokens.wcj.json`, porque o navy numa área grande, ao lado do ink, parecia outra cor. A sidebar se separa da página pela borda `#232A38`.
7. **Ícone com fundo:** azul sobre o tint (`bg-accent text-accent-foreground`).
8. **E-mail:**
   - cores da marca em hex inline: é a única exceção à regra de "só token", porque cliente de e-mail não lê CSS externo;
   - ícone PNG mais o nome em texto, porque Gmail e Outlook bloqueiam SVG.
9. **Manifest:** `public/site.webmanifest`, com os ícones da marca e `theme_color` `#0C0E13`.

## Alternativas descartadas

- **Manter só `#0F4FC4`:** seguro, mas o botão, o lugar onde a marca mais aparece, não seria o azul da marca.
- **`#1A6CF3` também para texto:** reprova no AA sobre o fundo claro e no tint.
- **Manter 12 px:** contraria o Brand Board em tela de produto.
- **Logo configurável por `.env`:** a indireção só servia ao template multi-cliente e não escolhe versão nem fundo.

## Consequências

- Texto azul novo usa `text-primary-text`, nunca `text-primary`.
- O `build-theme.mjs` exige `brand.primaryText` nos dois temas, e a escala de raio do Hub diverge da do template.
- Trocar o logo é trocar os SVG em `public/brand/`, a partir do mestre da marca. Não se redesenha nem se recolore.
- Pendências: a trilha de nós, os nós nos estados vazios e os PNG do logo para o e-mail. Ficam para quando os arquivos vierem do mestre (`wcj-master-brand-brain/marca/`).

## Como verificar

- `npm run theme` gera `--primary: #1A6CF3`, `--primary-text` e a escala de raio.
- O e2e com axe (`color-contrast` ligado) passa em claro, escuro e com o modal aberto.
- As stories do Storybook passam sem violação WCAG A/AA nos dois temas.
