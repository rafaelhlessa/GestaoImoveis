<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PropertyEvaluationMedia extends Model
{
    use HasFactory;

    protected $fillable = [
        'evaluation_id', 'path', 'mime', 'original_name', 'size'
    ];

    public function evaluation()
    {
        return $this->belongsTo(PropertyEvaluation::class, 'evaluation_id');
    }
}
