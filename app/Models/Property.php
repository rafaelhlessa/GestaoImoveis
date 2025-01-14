<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Property extends Model
{
    protected $fillable = ['is_active', 'title_deed', 'title_deed_number', 'other', 'area', 'unit', 'type_property', 'city', 'city_id', 'district', 'locality', 'nickname', 'about', 'file_photo'];

    // protected $casts = [
    //     'area' => 'float',
    //     'city_id' => 'integer',
    // ];

    public function owners(): BelongsToMany
    {
        // return $this->belongsToMany(User::class, 'property_user', 'property_id', 'user_id')
        //             ->withPivot('type_ownership_id', 'percentage', 'other')
        //             ->withTimestamps();
        return $this->belongsToMany(User::class, 'property_user', 'property_id', 'user_id')
            ->withPivot('type_ownership_id', 'percentage', 'other')
            ->withTimestamps();
            // ->with('typeOwnership'); // 🚀 Carrega o relacionamento sem precisar de join()
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
}
