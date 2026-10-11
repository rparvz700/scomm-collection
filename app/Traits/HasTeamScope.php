<?php

namespace App\Traits;

use App\Services\TeamScopeService;
use Illuminate\Database\Eloquent\Builder;

trait HasTeamScope
{
    public static function bootHasTeamScope(): void
    {
        static::addGlobalScope('team_scope', function (Builder $builder) {
            TeamScopeService::applyTeamScope($builder);
        });
    }
}
