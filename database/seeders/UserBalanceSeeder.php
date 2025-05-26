<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\UserBalance;
use Illuminate\Database\Seeder;

class UserBalanceSeeder extends Seeder
{
    public function run(): void
    {
        // Set initial balance for all existing users
        User::all()->each(function ($user) {
            UserBalance::create([
                'user_id' => $user->id,
                'balance' => '1000000.00' // NGN 1,000,000
            ]);
        });
    }
}
