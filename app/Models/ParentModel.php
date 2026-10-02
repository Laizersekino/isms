<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ParentModel extends Model
{
    protected $table = 'parents';

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(
            Student::class,
            'student_parents',
            'parent_id',
            'student_id'
        )->withPivot('relationship', 'is_primary');
    }
}
