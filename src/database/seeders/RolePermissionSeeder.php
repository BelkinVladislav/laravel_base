<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

/**
 * Seeder для создания ролей и прав доступа в системе
 *
 * Этот seeder:
 * 1. Сбрасывает кэш ролей и прав
 * 2. Создаёт все необходимые права доступа
 * 3. Создаёт роли и назначает им права
 * 4. Создаёт тестовых пользователей для каждой роли
 *
 * Выполнение: php artisan db:seed --class=RolePermissionSeeder
 */
class RolePermissionSeeder extends Seeder
{
    /**
     * Запуск seeder
     *
     * @return void
     */
    public function run(): void
    {
        // Сброс кэша ролей и прав доступа (ВАЖНО!)
        // Это необходимо выполнить в первую очередь
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // ===================================================================
        // ШАГ 1: Создание прав доступа (permissions)
        // ===================================================================

        $this->command->info('Создание прав доступа...');

        // Определяем все права в системе
        $permissions = [
            // Права для дашборда
            'view_dashboard',

            // Права для управления пользователями
            'manage_users',
            'view_users',
            'create_users',
            'edit_users',
            'delete_users',

            // Права для управления ролями и правами
            'manage_roles',
            'manage_permissions',
            'assign_roles',

            // Права для управления контентом
            'create_content',
            'edit_own_content',
            'edit_any_content',
            'delete_own_content',
            'delete_any_content',
            'publish_content',

            // Права для модерации
            'moderate_content',
            'view_reports',
            'handle_reports',

            // Права для аналитики
            'view_analytics',
            'export_data',

            // Системные права
            'system_settings',
            'view_logs',
            'manage_system',
        ];

        // Создаём все права в БД
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        $this->command->info('✓ Прав доступа создано: ' . count($permissions));

        // ===================================================================
        // ШАГ 2: Создание ролей и назначение прав
        // ===================================================================

        $this->command->info('Создание ролей...');

        // 1. СУПЕРАДМИНИСТРАТОР - получает ВСЕ права
        $superAdmin = Role::firstOrCreate(['name' => 'super_admin']);
        $superAdmin->syncPermissions(Permission::all());
        $this->command->line('  ✓ Создана роль: super_admin');

        // 2. АДМИНИСТРАТОР - административные функции без полного доступа
        $admin = Role::firstOrCreate(['name' => 'admin']);
        $admin->syncPermissions([
            'view_dashboard',
            'manage_users',
            'view_users',
            'create_users',
            'edit_users',
            'delete_users',
            'manage_roles',
            'assign_roles',
            'view_analytics',
            'view_reports',
            'system_settings',
            'view_logs',
            'delete_any_content',
        ]);
        $this->command->line('  ✓ Создана роль: admin');

        // 3. МЕНЕДЖЕР - управление пользователями и аналитика
        $manager = Role::firstOrCreate(['name' => 'manager']);
        $manager->syncPermissions([
            'view_dashboard',
            'view_users',
            'edit_users',
            'view_analytics',
            'view_reports',
            'export_data',
            'create_content',
            'edit_own_content',
        ]);
        $this->command->line('  ✓ Создана роль: manager');

        // 4. МОДЕРАТОР - модерация контента
        $moderator = Role::firstOrCreate(['name' => 'moderator']);
        $moderator->syncPermissions([
            'view_dashboard',
            'moderate_content',
            'view_reports',
            'handle_reports',
            'edit_any_content',
            'delete_any_content',
            'create_content',
            'edit_own_content',
        ]);
        $this->command->line('  ✓ Создана роль: moderator');

        // 5. ОБЫЧНЫЙ ПОЛЬЗОВАТЕЛЬ - базовые функции
        $user = Role::firstOrCreate(['name' => 'user']);
        $user->syncPermissions([
            'view_dashboard',
            'create_content',
            'edit_own_content',
            'delete_own_content',
        ]);
        $this->command->line('  ✓ Создана роль: user');

        // ===================================================================
        // ШАГ 3: Создание тестовых пользователей
        // ===================================================================

        $this->command->info('Создание тестовых пользователей...');

        // Удаляем существующих тестовых пользователей (опционально)
        // User::whereIn('email', [
        //     'superadmin@example.com',
        //     'admin@example.com',
        //     'manager@example.com',
        //     'moderator@example.com',
        //     'user@example.com',
        // ])->delete();

        // 1. Суперадминистратор
        $superAdminUser = User::firstOrCreate(
            ['email' => 'superadmin@example.com'],
            [
                'name' => 'Super Admin',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ]
        );
        $superAdminUser->assignRole('super_admin');
        $this->command->line('  ✓ Пользователь: superadmin@example.com (Super Admin)');

        // 2. Администратор
        $adminUser = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin User',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ]
        );
        $adminUser->assignRole('admin');
        $this->command->line('  ✓ Пользователь: admin@example.com (Admin)');

        // 3. Менеджер
        $managerUser = User::firstOrCreate(
            ['email' => 'manager@example.com'],
            [
                'name' => 'Manager User',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ]
        );
        $managerUser->assignRole('manager');
        $this->command->line('  ✓ Пользователь: manager@example.com (Manager)');

        // 4. Модератор
        $moderatorUser = User::firstOrCreate(
            ['email' => 'moderator@example.com'],
            [
                'name' => 'Moderator User',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ]
        );
        $moderatorUser->assignRole('moderator');
        $this->command->line('  ✓ Пользователь: moderator@example.com (Moderator)');

        // 5. Обычный пользователь
        $regularUser = User::firstOrCreate(
            ['email' => 'user@example.com'],
            [
                'name' => 'Regular User',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ]
        );
        $regularUser->assignRole('user');
        $this->command->line('  ✓ Пользователь: user@example.com (User)');

        $this->command->info('✓ Тестовые пользователи созданы');

        // ===================================================================
        // ИТОГОВОЕ СООБЩЕНИЕ
        // ===================================================================

        $this->command->info('');
        $this->command->info('═══════════════════════════════════════════════════════════');
        $this->command->info('✓ Seeder успешно выполнен!');
        $this->command->info('═══════════════════════════════════════════════════════════');
        $this->command->info('');
        $this->command->info('Созданы роли:');
        $this->command->line('  • super_admin - Суперадминистратор (полный доступ)');
        $this->command->line('  • admin - Администратор (управление системой)');
        $this->command->line('  • manager - Менеджер (управление пользователями)');
        $this->command->line('  • moderator - Модератор (модерация контента)');
        $this->command->line('  • user - Пользователь (базовые функции)');
        $this->command->info('');
        $this->command->info('Тестовые учётные записи (пароль: password):');
        $this->command->line('  • superadmin@example.com - Суперадминистратор');
        $this->command->line('  • admin@example.com - Администратор');
        $this->command->line('  • manager@example.com - Менеджер');
        $this->command->line('  • moderator@example.com - Модератор');
        $this->command->line('  • user@example.com - Обычный пользователь');
        $this->command->info('');
    }
}
