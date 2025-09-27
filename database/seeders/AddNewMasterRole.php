<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;
class AddNewMasterRole extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $masterRole = Role::create(['name' => 'master']);
        $master = User::create([
            'first_name' => 'Master',
            'last_name' => 'User',
            'email' => 'master@example.com',
            'mobile_number' => '1234567891',
            'password' => Hash::make('password'),
        ]);

        $master->assignRole('master');
    }
}
