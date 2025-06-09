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

    // NOVO: Relacionamento com PropertyEvaluation
    public function evaluations(): HasMany
    {
        return $this->hasMany(PropertyEvaluation::class, 'property_id');
    }

    // Relacionamento para obter apenas avaliações recentes
    public function recentEvaluations(): HasMany
    {
        return $this->hasMany(PropertyEvaluation::class, 'property_id')
                    ->orderBy('created_at', 'desc');
    }

    // Relacionamento para obter a última avaliação
    public function latestEvaluation()
    {
        return $this->hasOne(PropertyEvaluation::class, 'property_id')
                    ->latestOfMany();
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
        // static::bootBelongsToProprietario();
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    // Métodos auxiliares para avaliações
    public function getAverageValuationAttribute()
    {
        return $this->evaluations()->avg('valuation');
    }

    public function getEvaluationsCountAttribute()
    {
        return $this->evaluations()->count();
    }

    public function getHighestValuationAttribute()
    {
        return $this->evaluations()->max('valuation');
    }

    public function getLowestValuationAttribute()
    {
        return $this->evaluations()->min('valuation');
    }
}