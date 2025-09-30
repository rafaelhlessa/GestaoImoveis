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
        if ($user->hasProfile('proprietario')) {
            return PropertyUser::where('property_id', $property->id)
                ->where('user_id', $user->id)
                ->exists();
        }
        if ($user->hasProfile('prestador')) {
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
        return false;
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

        if ($user->hasProfile('proprietario')) {
            $isOwner = PropertyUser::where('property_id', $property->id)
                ->where('user_id', $user->id)
                ->exists();
            if ($isOwner) {
                return $user->activity && (bool) $user->activity->evaluation_permission;
            }
        }
        if ($user->hasProfile('prestador')) {
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
        }
        return false;
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
    // Permitir que prestadores possam criar
    // A verificação específica da propriedade é feita no controller
    return $user->hasProfile('prestador');
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
            'user_profiles' => $user->profiles->pluck('slug')->toArray()
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
