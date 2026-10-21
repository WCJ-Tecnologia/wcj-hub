<?php

namespace App\Platform\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Platform\Models\User;
use App\Platform\Requests\Admin\UpdateProfileRequest;
use App\Platform\Services\SessionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function __construct(private readonly SessionService $sessions) {}

    public function edit(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('admin/profile/index', self::pageProps($user));
    }

    /**
     * As props da tela, também usadas pela resposta 429 do limitador "senha-atual".
     *
     * @return array<string, mixed>
     */
    public static function pageProps(User $user): array
    {
        return [
            'profile' => [
                'name' => $user->name,
                'email' => $user->email,
                'role_label' => $user->type()->label(),
            ],
        ];
    }

    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validated();

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
