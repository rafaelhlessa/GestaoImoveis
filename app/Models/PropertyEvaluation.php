<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\BelongsToProprietario;

class PropertyEvaluation extends Model
{
    use HasFactory;

    protected $table = 'property_evaluations';

    protected $fillable = [
        'property_id',
        'user_id',
        'appraiser',
        'valuation',
        'comments',
    ];

    protected $casts = [
        'valuation'      => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    use BelongsToProprietario;

    protected static function booted()
    {
        static::bootBelongsToProprietario();
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * A propriedade avaliada.
     */
    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    /**
     * Usuário que realizou a avaliação.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
