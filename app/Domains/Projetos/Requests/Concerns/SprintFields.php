<?php

namespace App\Domains\Projetos\Requests\Concerns;

/**
 * Os campos da sprint (F5 §02), iguais na criação, na edição, no "Nova sprint" do arrastar e na
 * próxima sprint do encerramento: nome até 80 (único no projeto, o Service diz), objetivo
 * opcional até 500, início e fim em `AAAA-MM-DD`, fim no início ou depois.
 */
trait SprintFields
{
    /**
     * @return array<string, mixed>
     */
    protected function sprintRules(string $prefix = '', bool $required = true): array
    {
        $presence = $required ? 'required' : 'required_with:'.rtrim($prefix, '.');

        return [
            "{$prefix}name" => [$presence, 'string', 'max:80'],
            "{$prefix}goal" => ['nullable', 'string', 'max:500'],
            "{$prefix}starts_on" => [$presence, 'date_format:Y-m-d'],
            "{$prefix}ends_on" => [$presence, 'date_format:Y-m-d', "after_or_equal:{$prefix}starts_on"],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function sprintMessages(string $prefix = ''): array
    {
        return ["{$prefix}ends_on.after_or_equal" => 'O fim não pode ser antes do início'];
    }

    /**
     * @return array<string, string>
     */
    protected function sprintAttributes(string $prefix = ''): array
    {
        return ["{$prefix}name" => 'nome', "{$prefix}goal" => 'objetivo', "{$prefix}starts_on" => 'início', "{$prefix}ends_on" => 'fim'];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{name: string, goal: ?string, starts_on: string, ends_on: string}
     */
    protected function sprintData(array $data): array
    {
        return [
            'name' => trim((string) $data['name']),
            'goal' => isset($data['goal']) ? (string) $data['goal'] : null,
            'starts_on' => (string) $data['starts_on'],
            'ends_on' => (string) $data['ends_on'],
        ];
    }
}
