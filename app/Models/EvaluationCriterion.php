<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EvaluationCriterion extends Model
{
    use HasFactory;

    protected $table = 'evaluation_criteria';

    protected $fillable = [
        'name',
        'weight',
    ];

    /**
     * Itens de avaliação deste critério.
     */
    public function evaluationItems()
    {
        return $this->hasMany(EvaluationItem::class, 'criterion_id');
    }
}
