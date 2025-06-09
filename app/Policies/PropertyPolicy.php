<?php

namespace App\Policies;

use App\Models\Property;
use App\Models\User;
use App\Models\PropertyUser;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PropertyPolicy
{
    /**
     * ✅ CORREÇÃO PRINCIPAL: Update policy com debug completo
     */
    public function update(User $user, Property $property): bool
    {
        Log::info('=== PropertyPolicy::update - INÍCIO ===', [
            'user_id' => $user->id,
            'user_name' => $user->name,
            'user_profile' => $user->profile_id,
            'property_id' => $property->id,
            'property_nickname' => $property->nickname ?? 'N/A',
            'property_owner_id' => $property->owner_id
        ]);

        // ✅ CORREÇÃO: Para perfil 1 (proprietário), sempre permitir se for dono
        if ($user->profile_id === 1) {
            
            // Primeiro verifica se é o owner direto da propriedade
            $isDirectOwner = $property->owner_id == $user->id;
            Log::info('Profile 1 - Verificação owner direto:', [
                'is_direct_owner' => $isDirectOwner,
                'property_owner_id' => $property->owner_id,
                'user_id' => $user->id
            ]);

            if ($isDirectOwner) {
                Log::info('Profile 1 - AUTORIZADO por ownership direto');
                return true;
            }

            // Verifica na tabela property_user
            $propertyUserExists = PropertyUser::where('property_id', $property->id)
            ->where('user_id', $user->id)
            ->exists();
            
            Log::info('Profile 1 - Verificação property_user:', [
                'exists' => $propertyUserExists,
                'query_sql' => PropertyUser::where('property_id', $property->id)
                    ->where('user_id', $user->id)->toSql(),
                'bindings' => [$property->id, $user->id]
            ]);

            if ($propertyUserExists) {
                Log::info('Profile 1 - AUTORIZADO por property_user');
                return true;
            }

            // Se chegou aqui, não tem permissão
            Log::warning('Profile 1 - NEGADO - Não é proprietário da propriedade');
            return false;
        }

        // Para perfil 2 (prestador)
        if ($user->profile_id === 2) {
            $canEdit = DB::table('authorizations')
                ->where('service_provider_id', $user->id)
                ->where('can_create_properties', 1)
                ->whereExists(function ($query) use ($property) {
                    $query->select(DB::raw(1))
                        ->from('property_user')
                        ->whereColumn('property_user.user_id', 'authorizations.owner_id')
                        ->where('property_user.property_id', $property->id);
                })
                ->exists();

            Log::info('Profile 2 - Resultado:', ['can_edit' => $canEdit]);
            return $canEdit;
        }

        // Para perfil 3 (proprietário/prestador)
        if ($user->profile_id === 3) {
            // Primeiro verifica se é proprietário direto
            $isDirectOwner = $property->owner_id == $user->id;
            if ($isDirectOwner) {
                Log::info('Profile 3 - AUTORIZADO por ownership direto');
                return true;
            }

            // Verifica na property_user
            $isOwner = PropertyUser::where('property_id', $property->id)
                ->where('user_id', $user->id)
                ->exists();

            if ($isOwner) {
                Log::info('Profile 3 - AUTORIZADO por property_user');
                return true;
            }

            // Se não é proprietário, verifica como prestador
            $canEditAsProvider = DB::table('authorizations')
                ->where('service_provider_id', $user->id)
                ->where('can_create_properties', 1)
                ->whereExists(function ($query) use ($property) {
                    $query->select(DB::raw(1))
                        ->from('property_user')
                        ->whereColumn('property_user.user_id', 'authorizations.owner_id')
                        ->where('property_user.property_id', $property->id);
                })
                ->exists();

            Log::info('Profile 3 - Resultado como prestador:', ['can_edit_as_provider' => $canEditAsProvider]);
            return $canEditAsProvider;
        }

        Log::warning('PropertyPolicy::update - Profile desconhecido ou não autorizado:', [
            'profile' => $user->profile_id
        ]);
        return false;
    }

    /**
     * ✅ Outros métodos simplificados
     */
    public function viewAny(User $user): bool
    {
        return in_array($user->profile_id, [1, 3]);
    }

    public function view(User $user, Property $property): bool
    {
        return $this->update($user, $property); // Mesma lógica
    }

    public function create(User $user): bool
    {
        return in_array($user->profile_id, [1, 2, 3]);
    }

    public function delete(User $user, Property $property): bool
    {
        if (!in_array($user->profile_id, [1, 3])) {
            return false;
        }

        return $property->owner_id == $user->id || 
               PropertyUser::where('property_id', $property->id)
                   ->where('user_id', $user->id)
                   ->exists();
    }

    public function restore(User $user, Property $property): bool
    {
        return $this->delete($user, $property);
    }

    public function forceDelete(User $user, Property $property): bool
    {
        return $this->delete($user, $property);
    }
}