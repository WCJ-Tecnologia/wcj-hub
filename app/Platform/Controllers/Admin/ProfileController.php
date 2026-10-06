<?php

namespace App\Platform\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Platform\Services\SessionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function __construct(private readonly SessionService $sessions) {}

    public function edit(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('admin/profile/index', [
            'profile' => [
                'name' => $user->name,
                'email' => $user->email,
                'role_label' => $user->type()->label(),
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (! $request->filled('password')) {
            $request->merge([
                'password' => null,
                'password_confirmation' => null,
            ]);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'password' => ['nullable', 'confirmed', PasswordRule::defaults()],
        ]);

        $user->fill([
            'name' => $data['name'],
        ]);

        $passwordChanged = ! empty($data['password']);

        if ($passwordChanged) {
            $user->password = $data['password'];
        }

        $user->save();

        if ($passwordChanged) {
            $this->sessions->terminateOthers($user, $request->session()->getId());
        }

        return back()->with('success', 'Pronto, '.$user->firstName().'. Seu perfil está atualizado.');
    }
}
