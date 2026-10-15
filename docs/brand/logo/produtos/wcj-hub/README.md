# Logo do WCJ Hub

> **Proposta de 14/10/2026, aguarda aprovação do Lucas.** Gerado por `ferramentas/vetorizar/hub.py`; não se edita à mão.

O WCJ Hub é o sistema interno de gestão de projetos da WCJ. O logo dele é o da WCJ com o nome do produto: o símbolo e o "wcj" entram como estão, e o "hub" é montado com as peças do letreiro "tecnologia" do logo oficial, sem fonte e sem desenho novo.

| Letra | De onde vem |
|---|---|
| h | o "n" do "tecnologia", com a haste do "l" sobre a haste esquerda |
| u | o "n" girado 180° em volta do próprio centro |
| b | o bojo do "o", com uma haste do mesmo topo e raio do "l"; os cantos do bojo que encostam na haste ficam retos |

O espaço entre as letras é o do "tecnologia" (33 unidades). O "hub" leva a cor do "tecnologia": é o descritor, como no logo completo da WCJ.

## Versões

| Arquivo | O que é | Quando usar |
|---|---|---|
| `wcj-hub-logo-*.svg` | Símbolo + "wcj" + "hub" embaixo, com a largura do "wcj" | 40 px de altura ou mais (painel do login). Mesma proporção do logo completo da WCJ |
| `wcj-hub-logo-horizontal-*.svg` | Símbolo + "wcj" + "hub" ao lado; as hastes do h e do b têm a altura do "wcj" | De 16 a 40 px (sidebar, topbar) |
| `wcj-simbolo-*.svg` (em `logo/`) | O símbolo da WCJ, sem mudança | Abaixo de 16 px, sidebar recolhida |
| `wcj-hub-icone.svg` | Símbolo `#4D90FF` em quadrado ink `#0C0E13` de cantos arredondados | Favicon e ícones até 64 px (`icone/wcj-hub-icone-*.png`, `favicon.ico`) |
| `wcj-hub-icone-app.svg` | O mesmo, com o "hub" em branco embaixo do símbolo | Ícone de app, 120 px ou mais (`icone/wcj-hub-icone-app-*.png`; o de 180 é o `apple-touch-icon`) |
| `wcj-hub-icone-app-quadrado.svg` | Sem arredondar, com o desenho dentro da zona segura (círculo de 80%) | Android "maskable" e plataformas que aplicam a própria máscara |
| `png/` | Logo completo e horizontal em PNG transparente (400, 800 e 1600 px de largura) | Onde SVG não entra: e-mail, Word, PowerPoint |

Cores das quatro versões (`fundo-claro`, `fundo-escuro`, `preto`, `branco`): as mesmas do logo da WCJ, com o "hub" na cor do "tecnologia".

O ícone do produto é ink, não azul: assim a aba do Hub no navegador não se confunde com o site da WCJ, e o ícone combina com a sidebar e o `theme_color` do app (`#0C0E13`).

A área de proteção é a mesma do logo da WCJ: o raio de um nó (83,3 unidades) em toda a volta, já embutido nos arquivos. Valem as mesmas proibições: não esticar, não recolorir fora das quatro versões, sem sombra, contorno ou degradê.

## Como refazer

```bash
cd ferramentas/vetorizar && python3 hub.py
```

Precisa de `numpy` e `pillow` (`requirements.txt`). Grava os SVG, `png/`, `icone/` e `medidas.json` nesta pasta. Depois, copiar para `wcj-hub/docs/brand/logo/produtos/wcj-hub/` e para `wcj-hub/public/brand/` (ADR-006 do Hub).
