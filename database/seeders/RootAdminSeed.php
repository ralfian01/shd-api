<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Ramsey\Uuid\Uuid;

class RootAdminSeed extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [
            'uuid' => Uuid::uuid4()->toString(),
            'username' => env('ROOT_ADMIN_USERNAME'),
            'password' => Hash::make(env('ROOT_ADMIN_PASSWORD')),
            'role_id' => 1,
            'deletable' => false,
            'status_active' => true,
            'status_delete' => false,
        ];

        DB::table('account')->insert($data);
    }
}
