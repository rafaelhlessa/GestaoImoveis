<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PropertyUser extends Model
{

    protected $table = 'property_user';

    protected $fillable = ['property_id', 'user_id', 'type_ownership_id', 'percentage', 'other'];

    // public function properties()
    // {
    //     return $this->belongsToMany(Property::class, 'property_user', 'user_id', 'property_id')
    //                 ->withPivot('percentage') // Caso tenha um campo extra na tabela pivot
    //                 ->with('typeOwnership') // Caso queira trazer o relacionamento de propriedade
    //                 ->withTimestamps();
    // }

    // public function Ownership()
    // {
    //     return $this->belongsTo(TypeOwnership::class, 'type_ownership_id');
    // }

    // public function typeOwnerships()
    // {
    //     return $this->belongsToMany(TypeOwnership::class, 'property_user', 'property_id', 'type_ownership_id')
    //                 ->withPivot('user_id', 'percentage', 'other')
    //                 ->withTimestamps();
    // }

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

    // public function user()
    // {
    //     return $this->belongsTo(User::class, 'user_id');
    // }
}
