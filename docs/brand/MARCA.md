# Marca WCJ: arquivos-mestre

> **Aprovado pelo Lucas em 05/10/2026.** Logo: traçado aprovado; no fundo escuro, símbolo e "tecnologia" em #4D90FF; ícone em quadrado azul com o símbolo branco. Cores: vermelho de alerta, escala de quatro estados e escala de cinzas. Padrão de nós: trilha e recorte. A troca das cópias antigas do logo está em andamento: ver `ONDE-ESTA.md`.

Esta pasta passa a ser a fonte única dos arquivos de marca. Antes, o logo existia como 91 cópias de PNG espalhadas por 17 projetos, e o "SVG oficial" era uma exportação do Canva com imagem embutida.

## `logo/`

O logo em vetor, traçado em cima do arquivo de maior resolução que existe (a máscara de 2006 px de largura que estava dentro do SVG do Canva). O desenho é o mesmo; saíram a sombra interna das letras e os degraus de exportação.

| Arquivo | O que é | Quando usar |
|---|---|---|
| `wcj-logo-*.svg` | Símbolo + "wcj" + "tecnologia" | Padrão. Desenho com 40 px de altura ou mais. |
| `wcj-logo-horizontal-*.svg` | Símbolo + "wcj", sem "tecnologia" | Desenho entre 16 e 40 px de altura (cabeçalho de site já rolado, assinatura de e-mail). |
| `wcj-simbolo-*.svg` | Só o símbolo | Abaixo de 16 px, ou como elemento gráfico. |
| `wcj-icone.svg` | Símbolo branco em quadrado azul de cantos arredondados | Favicon, ícone de app, foto de perfil. |
| `wcj-icone-quadrado.svg` | O mesmo, sem arredondar | Plataformas que aplicam a própria máscara (iOS, Android). |
| `medidas.json` | Tamanho de cada versão e a margem, nas unidades do desenho | Para quem for posicionar por código. |
| `png/` | As mesmas versões em PNG de fundo transparente, em várias larguras (o número no nome) | Onde SVG não entra: Word, PowerPoint, e-mail. |
| `icone/` | Ícone em PNG (16 a 512 px), versão quadrada para iOS e Android, e `favicon.ico` | Sites e apps. |
| `compativel/` | O desenho novo na moldura do PNG antigo (1563×625, desenho no mesmo lugar) | Só para substituir o arquivo antigo em peça que já existe, sem mexer no layout. Peça nova usa as versões acima. |

Sufixos de cor:

| Sufixo | Símbolo e "tecnologia" | "wcj" | Fundo |
|---|---|---|---|
| `fundo-claro` (símbolo: `azul`) | `#1A6CF3` | `#0C0E13` | branco, `#F5F7FB` |
| `fundo-escuro` (símbolo: `azul-claro`) | `#4D90FF` | `#FFFFFF` | `#070B16`, `#0C0E13` |
| `preto` | `#0C0E13` | `#0C0E13` | claro, quando só há uma cor |
| `branco` | `#FFFFFF` | `#FFFFFF` | azul `#1A6CF3`, foto escura, uma cor |

**Área de proteção.** Cada arquivo já traz a margem: o raio de um nó (83,3 unidades do desenho) em toda a volta. Nada de texto, imagem ou borda dentro dela. Para alinhar o logo a outro elemento, alinhe pelo desenho, não pela borda do arquivo.

**O que não fazer.** Não trocar as cores fora das quatro versões, não esticar, não aplicar sombra, contorno ou degradê, não usar a versão completa abaixo de 40 px.

## `cores.json`

As cores aprovadas, para os projetos lerem em vez de copiar à mão: os quatro azuis, o vermelho de alerta (`#FF5A45`, com `#D12B15` para texto sobre fundo claro), os quatro estados (crítico, alto, médio, bom; cada um com cor de texto, selo e as versões para fundo escuro), os treze cinzas com o papel de cada um e as cores do padrão de nós.

`python3 ferramentas/contraste.py` confere os pares em que cada cor é usada como texto e falha se algum ficar abaixo de 4,5:1. Rodar sempre que o `cores.json` mudar.

Regras de uso (estão no Brand Board, seção 02): azul `#1A6CF3` em texto pequeno só sobre branco; sobre cinza-claro ou tint, `#0F4FC4`; sobre fundo escuro, `#4D90FF`. Verde, âmbar e laranja só em dado e em tela de produto.

## `nos/`

O padrão de nós, tirado do desenho do logo. Os arquivos saem sem margem, porque o uso é encostar na peça e deixar a borda cortar.

| Arquivo | O que é | Quando usar |
|---|---|---|
| `wcj-trilha-fundo-escuro.svg`, `wcj-trilha-fundo-claro.svg` | O nó solto em azul e o corpo do símbolo continuado por 12 nós, na grade do logo | O padrão. Entra por um lado da peça e sai pela borda; corta-se o que sobrar. |
| `wcj-recorte-fundo-escuro.svg`, `wcj-recorte-fundo-claro.svg` | O símbolo inteiro numa cor só | Capa com área livre grande: símbolo gigante, cortado pela borda. |
| `medidas.json` | Raio do nó, distâncias da grade e as cores | Para quem for desenhar o padrão por código (site, vídeo). |

A cor fica um tom acima do fundo: `#091833` sobre navy e `#E3ECFA` sobre cinza-claro (o fundo com 13% do azul primário no escuro e 8% no claro). Sobre fundo com degradê, pinte o desenho com o azul primário a 13% de opacidade (8% no claro): dá o mesmo resultado em qualquer ponto do fundo. É o que o site faz no hero, com o SVG embutido na página. No máximo um nó em azul por peça. Sem brilho, sem desfoque, sem linha fina entre pontos, e nunca atrás de texto corrido. Entra em site, deck, carrossel e vídeo; relatório e documento seguem sem nós. No vídeo, vale em qualquer estilo (decisão de 06/10/2026): o estilo muda o resto da cena, os nós não.

## `ferramentas/vetorizar/`

O código que gerou os arquivos de `logo/`, para o desenho poder ser refeito ou conferido. `bash gerar.sh` roda tudo e falha se a sobreposição com a origem cair abaixo de 99,5% (hoje: 99,70%).

| Arquivo | Papel |
|---|---|
| `origem/mascara-2006x788.png` | A fonte: a máscara que estava embutida em `logo_wcj_fundo_branco.svg`. |
| `tracar.py` | Contorno em retas, arcos de círculo e curvas de Bézier, com nós nos extremos, como em desenho de tipos. Terminais redondos viram semicírculos exatos. |
| `monta.py` | Regulariza o que o desenho mostra ser regra: os 5 nós com o mesmo raio e na mesma grade (maior ajuste: 0,1 px) e os dois "o" idênticos. Grava `logo_limpo.json`. |
| `logo_limpo.json` | O desenho final em primitivas. Os SVG saem daqui. |
| `svg.py` | Grava as versões e as cores. É onde se muda cor, margem ou se cria versão nova. |
| `exporta.py` | Gera os PNG, os ícones, o favicon e a versão compatível, sem navegador. |
| `exporta_ts.py` | Grava o desenho como dados para código (TypeScript). É de onde sai o `logoPaths.gen.ts` do kit de vídeo. |
| `nos.py` | Gera `nos/`: a trilha (contorno único, conferido contra as cópias do corpo) e o recorte. Com `--ts`, grava o mesmo desenho como dados para código: é de onde sai o `nosPaths.gen.ts` do kit de vídeo. |
| `conferencia_svg.py`, `compara.py` | Renderizam o vetor e comparam com a origem. |

`ferramentas/previa-de-link/` guarda a fonte da imagem de prévia de link do site (`wcj-site.html`, 1200 x 630) e o `gerar.sh` que a transforma no `og-image.png`.

`ferramentas/inventario.py` lista onde estão as cópias do logo nos projetos, pelo conteúdo do arquivo, e grava `ONDE-ESTA.md`. Com `--trocar`, substitui as cópias antigas das pastas internas pelos arquivos de `logo/compativel/`.

## O que falta

- Cor para impressão (achado M16). Decisão de 05/10/2026: fica para quando houver a próxima peça impressa. Aí se gera a folha de prova com as receitas de azul e de navy, escolhe-se no papel e o resultado entra aqui e no Brand Board. O PDF do logo para gráfica depende disso.
- Levar as cores e o padrão de nós aos projetos: app (`build-theme`), site, skill de carrossel, kit de vídeo e geradores do Office.
- Terminar a troca das cópias antigas nos projetos (`ONDE-ESTA.md`).
