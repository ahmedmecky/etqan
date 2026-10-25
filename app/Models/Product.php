<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

/** صنف من قائمة أصناف المخزن (تُرفع من Excel) */
class Product extends Model
{
    use BelongsToTenant;

    protected $fillable = ['tenant_id', 'name', 'price', 'location'];

    protected function casts(): array
    {
        return ['price' => 'decimal:2'];
    }
}