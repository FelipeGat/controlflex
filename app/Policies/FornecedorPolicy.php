<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class FornecedorPolicy
{
    public function update(User $user, Model $model): bool
    {
        return $user->id === $model->user_id;
    }

    public function delete(User $user, Model $model): bool
    {
        return $user->id === $model->user_id;
    }
}
