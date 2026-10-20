<?php

namespace App\Domains\Projetos\Enums;

/**
 * Cor da etiqueta de tipo e de área (F3 §11 item 7; banco.md seção 7, 14/10): paleta fechada de
 * 10 nomes, espelho de `TagColor` em resources/js/types/quadro.ts e dos tokens em
 * design/tokens.json. Nulo no banco = cinza.
 */
enum TagColorEnum: string
{
    case GRAY = 'gray';
    case BLUE = 'blue';
    case SKY = 'sky';
    case GREEN = 'green';
    case LIME = 'lime';
    case YELLOW = 'yellow';
    case ORANGE = 'orange';
    case RED = 'red';
    case PINK = 'pink';
    case PURPLE = 'purple';
}
