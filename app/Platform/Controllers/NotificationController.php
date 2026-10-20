<?php

namespace App\Platform\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Marcar aviso como lido, pelo sino (F3 §07). O aviso é procurado só entre os da pessoa logada:
 * de outra pessoa é 404, sem dizer se o id existe (inegociável 8). Ação sobre a própria conta
 * (lista do teste A5). A resposta volta à tela, e o sino relê `notifications`.
 */
class NotificationController extends Controller
{
    public function read(Request $request, string $notification): RedirectResponse
    {
        $request->user()->notifications()->whereKey($notification)->firstOrFail()->markAsRead();

        return back();
    }

    public function readAll(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return back();
    }
}
