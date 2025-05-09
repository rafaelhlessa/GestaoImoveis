<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EvaluationItem extends Model
{
    use HasFactory;

    protected $table = 'evaluation_items';

    protected $fillable = [
        'evaluation_id',
        'criterion_id',
        'note',
        'observation',
    ];

    protected $casts = [
        'note'       => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Avaliação a que este item pertence.
     */
    public function evaluation()
    {
        return $this->belongsTo(PropertyEvaluation::class, 'evaluation_id');
    }

    /**
     * Critério avaliado neste item.
     */
    public function criterion()
    {
        return $this->belongsTo(EvaluationCriterion::class, 'criterion_id');
    }
}
