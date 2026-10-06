<?php

namespace Database\Seeders;

use App\Platform\Models\AiAssistant;
use Database\Seeders\Concerns\SemeiaDominioFechado;
use Illuminate\Database\Seeder;

/** ai_assistants (IA3, IA4). O gpt é da v2, mas fica na semente para o canal existir. */
class AiAssistantSeeder extends Seeder
{
    use SemeiaDominioFechado;

    public function run(): void
    {
        $this->semear(AiAssistant::class, [
            AiAssistant::CLAUDE => ['slug' => 'claude', 'label' => 'Claude', 'write_allowed' => true],
            AiAssistant::CHATGPT => ['slug' => 'chatgpt', 'label' => 'ChatGPT', 'write_allowed' => false],
            AiAssistant::CODEX => ['slug' => 'codex', 'label' => 'Codex', 'write_allowed' => true],
            AiAssistant::GPT => ['slug' => 'gpt', 'label' => 'GPT "WCJ Hub"', 'write_allowed' => true],
        ]);
    }
}
