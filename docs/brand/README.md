# Marca WCJ no Hub: arquivos para o front

Pasta criada em 06/10/2026 para o **Lucas Varanda** (front-end) adaptar o template `ai-systems` à marca da WCJ. É cópia fiel do mestre da marca, `wcj-master-brand-brain/marca/`, aprovado por Lucas Scholz em 05/10/2026, mais o Brand Board e as fontes. **Mudança na marca se faz no mestre e se copia para cá**, nunca o contrário.

O WCJ Hub é sistema interno da WCJ: aqui a marca entra inteira (logo, cores, tipografia, padrão de nós). Nos sistemas feitos para cliente ela não entra.

## O que tem aqui

| Pasta ou arquivo | O que é | Para que serve no Hub |
|---|---|---|
| [`MARCA.md`](MARCA.md) | O guia do mestre: cada versão do logo, quando usar, área de proteção, cores, padrão de nós, o que não fazer | Ler primeiro |
| [`brand-board.html`](brand-board.html) | Brand Board (Pilar 3 da identidade): tipografia, cores, componentes e regras numa página só. Abrir no navegador a partir desta pasta (as imagens apontam para `logo/` e `nos/`) | Referência visual do conjunto |
| [`cores.json`](cores.json) | As cores aprovadas: 4 azuis, alerta, 4 estados (crítico, alto, médio, bom; claro e escuro), 13 cinzas com o papel de cada um, cores dos nós | Fonte das cores; o `tokens.wcj.json` nasce daqui |
| [`tokens.wcj.json`](tokens.wcj.json) | **Rascunho** do `design/tokens.json` do template, nas mesmas chaves, já com as cores da marca | Ponto de partida; conferir e copiar para `design/tokens.json` |
| `logo/*.svg` | O logo em vetor: completo, horizontal, símbolo, ícone; versões fundo claro, fundo escuro, preto, branco | Cabeçalho, sidebar, tela de login, e-mail |
| `logo/icone/` | Ícone em PNG de 16 a 512 px, versão quadrada para iOS e Android, `favicon.ico` | `public/` do app: favicon e manifest |
| `logo/medidas.json` | Tamanho de cada versão e a margem, nas unidades do desenho | Posicionar por código |
| `nos/*.svg` + `nos/medidas.json` | O padrão de nós (trilha e recorte), claro e escuro, sem margem | Tela de login, estados vazios, cabeçalho de página; nunca atrás de texto corrido |
| `fontes/*.ttf` | Space Grotesk (títulos), Hanken Grotesk (corpo), Space Mono (rótulos, dados). Licença OFL | Hospedar em `public/fonts/` e declarar com `@font-face` (a CSP do template não libera origem externa) |

Ficaram fora, de propósito: os PNG grandes do logo (`png/`, para Word e PowerPoint) e as versões `compativel/` (para trocar arquivo antigo sem mexer em layout). Estão no mestre, se precisar.

## Como o template consome a marca

O template não aceita cor nem fonte espalhada pelo código. Tudo entra por um arquivo e vira CSS gerado (`ai-systems/design/README.md`):

1. `design/tokens.json`: cor, fonte e raio, em HEX, nos blocos `light` e `dark`, com todas as chaves preenchidas. O `tokens.wcj.json` desta pasta já está nesse formato.
2. `npm run theme` gera o `resources/css/app.css` em OKLCH. Nunca editar o CSS à mão.
3. Componente e página só usam token semântico (`bg-primary`, `text-muted-foreground`, `bg-status-approved-bg`). Nada de hex, tamanho cravado ou raio fora do `tokens.json`. O teste de arquitetura e o `test:guard` cobram isso.
4. Contraste AA (4,5:1) em todo texto; o gate `color-contrast` do axe roda na suíte e2e e fica ligado. Regra da marca: azul `#1A6CF3` em texto pequeno só sobre branco; sobre cinza-claro ou tint, `#0F4FC4`; sobre fundo escuro, `#4D90FF`. `python3 ferramentas/contraste.py` no mestre confere os pares do `cores.json`.
5. Webfont hospedada em `public/`, declarada num CSS próprio importado por `app.tsx`.

## O que pedir ao Lucas Varanda, nesta ordem

1. **Tema:** validar o `tokens.wcj.json` (em especial os pares de status e o `primaryForeground` no escuro), copiar para `design/tokens.json`, rodar `npm run theme`, conferir claro e escuro em 375 px e desktop, axe verde. Entra no **B0** ou logo depois dele, como PR próprio.
2. **Fontes:** Space Grotesk, Hanken Grotesk e Space Mono em `public/fonts/` com `@font-face`; `fonts.heading`, `fonts.sans` e `fonts.mono` apontando para elas.
3. **Logo e ícone:** logo na sidebar (horizontal ou símbolo, conforme a largura; `MARCA.md` diz a partir de quantos pixels cada versão vale), tela de login com o logo completo, favicon e manifest a partir de `logo/icone/`. Versão `fundo-escuro` sobre a sidebar `#0C0E13`; `fundo-claro` sobre página clara.
4. **Padrão de nós:** com parcimônia. Tela de login e estados vazios; um nó azul no máximo por tela; nunca atrás de texto. Fora de documentos e relatórios.
5. **Shell e componentes do Hub:** depois do tema, revisar com o `docs/funcional/` na mão o que o template não tem e o Hub pede: quadro kanban com arrastar e soltar (F3), ficha do card com seções recolhidas (F3, R4), diálogo de criar sprint ao arrastar (F5), roteiro de QA por item (F4), linha do tempo do card (F7), cartões de conexão de IA (F8). Propor os componentes antes de os blocos B3 e B5 começarem, para Cauã e Vitinho reaproveitarem em vez de inventar.

Regras que valem para o front do Hub, além da marca: `.ai/stacks/ui.md` (gabarito de CRUD e estados vazio, erro e carregando), `.ai/stacks/laravel-inertia.md` §6 (padrão de página Inertia) e a premissa de Projetos (nada na tela sugere trava onde o sistema não trava).

## Fonte

- Mestre: `~/Documents/Projetos/WCJ/wcj-master-brand-brain/marca/` (README, `cores.json`, `logo/`, `nos/`, `ferramentas/` com o código que gera tudo). Inventário das cópias nos projetos: `ONDE-ESTA.md` de lá.
- Brand Board: `wcj-master-brand-brain/Pilar 3 - Brand Board.html` (copiado aqui com os caminhos ajustados).
- Fontes: `wcj-identidade-office/Fontes WCJ/` (os pesos Medium e SemiBold foram corrigidos em 05/10/2026; esta cópia já é a corrigida).
