<?php

namespace Database\Seeders;

use App\Platform\Models\ContactChannel;
use Database\Seeders\Concerns\SemeiaDominioFechado;
use Illuminate\Database\Seeder;

/** contact_channels (P52). */
class ContactChannelSeeder extends Seeder
{
    use SemeiaDominioFechado;

    public function run(): void
    {
        $this->semear(ContactChannel::class, [
            ContactChannel::WHATSAPP => ['slug' => 'whatsapp', 'label' => 'WhatsApp'],
            ContactChannel::PHONE => ['slug' => 'phone', 'label' => 'Telefone'],
            ContactChannel::EMAIL => ['slug' => 'email', 'label' => 'E-mail'],
            ContactChannel::MEETING => ['slug' => 'meeting', 'label' => 'Reunião'],
            ContactChannel::OTHER => ['slug' => 'other', 'label' => 'Outro'],
        ]);
    }
}
