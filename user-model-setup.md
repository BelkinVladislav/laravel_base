# Полная настройка модели User для Laravel с Spatie Permission

## 1. Базовая конфигурация модели User

Отредактируйте файл: `src/app/Models/User.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

/**
 * Модель пользователя с поддержкой ролей и прав доступа
 * 
 * Использует Spatie Permission для управления ролями и правами
 * Функциональность:
 * - Аутентификация и авторизация
 * - Управление ролями
 * - Управление правами доступа
 * - API токены (Laravel Sanctum)
 * 
 * @property int $id
 * @property string $name
 * @property string $email
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $remember_token
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * 
 * @method static \Database\Factories\UserFactory factory($count = null, $state = [])
 */
class User extends Authenticatable
{
    // ===================================================================
    // TRAITS (Классы, которые добавляют функциональность)
    // ===================================================================
    
    use HasApiTokens,      // Laravel Sanctum - для API токенов
        HasFactory,         // Factory для тестирования
        Notifiable,         // Уведомления
        HasRoles;           // Spatie Permission - ГЛАВНЫЙ ТРЕЙТ ДЛЯ РОЛЕЙ!

    // ===================================================================
    // КОНФИГУРАЦИЯ
    // ===================================================================

    /**
     * Атрибуты, которые можно массово заполнять через create() или update()
     * 
     * ОСТОРОЖНО: Здесь только "безопасные" для заполнения атрибуты!
     * Никогда не добавляйте здесь 'password' напрямую!
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * Атрибуты, которые должны быть скрыты при сериализации (toArray, toJson)
     * 
     * Это используется для безопасности - приватные данные не будут отправлены клиенту
     * 
     * @var array<int, string>
     */
    protected $hidden = [
        'password',              // Пароль никогда не отправляется
        'remember_token',        // Токен "помни меня"
    ];

    /**
     * Типы атрибутов для автоматического приведения типов
     * 
     * Спецификация типов - Laravel автоматически приведёт значения
     * 
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',  // email_verified_at будет Carbon объектом
            'password' => 'hashed',             // Пароль автоматически хешируется
        ];
    }

    // ===================================================================
    // ОТНОШЕНИЯ (Relations) - БД связи с другими таблицами
    // ===================================================================

    /**
     * Получить все роли пользователя
     * 
     * Это отношение добавляется автоматически через HasRoles трейт
     * 
     * Использование:
     * $user->roles;              // Коллекция объектов Role
     * $user->roles()->first();    // Первая роль
     * 
     * @return BelongsToMany
     */
    public function roles(): BelongsToMany
    {
        // Это автоматически генерируется HasRoles
        // Вам обычно не нужно переопределять этот метод
        return $this->morphToMany(
            config('permission.models.role'),
            'model',
            config('permission.table_names.model_has_roles'),
            config('permission.column_names.model_morph_key'),
            'role_id'
        );
    }

    // ===================================================================
    // МЕТОДЫ ДЛЯ ПРОВЕРКИ РОЛЕЙ (добавляются HasRoles)
    // ===================================================================

    /**
     * Проверить, есть ли у пользователя конкретная роль
     * 
     * Пример:
     * $user->hasRole('admin')           // true/false
     * $user->hasRole(['admin', 'user']) // true если есть ЛЮБАЯ из ролей
     * 
     * @param string|array $roles
     * @return bool
     */
    // public function hasRole($roles): bool
    // {
    //     // Добавляется автоматически через HasRoles
    // }

    /**
     * Проверить, есть ли у пользователя ВСЕ указанные роли
     * 
     * Пример:
     * $user->hasAllRoles(['admin', 'moderator']) // true только если есть обе роли
     * 
     * @param array $roles
     * @return bool
     */
    // public function hasAllRoles($roles): bool
    // {
    //     // Добавляется автоматически через HasRoles
    // }

    /**
     * Проверить, есть ли у пользователя ЛЮБАЯ из указанных ролей
     * 
     * Пример:
     * $user->hasAnyRole(['admin', 'moderator']) // true если есть хотя бы одна
     * 
     * @param array $roles
     * @return bool
     */
    // public function hasAnyRole($roles): bool
    // {
    //     // Добавляется автоматически через HasRoles
    // }

    // ===================================================================
    // МЕТОДЫ ДЛЯ РАБОТЫ С ПРАВАМИ (добавляются HasRoles)
    // ===================================================================

    /**
     * Проверить, есть ли у пользователя конкретное право доступа
     * 
     * Используется в контроллерах и политиках
     * 
     * Пример:
     * $user->hasPermissionTo('manage_users')        // true/false
     * $user->can('manage_users')                    // альтернатива через Gate
     * 
     * @param string $permission
     * @return bool
     */
    // public function hasPermissionTo($permission): bool
    // {
    //     // Добавляется автоматически через HasRoles
    // }

    /**
     * Получить все права пользователя (прямые + через роли)
     * 
     * Пример:
     * $user->getAllPermissions()       // Все права (пр + через роли)
     * $user->getDirectPermissions()    // Только прямые права
     * $user->getPermissionsViaRoles()  // Только через роли
     * 
     * @return \Illuminate\Database\Eloquent\Collection
     */
    // public function getAllPermissions()
    // {
    //     // Добавляется автоматически через HasRoles
    // }

    // ===================================================================
    // МЕТОДЫ ДЛЯ НАЗНАЧЕНИЯ РОЛЕЙ
    // ===================================================================

    /**
     * Назначить роль пользователю
     * 
     * Пример использования:
     * $user->assignRole('admin')              // Одна роль
     * $user->assignRole(['admin', 'user'])    // Несколько ролей
     * 
     * @param string|array $roles
     * @return $this
     */
    // public function assignRole($roles)
    // {
    //     // Добавляется автоматически через HasRoles
    //     return $this;
    // }

    /**
     * Убрать роль у пользователя
     * 
     * Пример:
     * $user->removeRole('admin')
     * 
     * @param string|array $roles
     * @return $this
     */
    // public function removeRole($roles)
    // {
    //     // Добавляется автоматически через HasRoles
    //     return $this;
    // }

    /**
     * Синхронизировать роли (заменить старые на новые)
     * 
     * Пример:
     * $user->syncRoles(['moderator']) // Удалит admin, назначит moderator
     * 
     * @param array $roles
     * @return $this
     */
    // public function syncRoles($roles)
    // {
    //     // Добавляется автоматически через HasRoles
    //     return $this;
    // }

    // ===================================================================
    // МЕТОДЫ ДЛЯ НАЗНАЧЕНИЯ ПРАВ (прямо пользователю)
    // ===================================================================

    /**
     * Назначить право доступа пользователю
     * 
     * ВАЖНО: Обычно права назначаются ролям, а не пользователям!
     * Это для исключений и особых случаев
     * 
     * Пример:
     * $user->givePermissionTo('manage_users')
     * 
     * @param string|array $permissions
     * @return $this
     */
    // public function givePermissionTo($permissions)
    // {
    //     // Добавляется автоматически через HasRoles
    //     return $this;
    // }

    /**
     * Убрать право доступа у пользователя
     * 
     * Пример:
     * $user->revokePermissionTo('manage_users')
     * 
     * @param string|array $permissions
     * @return $this
     */
    // public function revokePermissionTo($permissions)
    // {
    //     // Добавляется автоматически через HasRoles
    //     return $this;
    // }

    // ===================================================================
    // ДОПОЛНИТЕЛЬНЫЕ МЕТОДЫ (кастомные, на ваше усмотрение)
    // ===================================================================

    /**
     * Проверить, является ли пользователь администратором
     * 
     * Удобный метод для частых проверок
     * 
     * @return bool
     */
    public function isAdmin(): bool
    {
        return $this->hasAnyRole(['admin', 'super_admin']);
    }

    /**
     * Проверить, является ли пользователь суперадминистратором
     * 
     * @return bool
     */
    public function isSuperAdmin(): bool
    {
        return $this->hasRole('super_admin');
    }

    /**
     * Проверить, является ли пользователь модератором
     * 
     * @return bool
     */
    public function isModerator(): bool
    {
        return $this->hasRole('moderator');
    }

    /**
     * Проверить, может ли пользователь управлять другим пользователем
     * 
     * Суперадмины могут управлять всеми
     * Админы могут управлять не-админами
     * Остальные не могут
     * 
     * @param User $targetUser Пользователь, которым нужно управлять
     * @return bool
     */
    public function canManage(User $targetUser): bool
    {
        // Суперадмин может управлять всеми
        if ($this->isSuperAdmin()) {
            return true;
        }

        // Админ может управлять всеми кроме суперадминов
        if ($this->hasRole('admin') && !$targetUser->isSuperAdmin()) {
            return true;
        }

        // Остальные не могут управлять
        return false;
    }

    /**
     * Получить название роли пользователя (первая роль)
     * 
     * @return string|null
     */
    public function getRoleLabel(): ?string
    {
        $roles = [
            'super_admin' => 'Суперадминистратор',
            'admin' => 'Администратор',
            'manager' => 'Менеджер',
            'moderator' => 'Модератор',
            'user' => 'Пользователь',
        ];

        $role = $this->roles()->first()?->name;
        return $roles[$role] ?? $role;
    }

    /**
     * Получить цвет бейджа для роли (для UI)
     * 
     * @return string
     */
    public function getRoleColor(): string
    {
        $colors = [
            'super_admin' => 'red',
            'admin' => 'purple',
            'manager' => 'blue',
            'moderator' => 'yellow',
            'user' => 'gray',
        ];

        $role = $this->roles()->first()?->name;
        return $colors[$role] ?? 'gray';
    }
}
```

---

## 2. Необходимые настройки в конфигурации

Убедитесь, что в файле `config/permission.php` (создаётся при публикации Spatie):

```php
<?php

return [
    // Модели для использования
    'models' => [
        'permission' => Spatie\Permission\Models\Permission::class,
        'role' => Spatie\Permission\Models\Role::class,
    ],

    // Имена таблиц в БД
    'table_names' => [
        'roles' => 'roles',
        'permissions' => 'permissions',
        'model_has_roles' => 'model_has_roles',
        'role_has_permissions' => 'role_has_permissions',
    ],

    // Названия колонок
    'column_names' => [
        'model_morph_key' => 'model_id',
        'role_pivot_key' => 'role_id',
        'permission_pivot_key' => 'permission_id',
    ],

    // Guard для аутентификации
    'guards' => [
        'web' => ['uses_roles' => true, 'uses_permissions' => true],
        'api' => ['uses_roles' => true, 'uses_permissions' => true],
    ],

    // Кэширование для производительности
    'cache' => [
        'expiration_time' => \DateInterval::createFromDateString('24 hours'),
        'key' => 'spatie.permission.cache',
        'store' => 'default',
    ],
];
```

---

## 3. Регистрация middleware для защиты маршрутов

Отредактируйте `bootstrap/app.php` (Laravel 11) или `app/Http/Kernel.php` (Laravel 10):

```php
<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Регистрация middleware для защиты роутов
        $middleware->alias([
            // Проверка роли
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            
            // Проверка права доступа
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            
            // Проверка роли ИЛИ права доступа
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
```

---

## 4. Примеры использования методов User модели

### 4.1 В контроллере

```php
<?php

namespace App\Http\Controllers;

use App\Models\User;

class AdminController extends Controller
{
    public function index()
    {
        $currentUser = auth()->user();

        // ===== ПРОВЕРКА РОЛЕЙ =====
        
        // Проверка одной роли
        if ($currentUser->hasRole('admin')) {
            // Пользователь администратор
        }

        // Проверка нескольких ролей (любой из них)
        if ($currentUser->hasAnyRole(['admin', 'moderator'])) {
            // Пользователь админ или модератор
        }

        // Проверка всех ролей
        if ($currentUser->hasAllRoles(['admin', 'manager'])) {
            // Пользователь имеет ОБЕ роли
        }

        // Использование кастомных методов
        if ($currentUser->isAdmin()) {
            // Упрощённая проверка
        }

        // ===== ПРОВЕРКА ПРАВ =====

        if ($currentUser->hasPermissionTo('manage_users')) {
            // Пользователь может управлять пользователями
        }

        if ($currentUser->can('manage_users')) {
            // Альтернативный способ через Gate
        }

        // ===== РАБОТА С ПОЛЬЗОВАТЕЛЯМИ =====

        // Получить пользователя
        $user = User::find(1);

        // Назначить роль
        $user->assignRole('moderator');

        // Назначить несколько ролей
        $user->assignRole(['moderator', 'user']);

        // Синхронизировать роли (заменить)
        $user->syncRoles(['user']); // Удалит все роли, назначит только 'user'

        // Удалить роль
        $user->removeRole('moderator');

        // Получить все роли
        $roles = $user->roles; // Коллекция объектов Role

        // Получить названия ролей
        $roleNames = $user->getRoleNames(); // Коллекция строк ['admin', 'user']

        // ===== РАБОТА С ПРАВАМИ =====

        // Получить все права пользователя
        $permissions = $user->getAllPermissions();
        
        // Только прямые права
        $directPermissions = $user->getDirectPermissions();
        
        // Только через роли
        $permissionsViaRoles = $user->getPermissionsViaRoles();

        // Получить названия прав
        $permissionNames = $user->getAllPermissions()->pluck('name');

        // Назначить право прямо пользователю
        $user->givePermissionTo('manage_users');

        // Убрать право у пользователя
        $user->revokePermissionTo('manage_users');

        // ===== ПОИСК ПОЛЬЗОВАТЕЛЕЙ ПО РОЛЯМ =====

        // Пользователи с конкретной ролью
        $admins = User::role('admin')->get();

        // Пользователи без конкретной роли
        $nonAdmins = User::withoutRole('admin')->get();

        // Пользователи с конкретным правом
        $managers = User::permission('manage_users')->get();

        // Пользователи без конкретного права
        $nonManagers = User::withoutPermission('manage_users')->get();
    }
}
```

### 4.2 В маршрутах

```php
<?php

use Illuminate\Support\Facades\Route;

// ===== ЗАЩИТА ПО РОЛИ =====

// Одна роль
Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/admin', [AdminController::class, 'index']);
});

// Несколько ролей (любой из них)
Route::middleware(['auth', 'role:admin|moderator'])->group(function () {
    Route::get('/manage', [ManageController::class, 'index']);
});

// ===== ЗАЩИТА ПО ПРАВУ =====

Route::middleware(['auth', 'permission:manage_users'])->group(function () {
    Route::get('/users', [UserController::class, 'index']);
});

// ===== ЗАЩИТА ПО РОЛИ ИЛИ ПРАВУ =====

Route::middleware(['auth', 'role_or_permission:admin|manage_users'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index']);
});
```

### 4.3 В политиках (Policy)

```php
<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Администратор может управлять пользователями
     */
    public function manage(User $currentUser, User $targetUser): bool
    {
        // Использование кастомного метода
        return $currentUser->canManage($targetUser);
    }

    /**
     * Пользователь может редактировать себя
     */
    public function update(User $currentUser, User $targetUser): bool
    {
        return $currentUser->id === $targetUser->id || $currentUser->isAdmin();
    }
}
```

### 4.4 В Blade шаблонах

```blade
<!-- Проверка роли -->
@role('admin')
    <div>Вы администратор</div>
@endrole

<!-- Проверка любой из ролей -->
@hasanyrole('admin|moderator')
    <div>Вы админ или модератор</div>
@endhasanyrole

<!-- Проверка всех ролей -->
@hasallroles('admin|moderator')
    <div>Вы админ И модератор одновременно</div>
@endhasallroles

<!-- Проверка права -->
@can('manage_users')
    <a href="/users">Управление пользователями</a>
@endcan

<!-- Обратная проверка -->
@cannot('manage_users')
    <p>У вас нет доступа к управлению пользователями</p>
@endcannot

<!-- Если НЕ роль -->
@unlessrole('admin')
    <p>Вы не администратор</p>
@endunlessrole

<!-- Кастомный метод -->
@if(auth()->user()->isAdmin())
    <button>Админ-панель</button>
@endif
```

### 4.5 В Vue.js компонентах

```vue
<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

const page = usePage();
const user = computed(() => page.props.auth.user);

// Методы проверки
const hasRole = (role) => user.value.roles?.includes(role) ?? false;
const hasPermission = (permission) => user.value.permissions?.includes(permission) ?? false;
</script>

<template>
    <div>
        <!-- Проверка роли в шаблоне -->
        <button v-if="hasRole('admin')">Админ-панель</button>
        
        <!-- Проверка права -->
        <a v-if="hasPermission('manage_users')">Управление пользователями</a>
        
        <!-- Условный класс -->
        <div :class="{ 'admin-badge': hasRole('admin') }">
            Профиль пользователя
        </div>
    </div>
</template>
```

---

## 5. Миграции для таблиц ролей и прав

Когда вы выполняете `php artisan migrate`, создаются следующие таблицы:

```
users (была уже)
├── id
├── name
├── email
├── password
├── email_verified_at
├── created_at
├── updated_at

roles (новая)
├── id
├── name
├── guard_name (web, api)
├── created_at
├── updated_at

permissions (новая)
├── id
├── name
├── guard_name
├── created_at
├── updated_at

model_has_roles (новая - связь пользователей и ролей)
├── role_id
├── model_id (user id)
├── model_type (App\Models\User)

role_has_permissions (новая - связь ролей и прав)
├── permission_id
├── role_id

model_has_permissions (новая - прямые права пользователям)
├── permission_id
├── model_id
├── model_type
```

---

## 6. Таблица методов пользователя

| Метод | Описание | Пример |
|-------|---------|--------|
| `hasRole($role)` | Проверить наличие роли | `$user->hasRole('admin')` |
| `hasAnyRole($roles)` | Проверить любую из ролей | `$user->hasAnyRole(['admin', 'mod'])` |
| `hasAllRoles($roles)` | Проверить все роли | `$user->hasAllRoles(['admin', 'mod'])` |
| `assignRole($role)` | Назначить роль | `$user->assignRole('admin')` |
| `removeRole($role)` | Удалить роль | `$user->removeRole('admin')` |
| `syncRoles($roles)` | Синхронизировать роли | `$user->syncRoles(['user'])` |
| `hasPermissionTo($perm)` | Проверить право | `$user->hasPermissionTo('manage_users')` |
| `givePermissionTo($perm)` | Дать право | `$user->givePermissionTo('manage_users')` |
| `revokePermissionTo($perm)` | Забрать право | `$user->revokePermissionTo('manage_users')` |
| `getAllPermissions()` | Получить все права | `$user->getAllPermissions()` |
| `getDirectPermissions()` | Только прямые права | `$user->getDirectPermissions()` |
| `getPermissionsViaRoles()` | Через роли | `$user->getPermissionsViaRoles()` |
| `roles` | Отношение к ролям | `$user->roles()->get()` |
| `isAdmin()` | Кастомный метод | `$user->isAdmin()` |
| `isSuperAdmin()` | Кастомный метод | `$user->isSuperAdmin()` |
| `canManage($user)` | Кастомный метод | `$user->canManage($otherUser)` |

---

## 7. Полный пример использования

```php
<?php

// Получить пользователя
$user = User::find(1);

// Проверить, администратор ли
if ($user->isAdmin()) {
    // Может управлять всей системой
    
    // Получить всех пользователей
    $allUsers = User::all();
    
    // Назначить роль другому пользователю
    $otherUser = User::find(2);
    $otherUser->assignRole('moderator');
    
    // Проверить может ли управлять
    if ($user->canManage($otherUser)) {
        $otherUser->syncRoles(['user']);
    }
}

// Если модератор
if ($user->hasRole('moderator')) {
    // Может модерировать контент
    if ($user->hasPermissionTo('moderate_content')) {
        // Модерирует контент
    }
}

// Получить всех админов
$admins = User::role('admin')->get();

// Получить количество пользователей с ролью
$count = User::role('user')->count();

// Получить названия ролей
$roles = $user->getRoleNames(); // ['admin', 'moderator']

// Получить метку роли (для UI)
$label = $user->getRoleLabel(); // "Администратор"

// Получить цвет для бейджа
$color = $user->getRoleColor(); // "purple"
```

Готово! Теперь ваша модель User полностью настроена для работы с Spatie Permission! 🚀
