#!/usr/bin/env node
/**
 * Gera resources/css/app.css a partir de design/tokens.json.
 *
 *   npm run theme
 *
 * O tokens.json é a única fonte de cor, fonte e raio. Este script converte cada
 * HEX para OKLCH, escreve :root / .dark, o bloco @theme inline que expõe os
 * tokens como utilitários do Tailwind v4 e as regras de base que o template
 * precisa. Node puro, sem dependência. Token novo exige reiniciar o `npm run dev`.
 */
import { readFileSync, writeFileSync } from 'node:fs';
import { resolve } from 'node:path';

const root = resolve(import.meta.dirname, '..');
const tokensPath = resolve(root, 'design/tokens.json');
const cssPath = resolve(root, 'resources/css/app.css');

const tokens = JSON.parse(readFileSync(tokensPath, 'utf8'));

// ---------------------------------------------------------------------------
// sRGB (#RRGGBB) → OKLCH. Referência: Björn Ottosson, "A perceptual color space
// for image processing" (2020). Passos: gamma → linear, linear → LMS, raiz
// cúbica, LMS' → Lab, Lab → LCh.
// ---------------------------------------------------------------------------
function hexToRgb(hex, path) {
    const match = /^#?([0-9a-f]{6})$/i.exec(String(hex).trim());
    if (!match) throw new Error(`${path}: esperado HEX de 6 dígitos, recebi "${hex}"`);
    const int = parseInt(match[1], 16);
    return [(int >> 16) & 255, (int >> 8) & 255, int & 255].map((c) => c / 255);
}

function srgbToLinear(c) {
    return c <= 0.04045 ? c / 12.92 : ((c + 0.055) / 1.055) ** 2.4;
}

function hexToOklch(hex, path) {
    const [r, g, b] = hexToRgb(hex, path).map(srgbToLinear);

    const l = Math.cbrt(0.4122214708 * r + 0.5363325363 * g + 0.0514459929 * b);
    const m = Math.cbrt(0.2119034982 * r + 0.6806995451 * g + 0.1073969566 * b);
    const s = Math.cbrt(0.0883024619 * r + 0.2817188376 * g + 0.6299787005 * b);

    const L = 0.2104542553 * l + 0.793617785 * m - 0.0040720468 * s;
    const A = 1.9779984951 * l - 2.428592205 * m + 0.4505937099 * s;
    const B = 0.0259040371 * l + 0.7827717662 * m - 0.808675766 * s;

    const C = Math.sqrt(A * A + B * B);
    let h = C < 1e-4 ? 0 : (Math.atan2(B, A) * 180) / Math.PI;
    if (h < 0) h += 360;

    const fmt = (n, digits) => Number(n.toFixed(digits)).toString();
    return `oklch(${fmt(Math.max(0, Math.min(1, L)), 4)} ${fmt(C, 4)} ${fmt(h, 2)})`;
}

// ---------------------------------------------------------------------------
// Achata um bloco (light ou dark) em { '--nome-da-variavel': '#hex' }.
// ---------------------------------------------------------------------------
const kebab = (s) => s.replace(/[A-Z]/g, (c) => `-${c.toLowerCase()}`);

function require_(obj, key, path) {
    if (obj?.[key] === undefined) throw new Error(`${path}.${key} está faltando em design/tokens.json`);
    return obj[key];
}

function flatten(mode, name) {
    const out = {};
    const p = `tokens.${name}`;

    const brand = require_(mode, 'brand', p);
    out['--primary'] = require_(brand, 'primary', `${p}.brand`);
    out['--primary-foreground'] = require_(brand, 'primaryForeground', `${p}.brand`);

    const n = require_(mode, 'neutrals', p);
    for (const key of ['background', 'foreground', 'card', 'muted', 'mutedForeground', 'border', 'input', 'accent', 'destructive', 'ring']) {
        out[`--${kebab(key)}`] = require_(n, key, `${p}.neutrals`);
    }
    // Opcionais, derivados quando ausentes.
    out['--card-foreground'] = n.cardForeground ?? n.foreground;
    out['--popover'] = n.popover ?? n.card;
    out['--popover-foreground'] = n.popoverForeground ?? n.foreground;
    out['--secondary'] = n.secondary ?? n.muted;
    out['--secondary-foreground'] = n.secondaryForeground ?? n.foreground;
    out['--accent-foreground'] = n.accentForeground ?? n.foreground;
    out['--destructive-foreground'] = n.destructiveForeground ?? '#FFFFFF';

    const status = require_(mode, 'status', p);
    for (const key of ['pending', 'approved', 'rejected', 'neutral']) {
        const pair = require_(status, key, `${p}.status`);
        out[`--status-${key}`] = require_(pair, 'fg', `${p}.status.${key}`);
        out[`--status-${key}-bg`] = require_(pair, 'bg', `${p}.status.${key}`);
    }

    const sb = require_(mode, 'sidebar', p);
    out['--sidebar'] = require_(sb, 'background', `${p}.sidebar`);
    for (const key of ['foreground', 'primary', 'primaryForeground', 'accent', 'accentForeground', 'border', 'ring']) {
        out[`--sidebar-${kebab(key)}`] = require_(sb, key, `${p}.sidebar`);
    }

    const charts = require_(mode, 'charts', p);
    if (!Array.isArray(charts) || charts.length !== 5) throw new Error(`${p}.charts precisa de exatamente 5 cores`);
    charts.forEach((hex, i) => (out[`--chart-${i + 1}`] = hex));

    return out;
}

const light = flatten(require_(tokens, 'light', 'tokens'), 'light');
const dark = flatten(require_(tokens, 'dark', 'tokens'), 'dark');
const fonts = require_(tokens, 'fonts', 'tokens');
const radius = require_(tokens, 'radius', 'tokens');

for (const key of Object.keys(light)) {
    if (dark[key] === undefined) throw new Error(`${key} existe no claro mas não no escuro`);
}

const block = (vars, mode) =>
    Object.entries(vars)
        .map(([name, hex]) => `    ${name}: ${hexToOklch(hex, `${mode} ${name}`)}; /* ${hex.toUpperCase()} */`)
        .join('\n');

const themeColors = Object.keys(light)
    .map((name) => `    --color-${name.slice(2)}: var(${name});`)
    .join('\n');

const css = `/* GERADO por scripts/build-theme.mjs a partir de design/tokens.json — não edite à mão */
@import 'tailwindcss';
@import 'tw-animate-css';

@source '../../vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php';
@source '../../storage/framework/views/*.php';
@source '../**/*.blade.php';

@custom-variant dark (&:is(.dark *));

/* Tema claro. Cada valor vem de design/tokens.json (HEX ao lado, em comentário). */
:root {
    --radius: ${radius};

${block(light, 'light')}
}

/* Tema escuro. Todo token do claro tem par aqui. */
.dark {
${block(dark, 'dark')}
}

@theme inline {
    /* Fontes do sistema por padrão: a CSP não libera origem de terceiros.
       Para webfont, hospede em public/ e aponte em design/tokens.json. */
    --font-sans: ${fonts.sans};
    --font-heading: ${fonts.heading};
    --font-mono: ${fonts.mono};

    --radius-sm: calc(var(--radius) - 4px);
    --radius-md: calc(var(--radius) - 2px);
    --radius-lg: var(--radius);
    --radius-xl: calc(var(--radius) + 4px);
    --radius-2xl: calc(var(--radius) + 8px);

    /* Tamanho abaixo de xs para rótulos e eyebrows; nada de text-[Npx] em componente. */
    --text-2xs: 0.6875rem;
    --text-2xs--line-height: 1rem;

${themeColors}
}

@layer base {
    * {
        border-color: var(--border);
        outline-color: color-mix(in oklab, var(--ring) 50%, transparent);
    }
    body {
        background-color: var(--background);
        color: var(--foreground);
        font-family: var(--font-sans);
        -webkit-font-smoothing: antialiased;
    }
    h1,
    h2,
    h3,
    h4,
    .font-heading {
        font-family: var(--font-heading);
    }
    /* Foco visível padronizado: nunca remova o outline. */
    :focus-visible {
        outline: 2px solid var(--ring);
        outline-offset: 2px;
    }
}

/*
 * iOS dá zoom na página quando um campo focado tem menos de 16px. O text-sm
 * (14px) do Tailwind venceria o @layer base, então isto vive em utilities e
 * fixa 16px no celular sem travar o pinch-zoom.
 */
@layer utilities {
    @media (max-width: 767px) {
        input:not([type='checkbox']):not([type='radio']):not([type='hidden']):not([type='file']):not([type='range']):not([type='button']):not([type='submit']):not([type='reset']),
        select,
        textarea {
            font-size: 16px !important;
        }
    }

    .scrollbar-slim {
        scrollbar-width: thin;
        scrollbar-color: color-mix(in oklab, var(--primary) 40%, transparent) transparent;
    }
    .scrollbar-slim::-webkit-scrollbar {
        width: 8px;
        height: 8px;
    }
    .scrollbar-slim::-webkit-scrollbar-thumb {
        background: color-mix(in oklab, var(--primary) 35%, transparent);
        border-radius: 999px;
    }
}

@media (prefers-reduced-motion: reduce) {
    *,
    *::before,
    *::after {
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: 0.01ms !important;
    }
}
`;

writeFileSync(cssPath, css);
console.log(`resources/css/app.css gerado (${Object.keys(light).length} tokens × 2 temas).`);
