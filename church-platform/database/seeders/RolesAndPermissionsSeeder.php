<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // ------------------------------------------------------------------
        // Permissions
        // ------------------------------------------------------------------
        $permissions = [
            // Users
            'users.view', 'users.create', 'users.edit', 'users.delete',
            // Posts
            'posts.view', 'posts.create', 'posts.edit', 'posts.delete', 'posts.moderate',
            // Church Pages
            'churches.view', 'churches.create', 'churches.edit', 'churches.delete',
            // Communities
            'communities.view', 'communities.create', 'communities.edit', 'communities.delete',
            // Events
            'events.view', 'events.create', 'events.edit', 'events.delete',
            // Bible Studies
            'bible_studies.view', 'bible_studies.create', 'bible_studies.edit',
            // Prayer
            'prayer.view', 'prayer.moderate', 'prayer.respond_privately',
            // Counselling
            'counselling.private_chat', 'counselling.view_all_prayer',
            // Settings
            'settings.view', 'settings.edit',
            // Plugins
            'plugins.manage',
            // Themes
            'themes.manage',
            // Analytics
            'analytics.view',
            // Notifications
            'notifications.broadcast',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm]);
        }

        // ------------------------------------------------------------------
        // Roles
        // ------------------------------------------------------------------

        // Super Admin — all permissions
        $superAdmin = Role::firstOrCreate(['name' => 'super_admin']);
        $superAdmin->syncPermissions(Permission::all());

        // Church Admin — manage own church + community
        $churchAdmin = Role::firstOrCreate(['name' => 'church_admin']);
        $churchAdmin->syncPermissions([
            'users.view',
            'posts.view', 'posts.create', 'posts.edit', 'posts.delete', 'posts.moderate',
            'churches.view', 'churches.create', 'churches.edit',
            'communities.view', 'communities.create', 'communities.edit',
            'events.view', 'events.create', 'events.edit', 'events.delete',
            'bible_studies.view', 'bible_studies.create', 'bible_studies.edit',
            'prayer.view', 'prayer.moderate',
            'analytics.view',
            'notifications.broadcast',
        ]);

        // Counsellor — pastoral care, private prayer responses
        $counsellor = Role::firstOrCreate(['name' => 'counsellor']);
        $counsellor->syncPermissions([
            'posts.view', 'posts.create',
            'prayer.view', 'prayer.respond_privately', 'prayer.moderate',
            'counselling.private_chat', 'counselling.view_all_prayer',
            'events.view',
            'bible_studies.view',
        ]);

        // Musician — events and worship content
        $musician = Role::firstOrCreate(['name' => 'musician']);
        $musician->syncPermissions([
            'posts.view', 'posts.create',
            'events.view', 'events.create', 'events.edit',
            'bible_studies.view', 'bible_studies.create',
            'prayer.view',
        ]);

        // General User — standard social features
        $generalUser = Role::firstOrCreate(['name' => 'general_user']);
        $generalUser->syncPermissions([
            'posts.view', 'posts.create',
            'churches.view',
            'communities.view',
            'events.view',
            'bible_studies.view',
            'prayer.view',
        ]);
    }
}
