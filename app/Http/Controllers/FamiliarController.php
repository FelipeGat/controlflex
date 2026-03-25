<?php

namespace App\Http\Controllers;

use App\Models\Familiar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class FamiliarController extends Controller
{
    public function index()
    {
        $familiares = Familiar::where('user_id', Auth::id())->orderBy('nome')->get();
        return view('familiares.index', compact('familiares'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nome' => 'required|string|max:100',
            'salario' => 'nullable|numeric|min:0',
            'limite_cartao' => 'nullable|numeric|min:0',
            'limite_cheque' => 'nullable|numeric|min:0',
            'foto' => 'nullable|image|mimes:jpg,jpeg,png,gif|max:2048',
        ]);

        $fotoPath = null;
        if ($request->hasFile('foto')) {
            $fotoPath = $request->file('foto')->store('familiares', 'public');
        }

        Familiar::create([
            'user_id' => Auth::id(),
            'nome' => $request->nome,
            'salario' => $request->salario ?? 0,
            'limite_cartao' => $request->limite_cartao ?? 0,
            'limite_cheque' => $request->limite_cheque ?? 0,
            'foto' => $fotoPath,
        ]);

        return back()->with('success', 'Familiar adicionado com sucesso!');
    }

    public function update(Request $request, Familiar $familiar)
    {
        $this->authorize('update', $familiar);

        $request->validate([
            'nome' => 'required|string|max:100',
            'salario' => 'nullable|numeric|min:0',
            'limite_cartao' => 'nullable|numeric|min:0',
            'limite_cheque' => 'nullable|numeric|min:0',
            'foto' => 'nullable|image|mimes:jpg,jpeg,png,gif|max:2048',
        ]);

        $data = [
            'nome' => $request->nome,
            'salario' => $request->salario ?? 0,
            'limite_cartao' => $request->limite_cartao ?? 0,
            'limite_cheque' => $request->limite_cheque ?? 0,
        ];

        if ($request->hasFile('foto')) {
            if ($familiar->foto) {
                Storage::disk('public')->delete($familiar->foto);
            }
            $data['foto'] = $request->file('foto')->store('familiares', 'public');
        }

        $familiar->update($data);

        return back()->with('success', 'Familiar atualizado com sucesso!');
    }

    public function destroy(Familiar $familiar)
    {
        $this->authorize('delete', $familiar);

        if ($familiar->foto) {
            Storage::disk('public')->delete($familiar->foto);
        }

        $familiar->delete();
        return back()->with('success', 'Familiar excluído com sucesso!');
    }
}
