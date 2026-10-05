<?php

namespace Database\Seeders;

use App\Models\PedagangPasar;
use App\Models\PengelolaPasar;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BidangPasarDemoSeeder extends Seeder
{
    public function run(): void
    {
        $actor = User::where('username', 'admin')->firstOrFail();
        $data = [
            [
                ['nama_perusahaan' => 'PT Pengelola Pasar Batam Centre', 'alamat' => 'Batam Centre, Kota Batam', 'no_telepon' => '0778123401', 'kontak_person' => 'Andi Saputra', 'kapasitas_tenant' => 120],
                ['nama_toko' => 'Toko Sembako Sejahtera', 'penanggung_jawab' => 'Siti Rahma', 'no_telepon' => '081234567801', 'bidang_usaha' => 'Sembako', 'nomor_kios' => 'A-01', 'alamat' => 'Blok A Pasar Batam Centre'],
            ],
            [
                ['nama_perusahaan' => 'Koperasi Pengelola Pasar Jodoh', 'alamat' => 'Jodoh, Batu Ampar, Kota Batam', 'no_telepon' => '0778123402', 'kontak_person' => 'Budi Hartono', 'kapasitas_tenant' => 85],
                ['nama_toko' => 'Kios Sayur Segar Jodoh', 'penanggung_jawab' => 'Nur Aisyah', 'no_telepon' => '081234567802', 'bidang_usaha' => 'Sayur dan Buah', 'nomor_kios' => 'B-12', 'alamat' => 'Blok B Pasar Jodoh'],
            ],
            [
                ['nama_perusahaan' => 'CV Pengelola Pasar Batu Aji', 'alamat' => 'Batu Aji, Kota Batam', 'no_telepon' => '0778123403', 'kontak_person' => 'Rudi Kurniawan', 'kapasitas_tenant' => 100],
                ['nama_toko' => 'Kios Ikan Laut Berkah', 'penanggung_jawab' => 'Ahmad Fauzi', 'no_telepon' => '081234567803', 'bidang_usaha' => 'Ikan dan Hasil Laut', 'nomor_kios' => 'C-07', 'alamat' => 'Blok C Pasar Batu Aji'],
            ],
        ];

        DB::transaction(function () use ($data, $actor): void {
            foreach ($data as [$managerData, $merchantData]) {
                $manager = PengelolaPasar::updateOrCreate(
                    ['nama_perusahaan' => $managerData['nama_perusahaan']],
                    $managerData + ['status' => 'verified', 'is_active' => true, 'created_by' => $actor->id, 'verified_by' => $actor->id, 'verified_at' => now()]
                );
                PedagangPasar::updateOrCreate(
                    ['pengelola_pasar_id' => $manager->id, 'nama_toko' => $merchantData['nama_toko'], 'no_telepon' => $merchantData['no_telepon']],
                    $merchantData + ['status' => 'verified', 'is_active' => true, 'created_by' => $actor->id, 'verified_by' => $actor->id, 'verified_at' => now()]
                );
            }
        });
    }
}
