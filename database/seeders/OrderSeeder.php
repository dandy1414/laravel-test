<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::select('id')->get();

        if ($users->isEmpty()) {
            $this->command?->warn('No users found. Seed users first.');
            return;
        }

        foreach ($users as $user) {
            $count = random_int(1, 5);

            for ($i = 0; $i < $count; $i++) {
                $createdAt = now()
                    ->subDays(random_int(0, 90))
                    ->setTime(random_int(0, 23), random_int(0, 59), random_int(0, 59));

                Order::create([
                    'user_id'    => $user->id,
                    'created_at' => $createdAt,
                ]);
            }
        }
    }
}
