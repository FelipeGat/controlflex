<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        $user = $request->user();
        $tenant = null;
        $licenca = null;

        if ($user->tenant_id) {
            $tenant = $user->tenant()->with('plano')->first();

            if ($tenant) {
                $plano = $tenant->plano;
                $licenca = [
                    'plano_nome'      => $plano?->nome ?? '—',
                    'plano_descricao' => $plano?->descricao,
                    'tipo_cobranca'   => $tenant->tipo_cobranca,
                    'data_inicio'     => $tenant->data_inicio_plano,
                    'data_fim'        => $tenant->data_fim_plano,
                    'dias_restantes'  => $tenant->diasRestantes(),
                    'vencido'         => $tenant->planoVencido(),
                    'max_usuarios'    => $plano?->max_usuarios ?? 0,
                    'max_bancos'      => $plano?->max_bancos ?? 0,
                    'uso_usuarios'    => $tenant->users()->count(),
                    'uso_bancos'      => \App\Models\Banco::where('tenant_id', $tenant->id)->count(),
                ];
            }
        }

        return view('profile.edit', compact('user', 'licenca'));
    }

    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();

        $user->fill($request->safe()->only(['name', 'email']));

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        if ($request->hasFile('foto')) {
            if ($user->foto) {
                Storage::disk('public')->delete($user->foto);
            }
            $user->foto = $request->file('foto')->store('usuarios', 'public');
        }

        $user->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
