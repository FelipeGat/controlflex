<?php
namespace App\Policies;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class BancoPolicy
{
    public function update(User $user, Model $model): bool
    {
        return $user->tenant_id === $model->tenant_id && $user->temPermissao('bancos', 'editar');
    }
    public function delete(User $user, Model $model): bool
    {
        return $user->tenant_id === $model->tenant_id && $user->temPermissao('bancos', 'excluir');
    }
}
