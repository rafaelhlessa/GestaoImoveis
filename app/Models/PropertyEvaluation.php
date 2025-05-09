<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PropertyEvaluation extends Model
{
    use HasFactory;

    protected $table = 'property_evaluations';

    protected $fillable = [
        'property_id',
        'user_id',
        'score',
        'comments',
    ];

    protected $casts = [
        'score'      => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

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

    /**
     * Itens (notas por critério) desta avaliação.
     */
    public function items()
    {
        return $this->hasMany(EvaluationItem::class, 'evaluation_id');
    }
}
