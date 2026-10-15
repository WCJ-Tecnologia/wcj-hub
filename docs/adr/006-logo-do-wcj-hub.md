# ADR-006: Logo do WCJ Hub

**Data:** 2026-10-14
**Estado:** aprovada
**Decidido por:** Lucas Scholz, 2026-10-14 (escolheu a opção 4 entre as variações)

## Contexto

O Hub usava o logo da WCJ Tecnologia (ADR-005): a sidebar, o login, o favicon e o ícone do app mostravam "wcj tecnologia", iguais aos do site e de qualquer peça da empresa. Na barra de abas e na tela inicial do celular, nada dizia que aquele era o Hub. A marca proíbe redesenhar ou recolorir o logo (`docs/brand/MARCA.md`).

## Decisão

1. **O logo do produto é o símbolo da WCJ com "Hub" ao lado.** O símbolo entra como está. O "Hub" é escrito na **Nico Moji**, a fonte do letreiro do logo da WCJ (licença OFL), com o H maiúsculo, convertido em contorno: o arquivo não depende da fonte instalada.
2. **Medidas.** O "Hub" tem a altura do símbolo, com a base na base dele, a 42% dessa altura de distância. Cores das quatro versões do logo da WCJ: símbolo azul, "Hub" na cor do "wcj" (ink no claro, branco no escuro).
3. **Fonte única no mestre da marca.** O gerador é `wcj-master-brand-brain/marca/ferramentas/vetorizar/hub.py`, com a fonte em `fonte/`; os arquivos saem em `marca/logo/produtos/wcj-hub/` e se copiam para `docs/brand/logo/produtos/wcj-hub/` e `public/brand/`, sem alteração.
4. **Uma versão do logo.** O `WBrandLogoAtom` usa o mesmo arquivo para `full` (login) e `horizontal` (sidebar, topbar); `symbol` continua o símbolo da WCJ (sidebar recolhida, abaixo de 16 px). O `alt` passa a "WCJ Hub".
5. **Ícone ink.** Favicon (`favicon.svg`, `favicon.ico`) com o símbolo `#4D90FF` em quadrado `#0C0E13`; ícone de app (`apple-touch-icon.png`, manifest) com o "Hub" branco embaixo do símbolo, e a versão "maskable" na zona segura. O ink separa a aba do Hub da do site (azul) e é o `theme_color` do app.
6. **E-mail:** o ícone do cabeçalho passa a ser o do Hub (`wcj-hub-icone-64.png`), com o nome em texto como antes.

## Alternativas descartadas

Testadas em 14/10 e recusadas pelo Lucas:

- "wcj" + "hub" com letras recortadas do "tecnologia" (completo, horizontal, empilhado): estranho, e o "wcj" sobrava.
- Símbolo + "hub" minúsculo montado com peças do letreiro: letra esquisita.
- Símbolo + "Hub"/"HUB" em Space Grotesk, Hanken Grotesk ou Space Mono: fonte de texto, fora do letreiro do logo.
- Símbolo + "HUB" desenhado à mão no estilo do letreiro, e "HUB" na Nico Moji, normal ou engrossado: preferiu o "Hub".

## Consequências

- Trocar o logo do Hub é rodar `hub.py` no mestre e copiar os arquivos. Não se edita SVG à mão.
- Os arquivos do logo da WCJ (`wcj-logo-*`, `wcj-icone-*`) saem de `public/brand/`, porque nada no app usa mais; seguem em `docs/brand/logo/`.
- O link do logo continua com o nome "Início" (ADR-005 e N7); o `alt` "WCJ Hub" não colide mais com o cliente "WCJ Tecnologia".

## Como verificar

- Sidebar aberta e recolhida, login (desktop e 375 px), claro e escuro, no navegador.
- `npm run build-storybook` e a story `Atoms/Brand Logo`.
- e2e com axe (`link-name`, `image-alt`) sem violação.
