<?php
namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

trait BelongsToTenant
{
    public static function bootBelongsToTenant(): void
    {
        static::addGlobalScope('tenant', function (Builder $builder) {
            if (Auth::check() && Auth::user()->tenant_id) {
                $builder->where(
                    (new static)->getTable() . '.tenant_id',
                    Auth::user()->tenant_id
                );
            }
        });

        static::creating(function ($model) {
            if (Auth::check() && !$model->tenant_id) {
                $model->tenant_id = Auth::user()->tenant_id;
            }
        });
    }
}
