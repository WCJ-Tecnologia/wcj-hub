/**
 * Cores das etiquetas de tipo e área (F3 §03; decisão de Lucas Scholz em 14/10, como no Trello).
 * Os tokens `--tag-<cor>` e `--tag-<cor>-bg` vêm de `design/tokens.json` (claro e escuro, AA conferido).
 */
import type { TagColor } from '@/types/quadro';

/** Na ordem do seletor. */
export const TAG_COLORS: TagColor[] = ['gray', 'blue', 'sky', 'green', 'lime', 'yellow', 'orange', 'red', 'pink', 'purple'];

export const TAG_COLOR_LABEL: Record<TagColor, string> = {
    gray: 'Cinza',
    blue: 'Azul',
    sky: 'Azul-claro',
    green: 'Verde',
    lime: 'Verde-limão',
    yellow: 'Amarelo',
    orange: 'Laranja',
    red: 'Vermelho',
    pink: 'Rosa',
    purple: 'Roxo',
};

/** Classes de fundo e texto da etiqueta; sem cor, o cinza neutro. As classes ficam inteiras para o Tailwind achar. */
const TAG_CLASSES: Record<TagColor, string> = {
    gray: 'bg-tag-gray-bg text-tag-gray',
    blue: 'bg-tag-blue-bg text-tag-blue',
    sky: 'bg-tag-sky-bg text-tag-sky',
    green: 'bg-tag-green-bg text-tag-green',
    lime: 'bg-tag-lime-bg text-tag-lime',
    yellow: 'bg-tag-yellow-bg text-tag-yellow',
    orange: 'bg-tag-orange-bg text-tag-orange',
    red: 'bg-tag-red-bg text-tag-red',
    pink: 'bg-tag-pink-bg text-tag-pink',
    purple: 'bg-tag-purple-bg text-tag-purple',
};

/** Só o fundo cheio, para a bolinha do seletor e dos selects. */
const TAG_DOT_CLASSES: Record<TagColor, string> = {
    gray: 'bg-tag-gray',
    blue: 'bg-tag-blue',
    sky: 'bg-tag-sky',
    green: 'bg-tag-green',
    lime: 'bg-tag-lime',
    yellow: 'bg-tag-yellow',
    orange: 'bg-tag-orange',
    red: 'bg-tag-red',
    pink: 'bg-tag-pink',
    purple: 'bg-tag-purple',
};

export const tagClasses = (color: TagColor | null | undefined) => TAG_CLASSES[color ?? 'gray'];
export const tagDotClasses = (color: TagColor | null | undefined) => TAG_DOT_CLASSES[color ?? 'gray'];
