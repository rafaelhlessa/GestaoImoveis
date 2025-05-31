<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\BelongsToProprietario;

class PropertyUser extends Model
{

    protected $table = 'property_user';

    protected $fillable = ['property_id', 'user_id', 'type_ownership_id', 'percentage', 'other'];

    use BelongsToProprietario;

    protected static function booted()
    {
        // static::bootBelongsToProprietario();
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function property()
    {
        return $this->belongsTo(Property::class, 'property_id');
    }

    public function typeOwnership()
    {
        return $this->belongsTo(TypeOwnership::class, 'type_ownership_id');
    }

}
