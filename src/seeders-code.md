# Полный код Seeders для Laravel проекта с ролями и правами доступа

## 1. RolePermissionSeeder - основной seeder для создания ролей и прав

Создайте файл: `src/database/seeders/RolePermissionSeeder.php`

```php
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
```

---

## 2. DatabaseSeeder - главный seeder, который вызывает все остальные

Отредактируйте файл: `src/database/seeders/DatabaseSeeder.php`

```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Главный seeder приложения
 * 
 * Этот класс вызывает все остальные seeders в правильном порядке
 * Выполнение: php artisan db:seed
 */
class DatabaseSeeder extends Seeder
{
    /**
     * Запуск всех seeders
     * 
     * @return void
     */
    public function run(): void
    {
        $this->command->info('Начало заполнения базы данных...');
        $this->command->line('');

        // Вызываем seeder для создания ролей и прав доступа
        // ЭТО ДОЛЖНО БЫТЬ ПЕРВЫМ, так как другие seeders могут зависеть от ролей
        $this->call([
            RolePermissionSeeder::class,
            // Здесь можно добавить другие seeders
            // PostSeeder::class,
            // CommentSeeder::class,
            // TagSeeder::class,
        ]);

        $this->command->info('');
        $this->command->info('✓ База данных успешно заполнена!');
    }
}
```

---

## 3. UserSeeder - seeder для создания обычных пользователей (опционально)

Создайте файл: `src/database/seeders/UserSeeder.php`

```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

/**
 * Seeder для создания обычных пользователей
 * 
 * Этот seeder создаёт дополнительных пользователей для тестирования
 * Выполнение: php artisan db:seed --class=UserSeeder
 */
class UserSeeder extends Seeder
{
    /**
     * Запуск seeder
     * 
     * @return void
     */
    public function run(): void
    {
        $this->command->info('Создание обычных пользователей...');

        // Создаём 10 обычных пользователей с ролью 'user'
        User::factory()
            ->count(10)
            ->create()
            ->each(function ($user) {
                // Назначаем роль 'user' каждому пользователю
                $user->assignRole('user');
            });

        $this->command->info('✓ 10 обычных пользователей созданы с ролью "user"');
    }
}
```

---

## 4. UserFactory - фабрика для создания пользователей

Проверьте/обновите файл: `src/database/factories/UserFactory.php`

```php
<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\User;

/**
 * Фабрика для создания тестовых пользователей
 * 
 * Используется в seeders для создания реалистичных данных
 */
class UserFactory extends Factory
{
    /**
     * Модель, которая связана с этой фабрикой
     * 
     * @var string
     */
    protected $model = User::class;

    /**
     * Определение состояния модели по умолчанию
     * 
     * @return array
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => Hash::make('password'), // пароль по умолчанию
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Состояние для создания непроверенного пользователя
     * 
     * @return Factory
     */
    public function unverified(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'email_verified_at' => null,
            ];
        });
    }
}
```

---

## 5. Команды для запуска seeders

### Выполнение всех seeders

```bash
# Стандартный запуск (выполняет DatabaseSeeder)
docker-compose exec app php artisan db:seed

# Запуск с дополнительной информацией
docker-compose exec app php artisan db:seed --verbose
```

### Выполнение конкретного seeder

```bash
# Только RolePermissionSeeder
docker-compose exec app php artisan db:seed --class=RolePermissionSeeder

# Только UserSeeder
docker-compose exec app php artisan db:seed --class=UserSeeder
```

### Выполнение миграций + seeders (на свежей БД)

```bash
# Сброс БД + миграции + seeders (ОСТОРОЖНО! Удаляет всё!)
docker-compose exec app php artisan migrate:fresh --seed

# С дополнительной информацией
docker-compose exec app php artisan migrate:fresh --seed --verbose
```

### Выполнение миграций + конкретного seeder

```bash
docker-compose exec app php artisan migrate:fresh --class=Database\\Seeders\\RolePermissionSeeder
```

---

## 6. Проверка созданных ролей и прав

### Через Laravel Tinker

```bash
docker-compose exec app php artisan tinker
```

Внутри Tinker:

```php
// Просмотр всех ролей
>>> \Spatie\Permission\Models\Role::all()

// Просмотр всех прав
>>> \Spatie\Permission\Models\Permission::all()

// Просмотр роли и её прав
>>> $admin = \Spatie\Permission\Models\Role::findByName('admin')
>>> $admin->permissions

// Просмотр пользователя и его ролей
>>> $user = \App\Models\User::find(1)
>>> $user->roles
>>> $user->permissions

// Проверка права пользователя
>>> $user->hasPermissionTo('manage_users')

// Выход
>>> exit
```

### Через Database CLI

```bash
# Подключение к PostgreSQL
docker-compose exec postgres psql -U laravel -d laravel

# В PostgreSQL:
SELECT * FROM roles;
SELECT * FROM permissions;
SELECT * FROM model_has_roles WHERE model_id = 1;
SELECT * FROM role_has_permissions WHERE role_id = 1;

# Выход
\q
```

### Через Artisan команды

```bash
# Просмотр всех ролей
docker-compose exec app php artisan role:list

# Просмотр всех прав
docker-compose exec app php artisan permission:list

# Просмотр ролей и прав пользователя
docker-compose exec app php artisan tinker
>>> \App\Models\User::find(1)->load('roles', 'permissions')
>>> exit
```

---

## 7. Дополнительные операции с ролями и правами

### Добавление прав к роли программно

```php
// Внутри контроллера или Tinker
$admin = \Spatie\Permission\Models\Role::findByName('admin');
$admin->givePermissionTo('new_permission');
```

### Удаление прав из роли

```php
$admin = \Spatie\Permission\Models\Role::findByName('admin');
$admin->revokePermissionTo('some_permission');
```

### Назначение роли пользователю

```php
$user = \App\Models\User::find(1);
$user->assignRole('admin');
```

### Удаление роли у пользователя

```php
$user = \App\Models\User::find(1);
$user->removeRole('admin');
```

### Синхронизация ролей (замена старых новыми)

```php
$user = \App\Models\User::find(1);
$user->syncRoles(['user', 'moderator']);
```

---

## 8. Таблица с тестовыми пользователями и их доступом

| Email | Пароль | Роль | Может делать |
|-------|--------|------|-------------|
| superadmin@example.com | password | super_admin | Всё (полный доступ) |
| admin@example.com | password | admin | Управление пользователями, система, контент |
| manager@example.com | password | manager | Управление пользователями, аналитика |
| moderator@example.com | password | moderator | Модерация контента, отчёты |
| user@example.com | password | user | Создание контента, дашборд |

---

## 9. Как использовать в своём проекте

### Шаг 1: Создание своей роли

Добавьте в `RolePermissionSeeder` (метод `run`):

```php
// После создания стандартных ролей добавьте:
$editor = Role::firstOrCreate(['name' => 'editor']);
$editor->syncPermissions([
    'view_dashboard',
    'create_content',
    'edit_own_content',
    'publish_content',
]);
```

### Шаг 2: Добавление новых прав

Добавьте в массив `$permissions` в `RolePermissionSeeder`:

```php
$permissions = [
    // ... существующие права
    'my_new_permission',
    'another_permission',
];
```

### Шаг 3: Использование в маршрутах

```php
Route::middleware(['auth', 'role:editor'])->group(function () {
    Route::get('/editor/dashboard', [EditorController::class, 'dashboard']);
});
```

### Шаг 4: Переиспользование seeder

```bash
# Сбросить БД и заполнить заново
docker-compose exec app php artisan migrate:fresh --seed

# Или только обновить роли/пользователей
docker-compose exec app php artisan db:seed --class=RolePermissionSeeder
```
