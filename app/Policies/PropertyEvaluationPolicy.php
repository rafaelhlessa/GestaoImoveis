<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Property;
use App\Models\PropertyEvaluation;
use App\Models\PropertyUser;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PropertyEvaluationPolicy
{
    use HandlesAuthorization;

    /**
     * Verificar se o usuário pode acessar a propriedade
     */
    private function canAccessProperty(Property $property, User $user)
    {
        switch ($user->profile_id) {
            case 1: // Proprietário puro
                return PropertyUser::where('property_id', $property->id)
                    ->where('user_id', $user->id)
                    ->exists();

            case 2: // Prestador de serviço
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

            case 3: // Proprietário/Prestador
                $isOwner = PropertyUser::where('property_id', $property->id)
                    ->where('user_id', $user->id)
                    ->exists();

                if ($isOwner) return true;

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

            default:
                return false;
        }
    }

    /**
     * Verificar se o usuário pode criar avaliações
     */
    private function canCreateEvaluationForProperty(Property $property, User $user)
    {
        // Carregar o usuário com atividade se necessário
        if (!$user->activity) {
            $user = User::with('activity')->find($user->id);
        }

        switch ($user->profile_id) {
            case 1: // Proprietário puro - NUNCA pode avaliar
                return false;

            case 2: // Prestador de serviço
                $hasAuthorization = DB::table('authorizations')
                    ->where('service_provider_id', $user->id)
                    ->where('evaluation_permission', 1)
                    ->whereExists(function ($query) use ($property) {
                        $query->select(DB::raw(1))
                            ->from('property_user')
                            ->whereColumn('property_user.user_id', 'authorizations.owner_id')
                            ->where('property_user.property_id', $property->id);
                    })
                    ->exists();

                return $hasAuthorization && 
                       $user->activity && 
                       (bool) $user->activity->evaluation_permission;

            case 3: // Proprietário/Prestador
                $isOwner = PropertyUser::where('property_id', $property->id)
                    ->where('user_id', $user->id)
                    ->exists();

                if ($isOwner) {
                    return $user->activity && (bool) $user->activity->evaluation_permission;
                }

                // Se não é proprietário, verifica como prestador
                $hasAuthorization = DB::table('authorizations')
                    ->where('service_provider_id', $user->id)
                    ->where('evaluation_permission', 1)
                    ->whereExists(function ($query) use ($property) {
                        $query->select(DB::raw(1))
                            ->from('property_user')
                            ->whereColumn('property_user.user_id', 'authorizations.owner_id')
                            ->where('property_user.property_id', $property->id);
                    })
                    ->exists();

                return $hasAuthorization && 
                       $user->activity && 
                       (bool) $user->activity->evaluation_permission;

            default:
                return false;
        }
    }

    public function before(User $user, $ability)
    {
        // Admin super user pode tudo (se você tiver)
        if (isset($user->is_admin) && $user->is_admin) {
            return true;
        }
    }

    public function viewAny(User $user)
    {
        // Qualquer usuário autenticado pode ver listas (a filtragem é feita no controller)
        return true;
    }

    public function view(User $user, PropertyEvaluation $propertyEvaluation)
    {
        // Verificar se pode acessar a propriedade da avaliação
        $property = $propertyEvaluation->property;
        return $this->canAccessProperty($property, $user);
    }

    public function create(User $user)
    {
        // Verificação genérica - permitir que prestadores possam criar
        // A verificação específica da propriedade é feita no controller
        return in_array($user->profile_id, [2, 3]); // Prestadores e Proprietário/Prestadores
    }

    public function update(User $user, PropertyEvaluation $propertyEvaluation)
    {
        // Apenas quem criou a avaliação pode editar
        if ($user->id === $propertyEvaluation->user_id) {
            return true;
        }

        // Ou proprietários da propriedade
        $property = $propertyEvaluation->property;
        return PropertyUser::where('property_id', $property->id)
            ->where('user_id', $user->id)
            ->exists();
    }

    public function delete(User $user, PropertyEvaluation $propertyEvaluation)
    {
        // Apenas quem criou a avaliação pode deletar
        if ($user->id === $propertyEvaluation->user_id) {
            return true;
        }

        // Ou proprietários da propriedade
        $property = $propertyEvaluation->property;
        return PropertyUser::where('property_id', $property->id)
            ->where('user_id', $user->id)
            ->exists();
    }

    /**
     * Policy específica para verificar se pode avaliar uma propriedade específica
     */
    public function evaluateProperty(User $user, Property $property)
    {
        Log::info('PropertyEvaluationPolicy@evaluateProperty', [
            'user_id' => $user->id,
            'property_id' => $property->id,
            'user_profile' => $user->profile_id
        ]);

        return $this->canCreateEvaluationForProperty($property, $user);
    }

    /**
     * Policy para verificar acesso à propriedade
     */
    public function accessProperty(User $user, Property $property)
    {
        return $this->canAccessProperty($property, $user);
    }
}