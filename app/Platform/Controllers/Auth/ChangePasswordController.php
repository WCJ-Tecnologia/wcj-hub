<?php

namespace App\Platform\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Platform\Services\SessionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ChangePasswordController extends Controller
{
    public function __construct(private readonly SessionService $sessions) {}

    public function edit(Request $request): Response|RedirectResponse
    {
        $user = $request->user();

        if (! $user?->must_change_password) {
            return redirect()->route('admin.dashboard');
        }

        return Inertia::render('auth/change-password', [
            'firstName' => $user->firstName(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (! $user?->must_change_password) {
            return redirect()->route('admin.dashboard');
        }

        $data = $request->validate([
            'password' => ['required', 'confirmed', PasswordRule::defaults()],
        ]);

        if (Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'password' => 'Escolhe uma senha diferente da temporária — essa é só sua.',
            ]);
        }

        $user->forceFill([
            'password' => $data['password'],
            'must_change_password' => false,
        ])->save();

        $this->sessions->terminateOthers($user, $request->session()->getId());

        return redirect()
            ->intended(route('admin.dashboard'))
            ->with('success', 'Pronto, '.$user->firstName().'. Sua senha está no ponto.');
    }
}
