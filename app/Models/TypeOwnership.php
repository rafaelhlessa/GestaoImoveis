<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class TypeOwnership extends Model
{
    protected $fillable = ['name'];

    protected $table = 'type_ownership';

    // public function users()
    // {
    //     return $this->belongsTo(User::class, 'user_id');
    //     // return $this->belongsToMany(User::class, 'property_user', 'type_ownership_id', 'user_id');
    // }

    public function users(): HasManyThrough
{
    return $this->hasManyThrough(
        User::class,
        PropertyUser::class,
        'type_ownership_id', // Chave estrangeira em property_user referenciando type_ownership
        'id', // Chave primária em users
        'id', // Chave primária em type_ownership
        'user_id' // Chave estrangeira em property_user referenciando User
    );
}
    
}
