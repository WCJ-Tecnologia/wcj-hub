# Tema: como aplicar o brandbook de um cliente

`tokens.json` é a única fonte de cor, fonte e raio do sistema. `resources/css/app.css` é **gerado** a partir dele por `scripts/build-theme.mjs`; nunca edite o CSS à mão.

## Passo a passo

1. Edite `design/tokens.json`:
    - `light.brand.primary` / `dark.brand.primary` e os `primaryForeground` (texto sobre a primária).
    - Neutros (`background`, `foreground`, `card`, `muted`, `border`…), status (`pending`, `approved`, `rejected`, `neutral`, cada um com `fg` e `bg`), `sidebar` e `charts` (exatamente 5).
    - `fonts.sans`, `fonts.heading`, `fonts.mono`. Webfont precisa ser hospedada em `public/` (a CSP não libera origem externa) e declarada com `@font-face` num CSS próprio importado por `app.tsx`.
    - `radius` (`0.5rem` de fábrica; a escala `sm`/`md`/`lg`/`xl`/`2xl` deriva dele).
    - Tudo em HEX de 6 dígitos. Todo token existe nos dois blocos, `light` e `dark`.
2. `npm run theme` — reescreve `app.css` com os valores em OKLCH.
3. Reinicie `npm run dev` (Tailwind v4 gera os utilitários no boot).
4. Confira as telas em claro e escuro, em 375 px e no desktop.
5. Rode o axe: `npm run e2e:prepare && npm run e2e:serve` (em outro terminal) e `npm run test:e2e:smoke`. O gate de `color-contrast` fica ligado; se um par de status ou a primária falhar, ajuste o token, não o teste.

## Regras

- Componente e página só usam token semântico (`bg-primary`, `text-muted-foreground`, `bg-status-approved-bg`…). Nada de hex, `text-[13px]` ou raio cravado fora deste arquivo.
- Texto sobre a primária precisa de contraste AA (4,5:1). O escuro costuma pedir uma primária mais clara com `primaryForeground` escuro.
- Nenhum dado, logo ou cor de cliente entra no template `ai-systems`. A marca vive no projeto derivado.
