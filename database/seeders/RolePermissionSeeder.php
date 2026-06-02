<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'view dashboard',
            'view clients',
            'create clients',
            'update clients',
            'delete clients',
            'view monthly summaries',
            'create monthly summaries',
            'update monthly summaries',
            'delete monthly summaries',
            'view collections',
            'create collections',
            'update collections',
            'delete collections',
            'view risks',
            'create risks',
            'update risks',
            'delete risks',
            'view data dictionary',
            'manage data dictionary',
            'manage users',
            'manage roles',
        ];

        foreach ($permissions as $permission) {
            Permission::query()->firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        $admin = Role::query()->firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $manager = Role::query()->firstOrCreate(['name' => 'manager', 'guard_name' => 'web']);
        $collectionKam = Role::query()->firstOrCreate(['name' => 'collection_kam', 'guard_name' => 'web']);
        $riskAnalyst = Role::query()->firstOrCreate(['name' => 'risk_analyst', 'guard_name' => 'web']);
        $viewer = Role::query()->firstOrCreate(['name' => 'viewer', 'guard_name' => 'web']);

        $admin->syncPermissions($permissions);

        $manager->syncPermissions([
            'view dashboard',
            'view clients',
            'create clients',
            'update clients',
            'view monthly summaries',
            'create monthly summaries',
            'update monthly summaries',
            'view collections',
            'create collections',
            'update collections',
            'view risks',
            'create risks',
            'update risks',
            'view data dictionary',
        ]);

        $collectionKam->syncPermissions([
            'view dashboard',
            'view clients',
            'view monthly summaries',
            'view collections',
            'create collections',
            'update collections',
        ]);

        $riskAnalyst->syncPermissions([
            'view dashboard',
            'view clients',
            'view monthly summaries',
            'view collections',
            'view risks',
            'create risks',
            'update risks',
        ]);

        $viewer->syncPermissions([
            'view dashboard',
            'view clients',
            'view monthly summaries',
            'view collections',
            'view risks',
            'view data dictionary',
        ]);

        User::query()
            ->where('email', 'admin@scomm.test')
            ->first()
            ?->assignRole($admin);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
