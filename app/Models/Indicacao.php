<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Indicacao extends Model
{
    protected $table = 'indicacoes';

    protected $fillable = [
        'cupom_id',
        'tenant_indicado_id',
    ];

    public function cupom()
    {
        return $this->belongsTo(CupomIndicacao::class, 'cupom_id');
    }

    public function tenantIndicado()
    {
        return $this->belongsTo(Tenant::class, 'tenant_indicado_id');
    }
}
