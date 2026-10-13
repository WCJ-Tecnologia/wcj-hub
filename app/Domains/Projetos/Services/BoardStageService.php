<?php

namespace App\Domains\Projetos\Services;

use App\Domains\Projetos\Models\Project;
use App\Domains\Projetos\Models\StageRole;

/**
 * Etapas do quadro (P10–P12; docs/banco.md, board_stages). O quadro padrão mora só aqui: o
 * ProjectService e o ExemploSeeder o criam por este Service, para a ordem não morar em dois
 * lugares (PR #4).
 */
class BoardStageService
{
    /** As 8 etapas com que todo projeto nasce, na ordem da P10, com o papel de cada uma (P11). */
    public const DEFAULT_BOARD = [
        [StageRole::QUEUE, 'Backlog'],
        [StageRole::SPRINT, 'To do'],
        [StageRole::DEVELOPMENT, 'Doing'],
        [StageRole::PAUSED, 'Paused'],
        [StageRole::CODE_REVIEW, 'Code-review'],
        [StageRole::TEST, 'In test'],
        [StageRole::DONE, 'Done'],
        [StageRole::SUPPORT, 'Support'],
    ];

    /**
     * Não grava auditoria: quem cria o projeto audita a criação inteira, com o quadro junto.
     *
     * @return list<string> os nomes das etapas, na ordem
     */
    public function createDefaultBoard(Project $project): array
    {
        foreach (self::DEFAULT_BOARD as $index => [$role, $name]) {
            $project->boardStages()->create([
                'stage_role_id' => $role,
                'name' => $name,
                'position' => $index + 1,
            ]);
        }

        return array_column(self::DEFAULT_BOARD, 1);
    }
}
