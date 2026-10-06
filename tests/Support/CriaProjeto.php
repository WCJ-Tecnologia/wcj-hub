<?php

namespace Tests\Support;

use App\Domains\Projetos\Models\Project;
use App\Platform\Models\Client;
use App\Platform\Models\User;

/**
 * Projeto mínimo para os testes do Núcleo que dependem de "gestor do cliente" (P1, P5) ou de
 * projeto aberto (N11). O B2 ainda não tem Service de projeto: grava direto na tabela.
 */
trait CriaProjeto
{
    private int $prefixosDeProjeto = 0;

    /** @param  list<User>  $members */
    private function project(Client $client, User $manager, array $members = [], mixed $finishedAt = null): Project
    {
        $project = Project::query()->create([
            'client_id' => $client->id,
            'manager_id' => $manager->id,
            'key_prefix' => 'P'.++$this->prefixosDeProjeto,
            'name' => 'Projeto '.$this->prefixosDeProjeto,
            'starts_on' => now('America/Sao_Paulo')->toDateString(),
            'finished_at' => $finishedAt,
        ]);
        $project->users()->attach(array_map(fn (User $user) => $user->id, $members));

        return $project;
    }
}
