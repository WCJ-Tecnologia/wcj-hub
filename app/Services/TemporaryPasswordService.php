<?php

namespace App\Services;

use App\Mail\TemporaryPasswordMail;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class TemporaryPasswordService
{
    public function generate(): string
    {
        $letters = strtoupper(Str::random(4));
        $digits = (string) random_int(10, 99);
        $tail = Str::lower(Str::random(3));

        return $letters.$digits.$tail;
    }

    public function issue(User $user, bool $isReset = false): string
    {
        $plain = $this->generate();

        $user->forceFill([
            'password' => $plain,
            'must_change_password' => true,
        ])->save();

        if ($user->email) {
            Mail::to($user->email)->send(new TemporaryPasswordMail($user, $plain, $isReset));
        }

        return $plain;
    }
}
