<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Familiar;
use App\Models\Plano;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\CategoriasDefaultSeeder;
use Database\Seeders\FornecedoresDefaultSeeder;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = DB::transaction(function () use ($request) {
            $planoDefault = Plano::where('slug', 'individual')->first();

            $tenant = Tenant::create([
                'nome'     => $request->name,
                'ativo'    => true,
                'status'   => 'ativo',
                'plano_id' => $planoDefault?->id,
            ]);

            $user = User::create([
                'name'      => $request->name,
                'email'     => $request->email,
                'password'  => Hash::make($request->password),
                'tenant_id' => $tenant->id,
                'role'      => 'master',
                'ativo'     => true,
            ]);

            $familiar = Familiar::create([
                'tenant_id'     => $tenant->id,
                'user_id'       => $user->id,
                'nome'          => $request->name,
                'salario'       => 0,
                'limite_cartao' => 0,
                'limite_cheque' => 0,
            ]);

            $user->update(['familiar_id' => $familiar->id]);

            CategoriasDefaultSeeder::seedParaTenant($tenant->id, $user->id);
            FornecedoresDefaultSeeder::seedParaTenant($tenant->id, $user->id);

            return $user;
        });

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }
}
