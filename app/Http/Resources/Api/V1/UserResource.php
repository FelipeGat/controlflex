<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'nome'        => $this->name,
            'email'       => $this->email,
            'foto_url'    => $this->foto ? Storage::disk('public')->url($this->foto) : null,
            'role'        => $this->role,
            'tenant_id'   => $this->tenant_id,
            'familiar_id' => $this->familiar_id,
            'permissoes'  => $this->permissoes ?? [],
            'ativo'       => (bool) $this->ativo,
            'is_master'   => $this->isMaster(),
        ];
    }
}
