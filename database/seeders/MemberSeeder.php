<?php

namespace Database\Seeders;

use App\Models\Member;
use Illuminate\Database\Seeder;

class MemberSeeder extends Seeder
{
    public function run(): void
    {
        $members = [
            [
                'member_code' => 'KOP-001',
                'name' => 'Koperasi Sejahtera Bersama',
                'is_active' => true,
            ],
            [
                'member_code' => 'KOP-002',
                'name' => 'Koperasi Maju Jaya',
                'is_active' => true,
            ],
            [
                'member_code' => 'KOP-003',
                'name' => 'Koperasi Simpan Pinjam',
                'is_active' => false,
            ],
        ];

        foreach ($members as $member) {
            Member::updateOrCreate(
                ['member_code' => $member['member_code']],
                $member
            );
        }
    }
}
