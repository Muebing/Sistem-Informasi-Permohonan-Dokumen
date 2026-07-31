<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    /**
     * Seed roles and permissions.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'projects.view',
            'projects.create',
            'projects.update',
            'projects.delete',
            'projects.submit',
            'projects.export',
            'documents.upload',
            'reviews.view',
            'reviews.create',
            'dashboard.view',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $roles = [
            'applicant' => [
                'projects.view',
                'projects.create',
                'projects.update',
                'projects.delete',
                'projects.submit',
                'documents.upload',
                'reviews.view',
                'dashboard.view',
            ],
            'reviewer' => [
                'projects.view',
                'projects.export',
                'reviews.view',
                'reviews.create',
                'dashboard.view',
            ],
        ];

        foreach ($roles as $name => $rolePermissions) {
            $role = Role::findOrCreate($name, 'web');
            $role->syncPermissions($rolePermissions);
        }
    }
}
