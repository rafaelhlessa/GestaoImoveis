<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PropertyVeterinaryDeclarationBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'property_id',
        'created_by',
        'note',
    ];

    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    public function entries()
    {
        return $this->hasMany(PropertyVeterinaryDeclaration::class, 'batch_id');
    }
}
