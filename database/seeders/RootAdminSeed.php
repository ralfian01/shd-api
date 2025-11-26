<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;

class RootAdminSeed extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [
            'ta_uuid' => Uuid::uuid4()->toString(),
            'ta_username' => env('ROOT_ADMIN_USERNAME'),
            // 'ta_password' => hash('sha256', env('ROOT_ADMIN_PASSWORD')),
            'ta_password' => env('ROOT_ADMIN_PASSWORD'),
            'tr_id' => 1,
            'ta_deletable' => false,
            'ta_statusActive' => true,
            'ta_statusDelete' => false,
        ];

        DB::table('account')->insert($data);
    }
}
