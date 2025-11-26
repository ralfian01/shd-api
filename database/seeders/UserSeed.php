<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;

class UserSeed extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [
            [
                'ta_uuid' => Uuid::uuid4()->toString(),
                'ta_username' => 'admin@app.com',
                'ta_password' => hash('sha256', '123456789'),
                'tr_id' => 2,
                'ta_deletable' => true,
                'ta_statusActive' => true,
                'ta_statusDelete' => false,
            ],
            [
                'ta_uuid' => Uuid::uuid4()->toString(),
                'ta_username' => 'cs1@app.com',
                'ta_password' => hash('sha256', '123456789'),
                'tr_id' => 3,
                'ta_deletable' => true,
                'ta_statusActive' => true,
                'ta_statusDelete' => false,
            ],
            [
                'ta_uuid' => Uuid::uuid4()->toString(),
                'ta_username' => 'cs2@app.com',
                'ta_password' => hash('sha256', '123456789'),
                'tr_id' => 3,
                'ta_deletable' => true,
                'ta_statusActive' => true,
                'ta_statusDelete' => false,
            ],
            [
                'ta_uuid' => Uuid::uuid4()->toString(),
                'ta_username' => 'leader1@app.com',
                'ta_password' => hash('sha256', '123456789'),
                'tr_id' => 4,
                'ta_deletable' => true,
                'ta_statusActive' => true,
                'ta_statusDelete' => false,
            ]
        ];

        DB::table('account')->insert($data);
    }
}
