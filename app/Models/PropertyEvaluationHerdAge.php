<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PropertyEvaluationHerdAge extends Model
{
    use HasFactory;

    protected $fillable = [
        'herd_id',
        'faixa_etaria',
        'quantidade_machos',
        'quantidade_femeas',
    ];

    public function herd()
    {
        return $this->belongsTo(PropertyEvaluationHerd::class, 'herd_id');
    }
}
