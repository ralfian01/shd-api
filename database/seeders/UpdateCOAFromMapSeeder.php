<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use League\Csv\Reader;
use Exception; // Import Exception class

class UpdateCOAFromMapSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $csvFilePath = database_path('seeders/data/tbl_account_charts_2 - remap.csv');
        $tableName = 'account_charts'; // Ganti jika nama tabel Anda berbeda

        if (!file_exists($csvFilePath)) {
            $this->command->error("File CSV pemetaan tidak ditemukan di: " . $csvFilePath);
            return;
        }

        $csv = Reader::createFromPath($csvFilePath, 'r');
        $csv->setHeaderOffset(0);
        $records = $csv->getRecords();

        // Gunakan transaksi untuk memastikan integritas data.
        // Jika ada satu saja error, semua perubahan akan dibatalkan.
        DB::beginTransaction();

        try {
            $updateCount = 0;
            $deleteCount = 0;

            foreach ($records as $record) {
                $codeBefore = $record['account_code_before'];
                $codeAfter = $record['account_code_after'];

                // Lewati baris jika account_code_before kosong
                if (empty($codeBefore)) {
                    continue;
                }

                // Kondisi 1: Jika account_code_after adalah 'NULL' atau kosong, hapus record.
                if ($codeAfter === 'NULL' || $codeAfter === '') {
                    $deletedRows = DB::table($tableName)->where('account_code', $codeBefore)->delete();
                    if ($deletedRows > 0) {
                        $this->command->warn("Menghapus akun dengan kode lama: {$codeBefore}");
                        $deleteCount++;
                    }
                }
                // Kondisi 2: Jika ada account_code_after, perbarui record.
                else {
                    $updateData = [
                        'account_code' => $record['account_code_after'],
                        'account_name' => $record['account_name'],
                        'account_chart_category_id' => $record['account_chart_category_id'] ?: null, // Set null jika kosong
                    ];

                    $updatedRows = DB::table($tableName)->where('account_code', $codeBefore)->update($updateData);

                    if ($updatedRows > 0) {
                        $this->command->info("Memperbarui akun: {$codeBefore} -> {$record['account_code_after']}");
                        $updateCount++;
                    }
                }
            }

            // Jika semua proses berhasil, simpan perubahan ke database.
            DB::commit();
            $this->command->info('----------------------------------------------------');
            $this->command->info("Proses pembaruan dan penghapusan COA selesai.");
            $this->command->info("Total akun diperbarui: {$updateCount}");
            $this->command->info("Total akun dihapus: {$deleteCount}");
        } catch (Exception $e) {
            // Jika terjadi error, batalkan semua perubahan.
            DB::rollBack();
            $this->command->error("Terjadi kesalahan, semua perubahan telah dibatalkan.");
            $this->command->error("Pesan Error: " . $e->getMessage());
        }
    }
}
