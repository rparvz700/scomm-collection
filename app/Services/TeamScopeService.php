<?php

namespace App\Services;

use App\Models\Client;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class TeamScopeService
{
    /**
     * Recursion guard to prevent infinite loops when querying Client internally.
     */
    protected static bool $isApplying = false;

    /**
     * Apply Team-Based RBAC filter to any Client or Client-related query.
     */
    public static function applyTeamScope(Builder $query, ?User $user = null): Builder
    {
        if (static::$isApplying) {
            return $query;
        }

        $user = $user ?? Auth::user();
        if (!$user) {
            return $query;
        }

        // 1. Super Admin / Executive / HOD / Admin Bypass
        if (
            $user->can('view all clients') ||
            $user->hasAnyRole(['admin', 'mgt', 'collection_hod', 'manager', 'Super Admin'])
        ) {
            return $query;
        }

        static::$isApplying = true;

        try {
            $userName = strtolower(trim($user->name));
            $userId = $user->id;

            // Base un-scoped client query for internal lookup
            $unscopedClient = Client::withoutGlobalScope('team_scope');

            // 2. Collection Supervisor Scope Check
            $isCollectionSupervisor = (clone $unscopedClient)
                ->where('collection_supervisor_id', $userId)
                ->orWhereRaw('LOWER(collection_supervisor) = ?', [$userName])
                ->exists();

            if ($isCollectionSupervisor) {
                return $query->where(function (Builder $q) use ($userId, $userName) {
                    $q->where('collection_supervisor_id', $userId)
                      ->orWhereRaw('LOWER(collection_supervisor) = ?', [$userName]);
                });
            }

            // 3. SM Supervisor Scope Check
            $isSmSupervisor = (clone $unscopedClient)
                ->where('sm_supervisor_id', $userId)
                ->orWhereRaw('LOWER(sm_supervisor) = ?', [$userName])
                ->exists();

            if ($isSmSupervisor) {
                return $query->where(function (Builder $q) use ($userId, $userName) {
                    $q->where('sm_supervisor_id', $userId)
                      ->orWhereRaw('LOWER(sm_supervisor) = ?', [$userName]);
                });
            }

            // 4. Collection KAM / Teammate Scope Check
            $collTeamName = (clone $unscopedClient)
                ->where('collection_kam_id', $userId)
                ->orWhereRaw('LOWER(collection_kam) = ?', [$userName])
                ->value('collection_team_name');

            $collSupervisorId = (clone $unscopedClient)
                ->where('collection_kam_id', $userId)
                ->orWhereRaw('LOWER(collection_kam) = ?', [$userName])
                ->value('collection_supervisor_id');

            $collSupervisorName = (clone $unscopedClient)
                ->where('collection_kam_id', $userId)
                ->orWhereRaw('LOWER(collection_kam) = ?', [$userName])
                ->value('collection_supervisor');

            // 5. SM KAM / Teammate Scope Check
            $smTeamName = (clone $unscopedClient)
                ->where('sm_kam_id', $userId)
                ->orWhereRaw('LOWER(sm_kam) = ?', [$userName])
                ->value('team_name');

            $smSupervisorId = (clone $unscopedClient)
                ->where('sm_kam_id', $userId)
                ->orWhereRaw('LOWER(sm_kam) = ?', [$userName])
                ->value('sm_supervisor_id');

            return $query->where(function (Builder $q) use ($userId, $userName, $collTeamName, $collSupervisorId, $collSupervisorName, $smTeamName, $smSupervisorId) {
                if ($collTeamName) {
                    $q->orWhere('collection_team_name', $collTeamName);
                }
                if ($collSupervisorId) {
                    $q->orWhere('collection_supervisor_id', $collSupervisorId);
                }
                if ($collSupervisorName) {
                    $q->orWhereRaw('LOWER(collection_supervisor) = ?', [strtolower(trim($collSupervisorName))]);
                }

                if ($smTeamName) {
                    $q->orWhere('team_name', $smTeamName);
                }
                if ($smSupervisorId) {
                    $q->orWhere('sm_supervisor_id', $smSupervisorId);
                }

                // Direct fallback matches
                $q->orWhere('collection_kam_id', $userId)
                  ->orWhereRaw('LOWER(collection_kam) = ?', [$userName])
                  ->orWhere('sm_kam_id', $userId)
                  ->orWhereRaw('LOWER(sm_kam) = ?', [$userName]);
            });
        } finally {
            static::$isApplying = false;
        }
    }
}
