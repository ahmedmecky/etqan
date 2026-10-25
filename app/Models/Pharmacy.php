<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

/** صيدلية (عميل) من قائمة عملاء المخزن (تُرفع من Excel) */
class Pharmacy extends Model
{
    use BelongsToTenant;

    protected $fillable = ['tenant_id', 'code', 'name', 'address'];
}