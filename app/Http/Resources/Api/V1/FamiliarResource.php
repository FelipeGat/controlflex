<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class FamiliarResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'       => $this->id,
            'nome'     => $this->nome,
            'foto_url' => $this->foto ? Storage::disk('public')->url($this->foto) : null,
        ];
    }
}
