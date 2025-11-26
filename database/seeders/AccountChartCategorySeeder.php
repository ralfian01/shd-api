<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AccountChartCategory;
use Illuminate\Support\Facades\DB;

class AccountChartCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        // Tentukan path ke file CSV
        $csvFile = database_path('seeders/data/tbl_account_chart_categories.csv');

        // Periksa apakah file ada
        if (!file_exists($csvFile)) {
            $this->command->error("File CSV tidak ditemukan di: " . $csvFile);
            return;
        }

        // Baca file CSV
        $data = array_map('str_getcsv', file($csvFile));

        // Ambil header (baris pertama) dan hapus dari data
        $header = array_shift($data);

        // Kosongkan tabel sebelum mengisi untuk menghindari duplikasi
        AccountChartCategory::truncate();

        foreach ($data as $row) {
            // Gabungkan header dengan baris data untuk membuat array asosiatif
            $rowData = array_combine($header, $row);

            // Konversi nilai 'NULL' string menjadi nilai null PHP
            if (strtoupper($rowData['cash_flow_activity']) === 'NULL') {
                $rowData['cash_flow_activity'] = null;
            }

            // Buat record baru
            AccountChartCategory::create($rowData);
        }

        // Aktifkan kembali pengecekan foreign key
        // DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $this->command->info("Seeder Kategori Bagan Akun Perkiraan berhasil dijalankan.");
    }
}
