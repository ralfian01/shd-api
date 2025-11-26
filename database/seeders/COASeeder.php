<?php

namespace Database\Seeders;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use League\Csv\Reader;

class COASeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Solusi untuk error foreign key di PostgreSQL: Nonaktifkan trigger sementara
        // Ganti 'account_charts' dengan nama tabel Anda jika berbeda
        DB::statement('ALTER TABLE tbl_account_charts DISABLE TRIGGER ALL;');

        // // Kosongkan tabel. Truncate lebih efisien dan me-reset auto-increment.
        // DB::table('account_charts')->truncate();

        $csvFilePath = database_path('seeders/data/tbl_account_charts_2.csv');

        if (!file_exists($csvFilePath)) {
            $this->command->error("File CSV tidak ditemukan di: " . $csvFilePath);
            // Aktifkan lagi sebelum keluar jika terjadi error
            DB::statement('ALTER TABLE tbl_account_charts ENABLE TRIGGER ALL;');
            return;
        }

        $csv = Reader::createFromPath($csvFilePath, 'r');
        $csv->setHeaderOffset(0); // Baris pertama adalah header
        $records = $csv->getRecords();

        // PETA KUNCI: [ 'business_id-account_code' => 'database_id' ]
        // Contoh: [ '1-1.1' => 5, '1-1.2' => 8 ]
        $map = [];

        foreach ($records as $record) {
            $parentId = null;
            $accountCode = $record['account_code'];
            $businessId = $record['business_id'];

            // Langkah 1: Tentukan apakah akun ini memiliki induk berdasarkan `account_code`
            if (strpos($accountCode, '.') !== false) {
                // Akun ini adalah anak. Mari kita cari kode induknya.
                // Contoh: '1.1.1' -> '1.1' atau '5.2' -> '5'
                $parentAccountCode = substr($accountCode, 0, strrpos($accountCode, '.'));

                // Buat kunci unik untuk mencari induk di dalam peta
                $parentUniqueKey = "{$businessId}-{$parentAccountCode}";

                // Langkah 2: Cari ID database dari induk di dalam peta
                if (isset($map[$parentUniqueKey])) {
                    $parentId = $map[$parentUniqueKey];
                }
            }
            // Jika tidak ada '.' maka ini adalah induk tertinggi, $parentId tetap null.

            // Langkah 3: Masukkan data ke database dengan parent_id yang sudah benar
            $newId = DB::table('account_charts')->insertGetId([
                'business_id'        => $businessId,
                'parent_id'          => $parentId, // Menggunakan $parentId yang sudah ditemukan
                'account_code'       => $accountCode,
                'account_name'       => $record['account_name'],
                'account_type'       => $record['account_type'],
                'normal_balance'     => $record['normal_balance'],
                'cash_flow_activity' => $record['cash_flow_activity'] === 'NULL' ? null : $record['cash_flow_activity'],
                'is_active'          => $record['is_active'],
            ]);

            // Langkah 4: Simpan ID yang baru dibuat ke dalam peta.
            // Ini PENTING agar anak-anak dari akun ini bisa menemukannya.
            $currentUniqueKey = "{$businessId}-{$accountCode}";
            $map[$currentUniqueKey] = $newId;
        }

        // JANGAN LUPA: Aktifkan kembali trigger setelah seeder selesai
        DB::statement('ALTER TABLE tbl_account_charts ENABLE TRIGGER ALL;');

        $this->command->info('Seeder Chart of Accounts (COA) berhasil dijalankan dengan hierarki yang benar.');
    }
}
