<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();
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

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
    }
}
