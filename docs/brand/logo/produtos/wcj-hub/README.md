# Logo do WCJ Hub

> **Aprovado pelo Lucas em 14/10/2026** (opção 4). Gerado por `ferramentas/vetorizar/hub.py`; não se edita à mão.

O WCJ Hub é o sistema interno de gestão de projetos da WCJ. O logo dele é o símbolo da WCJ com "Hub" ao lado, escrito na **Nico Moji**, a fonte do letreiro do logo da WCJ ("wcj" e "tecnologia"). O texto está convertido em contorno: os arquivos não dependem da fonte instalada.

- "Hub" com o H maiúsculo, com a altura do símbolo e a base na base dele, a 42% dessa altura de distância.
- Espaço extra de 30 unidades da fonte entre as letras.
- Fonte: `ferramentas/vetorizar/fonte/NicoMoji-Regular.ttf`, licença SIL OFL 1.1 (`fonte/OFL.txt`), do repositório `google/fonts` (`ofl/nicomoji`).

## Versões

| Arquivo | O que é | Quando usar |
|---|---|---|
| `wcj-hub-logo-*.svg` | Símbolo + "Hub" ao lado | Sidebar, topbar, login: 16 px de altura ou mais |
| `wcj-simbolo-*.svg` (em `logo/`) | O símbolo da WCJ, sem mudança | Abaixo de 16 px, sidebar recolhida |
| `wcj-hub-icone.svg` | Símbolo `#4D90FF` em quadrado ink `#0C0E13` de cantos arredondados | Favicon e ícones até 64 px (`icone/wcj-hub-icone-*.png`, `favicon.ico`) |
| `wcj-hub-icone-app.svg` | O mesmo, com o "Hub" em branco embaixo do símbolo | Ícone de app, 120 px ou mais (`icone/wcj-hub-icone-app-*.png`; o de 180 é o `apple-touch-icon`) |
| `wcj-hub-icone-app-quadrado.svg` | Sem arredondar, com o desenho dentro da zona segura (círculo de 80%) | Android "maskable" e plataformas que aplicam a própria máscara |
| `png/` | O logo em PNG transparente (400, 800 e 1600 px de largura) | Onde SVG não entra: e-mail, Word, PowerPoint |

Cores das quatro versões (`fundo-claro`, `fundo-escuro`, `preto`, `branco`): as do logo da WCJ, com o "Hub" na cor do "wcj".

O ícone do produto é ink, não azul: a aba do Hub no navegador não se confunde com o site da WCJ, e o ícone combina com a sidebar e o `theme_color` do app (`#0C0E13`).

A área de proteção é a do logo da WCJ: o raio de um nó (83,3 unidades) em toda a volta, já embutido nos arquivos. Valem as mesmas proibições: não esticar, não recolorir fora das quatro versões, sem sombra, contorno ou degradê.

## Como refazer

```bash
cd ferramentas/vetorizar && python3 hub.py
```

Precisa de `numpy`, `pillow`, `shapely` e `fonttools` (`requirements.txt`). Grava os SVG, `png/`, `icone/` e `medidas.json` nesta pasta. Depois, copiar para `wcj-hub/docs/brand/logo/produtos/wcj-hub/` e para `wcj-hub/public/brand/` (ADR-006 do Hub).
