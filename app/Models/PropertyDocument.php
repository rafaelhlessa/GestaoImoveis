<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\BelongsToProprietario;

class PropertyDocument extends Model
{
    protected $table = 'property_document';

    protected $fillable = [
        'name', 'date', 'show', 'file', 'file_name', 'property_id'
    ];

    use BelongsToProprietario;

    protected static function booted()
    {
        // static::bootBelongsToProprietario();
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class, 'property_id');
    }

}
