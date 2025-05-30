<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\BelongsToProprietario;

class Property extends Model
{
    protected $fillable = ['is_active', 'title_deed', 'title_deed_number', 'other', 'area', 'unit', 'type_property', 'address', 'city', 'city_id', 'district', 'locality', 'nickname', 'about', 'file_photo'];

    use BelongsToProprietario;

    public function owners(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'property_user', 'property_id', 'user_id')
            ->withPivot('type_ownership_id', 'percentage', 'other')
            ->withTimestamps();
    }

    public function typeOwnerships(): BelongsToMany
    {
        return $this->belongsToMany(TypeOwnership::class, 'property_user', 'property_id', 'type_ownership_id')
                    ->withPivot('user_id', 'percentage', 'other')
                    ->withTimestamps();
    }

    public function documents(): HasMany
    {
        return $this->hasMany(PropertyDocument::class, 'property_id');
    }

    public function authorizations()
    {
        return $this->hasManyThrough(
            Authorization::class, // Model final (Authorization)
            PropertyUser::class,  // Model intermediária (pivot)
            'property_id',        // FK na PropertyUser que referencia a Property
            'owner_id',           // FK na Authorization que referencia o User
            'id',                 // PK da Property
            'user_id'             // FK na PropertyUser que referencia o Owner(User)
        );
    }

     protected static function booted()
    {
        static::bootBelongsToProprietario();
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }
}
