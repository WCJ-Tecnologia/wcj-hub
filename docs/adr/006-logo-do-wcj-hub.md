# ADR-006: Logo do WCJ Hub

**Data:** 2026-10-14
**Estado:** proposta
**Decidido por:** aguarda Lucas Scholz

## Contexto

O Hub usava o logo da WCJ Tecnologia (ADR-005): a sidebar, o login, o favicon e o ícone do app mostravam "wcj tecnologia", iguais aos do site e de qualquer peça da empresa. Na barra de abas e na tela inicial do celular, nada dizia que aquele era o Hub. A marca proíbe redesenhar ou recolorir o logo (`docs/brand/MARCA.md`), então o logo do produto não podia ser um desenho novo.

## Decisão

1. **O logo do produto é o da WCJ com "hub" no lugar de "tecnologia".** O símbolo e o "wcj" entram como estão. O "hub" é montado com as peças do letreiro "tecnologia" do próprio logo: h = "n" + haste do "l"; u = "n" girado 180°; b = bojo do "o" + haste. Sem fonte e sem desenho novo, no mesmo espaçamento e na cor do "tecnologia".
2. **Fonte única no mestre da marca.** O gerador é `wcj-master-brand-brain/marca/ferramentas/vetorizar/hub.py`; os arquivos saem em `marca/logo/produtos/wcj-hub/` e se copiam para `docs/brand/logo/produtos/wcj-hub/` e `public/brand/`, sem alteração.
3. **Duas versões, a mesma regra de tamanho do ADR-005.**
   - Completo (`wcj-hub-logo-*`): "hub" embaixo do "wcj", com a largura dele; mesma proporção do logo completo da WCJ. Painel do login.
   - Horizontal (`wcj-hub-logo-horizontal-*`): "hub" ao lado do "wcj", com as hastes na altura dele. Sidebar aberta, topbar, login no celular.
   - Abaixo de 16 px e na sidebar recolhida segue o símbolo da WCJ.
4. **Ícone ink.** Favicon (`favicon.svg`, `favicon.ico`) com o símbolo `#4D90FF` em quadrado `#0C0E13`; ícone de app (`apple-touch-icon.png`, manifest) com o "hub" embaixo do símbolo e a versão "maskable" na zona segura. O ink separa a aba do Hub da do site (azul) e é o `theme_color` do app.
5. **E-mail:** o ícone do cabeçalho passa a ser o do Hub (`wcj-hub-icone-64.png`), com o nome em texto como antes.
6. **O `WBrandLogoAtom` não muda de API.** `full` e `horizontal` apontam para os arquivos do Hub; `symbol` continua o da WCJ; o `alt` passa a "WCJ Hub".

## Alternativas descartadas

- **"hub" em Space Grotesk ao lado do logo da WCJ, com um fio:** mais simples, mas mistura fonte de texto com o letreiro desenhado do logo; fica com cara de rótulo, não de marca.
- **"hub" no lugar de "tecnologia", no mesmo corpo:** três letras embaixo de "wcj" deixavam um vazio à direita e somem no tamanho do login.
- **Símbolo novo (um "H" de nós):** a marca não admite redesenho, e o símbolo da WCJ já é o que identifica a empresa.
- **Ícone azul com o "hub":** igual demais ao ícone do site da WCJ na barra de abas.

## Consequências

- Trocar o logo do Hub é rodar `hub.py` no mestre e copiar os arquivos. Não se edita SVG à mão.
- Os arquivos do logo da WCJ (`wcj-logo-*`, `wcj-icone-*`) saem de `public/brand/`, porque nada no app usa mais; seguem em `docs/brand/logo/`.
- O link do logo continua com o nome "Início" (ADR-005 e N7); o `alt` "WCJ Hub" não colide mais com o cliente "WCJ Tecnologia".

## Como verificar

- Sidebar aberta e recolhida, login (desktop e 375 px), claro e escuro, no navegador.
- `npm run build-storybook` e a story `Atoms/Brand Logo`.
- e2e com axe (`link-name`, `image-alt`) sem violação.
