<?php

namespace App\Policies;

use App\Models\User;
use App\Models\PropertyDocument;
use App\Models\Authorization;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Support\Facades\DB;

class PropertyDocumentPolicy
{
    use HandlesAuthorization;

    public function before(User $user, $ability)
    {
        if ($user->profile_id === User::PROFILE_ADMIN) {
            return true;
        }
    }

    public function viewAny(User $user)
    {
        return in_array($user->profile_id, [
            User::PROFILE_ADMIN,
            User::PROFILE_MANAGER,
            User::PROFILE_VIEWER,
        ]);
    }

    public function view(User $user, PropertyDocument $propertyDocument)
    {
        // Busca a propriedade relacionada ao documento
        $property = $propertyDocument->property;
        
        if (!$property) {
            return false;
        }

        // Verifica se é o dono da propriedade
        if ($user->id === $property->owner_id) {
            return true;
        }

        // Verifica se é proprietário da propriedade através da tabela property_user
        $isOwner = DB::table('property_user')
            ->where('property_id', $property->id)
            ->where('user_id', $user->id)
            ->exists();

        if ($isOwner) {
            return true;
        }

        // Verifica autorização para prestadores de serviço
        return DB::table('authorizations')
            ->where('service_provider_id', $user->id)
            ->where('can_view_documents', 1)
            ->whereExists(function ($query) use ($property) {
                $query->select(DB::raw(1))
                    ->from('property_user')
                    ->whereColumn('property_user.user_id', 'authorizations.owner_id')
                    ->where('property_user.property_id', $property->id);
            })
            ->exists();
    }

    public function create(User $user)
    {
        return in_array($user->profile_id, [
            User::PROFILE_ADMIN,
            User::PROFILE_MANAGER,
        ]);
    }

    public function update(User $user, PropertyDocument $propertyDocument)
    {
        // Busca a propriedade relacionada ao documento
        $property = $propertyDocument->property;
        
        if (!$property) {
            return false;
        }

        // Verifica se é o dono da propriedade
        if ($user->id === $property->owner_id) {
            return true;
        }

        // Verifica se é proprietário da propriedade através da tabela property_user
        $isOwner = DB::table('property_user')
            ->where('property_id', $property->id)
            ->where('user_id', $user->id)
            ->exists();

        if ($isOwner) {
            return true;
        }

        // Verifica autorização para prestadores de serviço com permissão de criar/editar
        return DB::table('authorizations')
            ->where('service_provider_id', $user->id)
            ->where('can_create_properties', 1)
            ->whereExists(function ($query) use ($property) {
                $query->select(DB::raw(1))
                    ->from('property_user')
                    ->whereColumn('property_user.user_id', 'authorizations.owner_id')
                    ->where('property_user.property_id', $property->id);
            })
            ->exists();
    }

    public function delete(User $user, PropertyDocument $propertyDocument)
    {
        // Busca a propriedade relacionada ao documento
        $property = $propertyDocument->property;
        
        if (!$property) {
            return false;
        }

        return $user->id === $property->owner_id;
    }
}