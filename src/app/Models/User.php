<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

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
