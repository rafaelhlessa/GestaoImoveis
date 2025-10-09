<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PropertyEvaluationHerd extends Model
{
    use HasFactory;

    protected $fillable = [
        'evaluation_id',
        'especie',
        'raca',
    ];

    public function evaluation()
    {
        return $this->belongsTo(PropertyEvaluation::class, 'evaluation_id');
    }

    public function ages()
    {
        return $this->hasMany(PropertyEvaluationHerdAge::class, 'herd_id');
    }
}
