<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

/** وظيفة في الهيكل الوظيفي لمخزن */
class TenantJob extends Model
{
    use BelongsToTenant;

    public const MODES = [
        'staffed' => 'لها موظف مخصص',
        'covered' => 'بدون موظف · تُوزع مهامها',
        'na'      => 'النشاط غير موجود في المخزن',
    ];

    protected $fillable = ['tenant_id', 'job_code', 'mode', 'planned', 'blanks', 'reassign', 'note', 'updated_by'];

    protected function casts(): array
    {
        return ['blanks' => 'array', 'reassign' => 'array', 'planned' => 'integer'];
    }
}