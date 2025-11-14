# Примеры защиты роутов и проверки прав доступа в Laravel

## 1. Защита роутов с помощью middleware

### routes/web.php

```php
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Moderator\ModeratorController;

/*
|--------------------------------------------------------------------------
| Публичные роуты (доступны всем пользователям)
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/about', function () {
    return view('about');
})->name('about');

/*
|--------------------------------------------------------------------------
| Роуты для авторизованных пользователей (любая роль)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'verified'])->group(function () {
    // Дашборд доступен всем авторизованным пользователям
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');
    
    // Профиль пользователя
    Route::get('/profile', [ProfileController::class, 'edit'])
        ->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])
        ->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])
        ->name('profile.destroy');
});

/*
|--------------------------------------------------------------------------
| Административные роуты (admin + super_admin)
| Использование middleware 'role'
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:admin|super_admin'])->prefix('admin')->name('admin.')->group(function () {
    // Главная страница админ-панели
    Route::get('/dashboard', [AdminController::class, 'index'])
        ->name('dashboard');
    
    // Управление пользователями
    Route::get('/users', [UserController::class, 'index'])
        ->name('users.index');
    Route::get('/users/create', [UserController::class, 'create'])
        ->name('users.create');
    Route::post('/users', [UserController::class, 'store'])
        ->name('users.store');
    Route::get('/users/{user}/edit', [UserController::class, 'edit'])
        ->name('users.edit');
    Route::patch('/users/{user}', [UserController::class, 'update'])
        ->name('users.update');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])
        ->name('users.destroy');
});

/*
|--------------------------------------------------------------------------
| Роуты только для super_admin
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:super_admin'])->prefix('super-admin')->name('super-admin.')->group(function () {
    // Системные настройки
    Route::get('/settings', function () {
        return view('super-admin.settings');
    })->name('settings');
    
    // Управление ролями
    Route::resource('roles', RoleController::class);
});

/*
|--------------------------------------------------------------------------
| Роуты для модераторов (moderator + admin + super_admin)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:moderator|admin|super_admin'])->prefix('moderator')->name('moderator.')->group(function () {
    // Панель модератора
    Route::get('/dashboard', [ModeratorController::class, 'index'])
        ->name('dashboard');
    
    // Модерация контента
    Route::get('/content', [ModeratorController::class, 'content'])
        ->name('content');
    Route::post('/content/{id}/approve', [ModeratorController::class, 'approve'])
        ->name('content.approve');
    Route::post('/content/{id}/reject', [ModeratorController::class, 'reject'])
        ->name('content.reject');
});

/*
|--------------------------------------------------------------------------
| Защита через permissions (права доступа)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'permission:manage_users'])->group(function () {
    // Доступно всем, у кого есть право 'manage_users'
    Route::get('/user-management', [UserManagementController::class, 'index']);
});

Route::middleware(['auth', 'permission:view_analytics|view_reports'])->group(function () {
    // Доступно тем, у кого есть ЛЮБОЕ из указанных прав
    Route::get('/analytics', [AnalyticsController::class, 'index']);
});

/*
|--------------------------------------------------------------------------
| Комбинированная защита (роль И право)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:manager', 'permission:export_data'])->group(function () {
    // Доступно только менеджерам, которые имеют право export_data
    Route::get('/export', [ExportController::class, 'index']);
});
```

---

## 2. Защита в контроллерах

### app/Http/Controllers/Admin/UserController.php

```php
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * Контроллер управления пользователями
 * 
 * Все методы защищены проверкой прав доступа
 */
class UserController extends Controller
{
    /**
     * Конструктор с middleware (Laravel 10 и ниже)
     */
    public function __construct()
    {
        // Способ 1: Применить middleware ко всем методам
        $this->middleware(['auth', 'role:admin|super_admin']);
        
        // Способ 2: Применить middleware к конкретным методам
        // $this->middleware('role:admin|super_admin')->only(['destroy']);
        // $this->middleware('permission:manage_users')->except(['index', 'show']);
    }

    /**
     * Список пользователей
     * Проверяем право доступа в методе
     */
    public function index()
    {
        // Способ 1: Через authorize (требует Policy)
        $this->authorize('viewAny', User::class);
        
        // Способ 2: Через метод can()
        if (auth()->user()->cannot('manage_users')) {
            abort(403, 'У вас нет прав для просмотра пользователей');
        }
        
        // Способ 3: Через hasRole()
        if (!auth()->user()->hasRole(['admin', 'super_admin'])) {
            abort(403);
        }
        
        // Получаем пользователей с ролями и пагинацией
        $users = User::with('roles')
            ->paginate(20);

        return Inertia::render('Admin/Users/Index', [
            'users' => $users
        ]);
    }

    /**
     * Создание пользователя
     */
    public function store(Request $request)
    {
        // Проверяем право доступа
        $this->authorize('create', User::class);
        
        // Валидация
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|exists:roles,name',
        ]);

        // Создаём пользователя
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => bcrypt($validated['password']),
        ]);

        // Назначаем роль
        $user->assignRole($validated['role']);

        return redirect()->route('admin.users.index')
            ->with('success', 'Пользователь успешно создан');
    }

    /**
     * Обновление пользователя
     */
    public function update(Request $request, User $user)
    {
        // Проверяем право доступа на обновление конкретного пользователя
        $this->authorize('update', $user);
        
        // Валидация
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'role' => 'sometimes|exists:roles,name',
        ]);

        // Обновляем пользователя
        $user->update($validated);

        // Обновляем роль, если передана
        if (isset($validated['role'])) {
            $user->syncRoles([$validated['role']]);
        }

        return redirect()->route('admin.users.index')
            ->with('success', 'Пользователь обновлён');
    }

    /**
     * Удаление пользователя
     */
    public function destroy(User $user)
    {
        // Проверяем право доступа
        $this->authorize('delete', $user);
        
        // Запрещаем удалять самого себя
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Вы не можете удалить себя');
        }

        // Запрещаем обычным админам удалять супер-админов
        if ($user->hasRole('super_admin') && !auth()->user()->hasRole('super_admin')) {
            return back()->with('error', 'Вы не можете удалить супер-администратора');
        }

        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('success', 'Пользователь удалён');
    }
}
```

---

## 3. Policy для детальной проверки прав

### app/Policies/UserPolicy.php

```php
<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Policy для управления доступом к пользователям
 * 
 * Определяет, кто и что может делать с пользователями
 */
class UserPolicy
{
    /**
     * Просмотр списка пользователей
     */
    public function viewAny(User $currentUser): bool
    {
        // Админы и менеджеры могут видеть список пользователей
        return $currentUser->hasAnyRole(['admin', 'super_admin', 'manager']);
    }

    /**
     * Просмотр конкретного пользователя
     */
    public function view(User $currentUser, User $user): bool
    {
        // Можно просматривать себя или если есть права администратора
        return $currentUser->id === $user->id 
            || $currentUser->hasRole(['admin', 'super_admin']);
    }

    /**
     * Создание пользователя
     */
    public function create(User $currentUser): bool
    {
        // Только админы могут создавать пользователей
        return $currentUser->hasRole(['admin', 'super_admin']);
    }

    /**
     * Обновление пользователя
     */
    public function update(User $currentUser, User $user): bool
    {
        // Можно редактировать себя или если есть права администратора
        if ($currentUser->id === $user->id) {
            return true;
        }

        // Админы могут редактировать других, но не супер-админов (если сами не супер-админы)
        if ($currentUser->hasRole('admin') && !$user->hasRole('super_admin')) {
            return true;
        }

        // Супер-админы могут редактировать всех
        return $currentUser->hasRole('super_admin');
    }

    /**
     * Удаление пользователя
     */
    public function delete(User $currentUser, User $user): bool
    {
        // Нельзя удалить самого себя
        if ($currentUser->id === $user->id) {
            return false;
        }

        // Только супер-админы могут удалять супер-админов
        if ($user->hasRole('super_admin') && !$currentUser->hasRole('super_admin')) {
            return false;
        }

        // Админы и супер-админы могут удалять пользователей
        return $currentUser->hasRole(['admin', 'super_admin']);
    }

    /**
     * Назначение ролей
     */
    public function assignRole(User $currentUser, User $user): Response
    {
        // Только админы могут назначать роли
        if (!$currentUser->hasRole(['admin', 'super_admin'])) {
            return Response::deny('У вас нет прав для назначения ролей');
        }

        // Обычные админы не могут назначать роль super_admin
        if (!$currentUser->hasRole('super_admin')) {
            return Response::deny('Только супер-админ может назначать роль супер-администратора');
        }

        return Response::allow();
    }
}
```

### Регистрация Policy (app/Providers/AuthServiceProvider.php)

```php
<?php

namespace App\Providers;

use App\Models\User;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Карта политик приложения
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        User::class => UserPolicy::class,
    ];

    /**
     * Регистрация сервисов аутентификации
     */
    public function boot(): void
    {
        // Автоматическая регистрация политик
        $this->registerPolicies();
    }
}
```

---

## 4. Проверка прав в Blade шаблонах

### resources/views/dashboard.blade.php

```blade
@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Дашборд</h1>

    {{-- Проверка роли --}}
    @role('admin')
        <div class="alert alert-info">
            Вы администратор системы
        </div>
    @endrole

    @role('admin|super_admin')
        <a href="{{ route('admin.dashboard') }}" class="btn btn-primary">
            Админ-панель
        </a>
    @endrole

    {{-- Проверка наличия любой из ролей --}}
    @hasanyrole('admin|moderator|manager')
        <div class="admin-tools">
            <h3>Инструменты управления</h3>
        </div>
    @endhasanyrole

    {{-- Проверка наличия всех указанных ролей --}}
    @hasallroles('admin|manager')
        <div>Контент для тех, кто одновременно админ и менеджер</div>
    @endhasallroles

    {{-- Проверка прав доступа (permissions) --}}
    @can('manage_users')
        <a href="{{ route('admin.users.index') }}" class="btn btn-success">
            Управление пользователями
        </a>
    @endcan

    @can('view_analytics')
        <a href="{{ route('analytics.index') }}" class="btn btn-info">
            Аналитика
        </a>
    @endcan

    {{-- Проверка любого из прав --}}
    @canany(['edit_content', 'delete_content'])
        <div>Вы можете редактировать или удалять контент</div>
    @endcanany

    {{-- Обратная проверка (если НЕТ права) --}}
    @cannot('manage_users')
        <p>У вас нет доступа к управлению пользователями</p>
    @endcannot

    {{-- Использование директив unlessrole (если НЕТ роли) --}}
    @unlessrole('admin')
        <div class="alert alert-warning">
            Некоторые функции доступны только администраторам
        </div>
    @endunlessrole
</div>
@endsection
```

---

## 5. Проверка прав в Vue.js компонентах (с Inertia.js)

### resources/js/Pages/Dashboard.vue

```vue
<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

/**
 * Композабл для проверки прав доступа
 */
const page = usePage();
const user = computed(() => page.props.auth.user);

// Вспомогательные функции для проверки ролей и прав
const hasRole = (role) => {
    return user.value.roles?.includes(role) ?? false;
};

const hasAnyRole = (...roles) => {
    return roles.some(role => hasRole(role));
};

const hasPermission = (permission) => {
    return user.value.permissions?.includes(permission) ?? false;
};

const hasAnyPermission = (...permissions) => {
    return permissions.some(permission => hasPermission(permission));
};
</script>

<template>
    <div class="dashboard">
        <h1>Дашборд</h1>

        <!-- Условный рендеринг по роли -->
        <div v-if="hasRole('admin')" class="admin-panel">
            <h2>Админ-панель</h2>
            <a href="/admin/dashboard" class="btn btn-primary">
                Перейти в админку
            </a>
        </div>

        <!-- Проверка нескольких ролей -->
        <div v-if="hasAnyRole('admin', 'moderator', 'manager')">
            <h3>Инструменты управления</h3>
            <ul>
                <li v-if="hasRole('admin')">
                    <a href="/admin/users">Пользователи</a>
                </li>
                <li v-if="hasRole('moderator')">
                    <a href="/moderator/content">Модерация</a>
                </li>
            </ul>
        </div>

        <!-- Проверка прав доступа -->
        <div v-if="hasPermission('manage_users')">
            <a href="/users" class="btn">Управление пользователями</a>
        </div>

        <div v-if="hasPermission('view_analytics')">
            <a href="/analytics" class="btn">Аналитика</a>
        </div>

        <!-- Проверка любого из прав -->
        <div v-if="hasAnyPermission('edit_content', 'delete_content')">
            <p>Вы можете редактировать контент</p>
        </div>

        <!-- Обратная проверка (если НЕТ права) -->
        <div v-if="!hasPermission('manage_users')">
            <p class="text-muted">
                У вас нет доступа к управлению пользователями
            </p>
        </div>
    </div>
</template>
```

### Создание глобального композабла для проверки прав

**resources/js/Composables/usePermissions.js**

```javascript
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

/**
 * Композабл для проверки ролей и прав доступа
 * 
 * @returns {Object} Объект с методами проверки прав
 */
export function usePermissions() {
    const page = usePage();
    const user = computed(() => page.props.auth?.user);

    /**
     * Проверка наличия конкретной роли
     */
    const hasRole = (role) => {
        if (!user.value) return false;
        return user.value.roles?.includes(role) ?? false;
    };

    /**
     * Проверка наличия любой из указанных ролей
     */
    const hasAnyRole = (...roles) => {
        if (!user.value) return false;
        return roles.some(role => hasRole(role));
    };

    /**
     * Проверка наличия всех указанных ролей
     */
    const hasAllRoles = (...roles) => {
        if (!user.value) return false;
        return roles.every(role => hasRole(role));
    };

    /**
     * Проверка наличия права доступа
     */
    const hasPermission = (permission) => {
        if (!user.value) return false;
        return user.value.permissions?.includes(permission) ?? false;
    };

    /**
     * Проверка наличия любого из указанных прав
     */
    const hasAnyPermission = (...permissions) => {
        if (!user.value) return false;
        return permissions.some(permission => hasPermission(permission));
    };

    /**
     * Проверка наличия всех указанных прав
     */
    const hasAllPermissions = (...permissions) => {
        if (!user.value) return false;
        return permissions.every(permission => hasPermission(permission));
    };

    /**
     * Проверка - является ли пользователь админом
     */
    const isAdmin = computed(() => {
        return hasAnyRole('admin', 'super_admin');
    });

    /**
     * Проверка - является ли пользователь супер-админом
     */
    const isSuperAdmin = computed(() => {
        return hasRole('super_admin');
    });

    return {
        hasRole,
        hasAnyRole,
        hasAllRoles,
        hasPermission,
        hasAnyPermission,
        hasAllPermissions,
        isAdmin,
        isSuperAdmin,
        user
    };
}
```

**Использование композабла:**

```vue
<script setup>
import { usePermissions } from '@/Composables/usePermissions';

const { hasRole, hasPermission, isAdmin } = usePermissions();
</script>

<template>
    <div>
        <button v-if="isAdmin" class="btn-admin">
            Админ-панель
        </button>

        <div v-if="hasPermission('manage_users')">
            <!-- Контент для пользователей с правом manage_users -->
        </div>
    </div>
</template>
```

---

## 6. API защита роутов

### routes/api.php

```php
<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\PostController;

/*
|--------------------------------------------------------------------------
| API роуты с защитой через Sanctum
|--------------------------------------------------------------------------
*/

// Публичные API роуты (без аутентификации)
Route::get('/public/posts', [PostController::class, 'public']);

// Защищённые API роуты (требуется аутентификация через Sanctum)
Route::middleware('auth:sanctum')->group(function () {
    // Получение текущего пользователя
    Route::get('/user', function (Request $request) {
        return $request->user()->load('roles', 'permissions');
    });
    
    // Посты - доступны всем авторизованным
    Route::apiResource('posts', PostController::class);
});

// API для админов
Route::middleware(['auth:sanctum', 'role:admin|super_admin'])->prefix('admin')->group(function () {
    Route::apiResource('users', UserController::class);
    Route::post('users/{user}/roles', [UserController::class, 'assignRole']);
});

// API с проверкой прав доступа
Route::middleware(['auth:sanctum', 'permission:manage_users'])->group(function () {
    Route::get('/users', [UserController::class, 'index']);
});
```

### app/Http/Controllers/Api/UserController.php

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * API контроллер для управления пользователями
 */
class UserController extends Controller
{
    /**
     * Список пользователей
     */
    public function index(): JsonResponse
    {
        // Проверяем права через Policy
        $this->authorize('viewAny', User::class);

        $users = User::with('roles')->paginate(20);

        return response()->json($users);
    }

    /**
     * Создание пользователя
     */
    public function store(Request $request): JsonResponse
    {
        // Проверяем права
        if (!$request->user()->hasRole(['admin', 'super_admin'])) {
            return response()->json([
                'message' => 'Недостаточно прав'
            ], 403);
        }

        // Валидация и создание
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => bcrypt($validated['password']),
        ]);

        return response()->json($user, 201);
    }

    /**
     * Назначение роли пользователю
     */
    public function assignRole(Request $request, User $user): JsonResponse
    {
        // Проверяем права
        $this->authorize('assignRole', $user);

        $validated = $request->validate([
            'role' => 'required|exists:roles,name'
        ]);

        $user->syncRoles([$validated['role']]);

        return response()->json([
            'message' => 'Роль успешно назначена',
            'user' => $user->load('roles')
        ]);
    }
}
```

---

## 7. Создание кастомного middleware

### app/Http/Middleware/CheckSubscription.php

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware для проверки подписки пользователя
 * 
 * Пример кастомной логики проверки доступа
 */
class CheckSubscription
{
    /**
     * Обработка входящего запроса
     */
    public function handle(Request $request, Closure $next, string $plan = null): Response
    {
        $user = $request->user();

        // Проверяем, авторизован ли пользователь
        if (!$user) {
            return redirect()->route('login');
        }

        // Супер-админы имеют доступ ко всему
        if ($user->hasRole('super_admin')) {
            return $next($request);
        }

        // Проверяем наличие активной подписки
        if (!$user->hasActiveSubscription()) {
            return redirect()->route('subscription.expired')
                ->with('error', 'Ваша подписка истекла');
        }

        // Если указан конкретный план, проверяем его
        if ($plan && !$user->hasSubscriptionPlan($plan)) {
            abort(403, 'Для доступа требуется подписка: ' . $plan);
        }

        return $next($request);
    }
}
```

### Регистрация middleware (bootstrap/app.php для Laravel 11)

```php
<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\CheckSubscription;

return Application::configure(basePath: dirname(__DIR__))
    ->withMiddleware(function (Middleware $middleware) {
        // Регистрация алиаса для middleware
        $middleware->alias([
            'subscription' => CheckSubscription::class,
        ]);
    })
    ->create();
```

### Использование в роутах

```php
Route::middleware(['auth', 'subscription:premium'])->group(function () {
    Route::get('/premium-content', function () {
        return view('premium.content');
    });
});
```
