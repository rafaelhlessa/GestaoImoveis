<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PropertyVeterinaryDeclaration extends Model
{
    use HasFactory;

    protected $fillable = [
        'property_id',
        'batch_id',
        'species',
        'age_band',
        'qty_males',
        'qty_females',
        'created_by',
    ];

    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    public function batch()
    {
        return $this->belongsTo(PropertyVeterinaryDeclarationBatch::class, 'batch_id');
    }
}
