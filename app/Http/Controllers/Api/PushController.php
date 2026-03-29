<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PushSubscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PushController extends Controller
{
    /**
     * POST /api/push/subscribe
     * Body: { endpoint, keys: { p256dh, auth } }
     */
    public function subscribe(Request $request)
    {
        $request->validate([
            'endpoint'      => 'required|string',
            'keys.p256dh'   => 'required|string',
            'keys.auth'     => 'required|string',
        ]);

        PushSubscription::updateOrCreate(
            [
                'user_id'  => Auth::id(),
                'endpoint' => $request->input('endpoint'),
            ],
            [
                'tenant_id' => Auth::user()->tenant_id,
                'p256dh'    => $request->input('keys.p256dh'),
                'auth'      => $request->input('keys.auth'),
            ]
        );

        return response()->json(['ok' => true]);
    }

    /**
     * POST /api/push/unsubscribe
     * Body: { endpoint }
     */
    public function unsubscribe(Request $request)
    {
        $request->validate(['endpoint' => 'required|string']);

        PushSubscription::where('user_id', Auth::id())
            ->where('endpoint', $request->input('endpoint'))
            ->delete();

        return response()->json(['ok' => true]);
    }
}
