<?php
namespace App\Policies;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class FornecedorPolicy
{
    public function update(User $user, Model $model): bool
    {
        return $user->tenant_id === $model->tenant_id && $user->temPermissao('fornecedores', 'editar');
    }
    public function delete(User $user, Model $model): bool
    {
        return $user->tenant_id === $model->tenant_id && $user->temPermissao('fornecedores', 'excluir');
    }
}
