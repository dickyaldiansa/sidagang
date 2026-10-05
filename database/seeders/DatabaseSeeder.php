<?php

namespace Database\Seeders;

use App\Models\KelompokKomoditas;
use App\Models\Pasar;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Satuan;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $roles = collect([
            ['super_admin', 'Super Administrator'], ['admin', 'Admin Disperindag'], ['petugas_pasar', 'Petugas Pasar'],
            ['petugas_stok', 'Petugas Stok'], ['petugas_bidang_pasar', 'Petugas Bidang Pasar'], ['petugas_industri', 'Petugas Bidang Perindustrian'], ['petugas_metrologi', 'Petugas Tertib Niaga & Metrologi'], ['petugas_sekretariat', 'Petugas Sekretariat'], ['validator', 'Validator'], ['kabid', 'Kepala Bidang'], ['kepala_dinas', 'Kepala Dinas'],
        ])->mapWithKeys(fn ($r) => [$r[0] => Role::updateOrCreate(['name' => $r[0]], ['label' => $r[1], 'description' => $r[1]])]);

        $permissionDefinitions = [
            ['dashboard.view', 'Lihat Dashboard', 'Dashboard'],
            ['prices.view', 'Lihat Harga', 'Harga'], ['prices.create', 'Input Harga', 'Harga'], ['prices.verify', 'Verifikasi Harga', 'Harga'],
            ['stocks.view', 'Lihat Stok', 'Stok'], ['stocks.create', 'Input Stok', 'Stok'], ['stocks.verify', 'Verifikasi Stok', 'Stok'],
            ['reports.view', 'Lihat dan Export Laporan', 'Laporan'],
            ['market_data.view', 'Lihat Data Bidang Pasar', 'Bidang Pasar'], ['market_data.create', 'Input Data Bidang Pasar', 'Bidang Pasar'],
            ['market_data.verify', 'Verifikasi Data Bidang Pasar', 'Bidang Pasar'], ['market_data.export', 'Export Data Bidang Pasar', 'Bidang Pasar'],
            ['market_data.manage', 'Kelola Administrasi Bidang Pasar', 'Bidang Pasar'],
            ['industry.view', 'Lihat Data Industri', 'Bidang Perindustrian'], ['industry.create', 'Kelola Data IKM', 'Bidang Perindustrian'], ['industry.export', 'Export Data Industri', 'Bidang Perindustrian'], ['industry.manage', 'Kelola Administrasi Industri', 'Bidang Perindustrian'],
            ['metrology.view', 'Lihat Data Metrologi', 'Tertib Niaga & Metrologi'], ['metrology.create', 'Kelola Data Metrologi', 'Tertib Niaga & Metrologi'], ['metrology.export', 'Ekspor Data Metrologi', 'Tertib Niaga & Metrologi'], ['metrology.manage', 'Kelola Administrasi Metrologi', 'Tertib Niaga & Metrologi'],
            ['secretariat.view', 'Lihat Data Sekretariat', 'Sekretariat'], ['secretariat.create', 'Kelola Data Sekretariat', 'Sekretariat'], ['secretariat.export', 'Ekspor Data Sekretariat', 'Sekretariat'], ['secretariat.manage', 'Kelola Administrasi Sekretariat', 'Sekretariat'],
            ['master.manage', 'Kelola Master Data', 'Administrasi'], ['users.manage', 'Kelola Akun', 'Administrasi'], ['roles.manage', 'Kelola Hak Akses', 'Administrasi'],
        ];
        $permissions = collect($permissionDefinitions)->mapWithKeys(fn ($permission) => [
            $permission[0] => Permission::updateOrCreate(['name' => $permission[0]], ['label' => $permission[1], 'module' => $permission[2], 'is_active' => true]),
        ]);
        $allPermissions = $permissions->pluck('id')->all();
        $roles['super_admin']->permissions()->sync($allPermissions);
        $roles['admin']->permissions()->sync($allPermissions);
        $roles['petugas_pasar']->permissions()->sync($permissions->only(['dashboard.view', 'prices.view', 'prices.create', 'market_data.view', 'market_data.create'])->pluck('id'));
        $roles['petugas_stok']->permissions()->sync($permissions->only(['dashboard.view', 'stocks.view', 'stocks.create'])->pluck('id'));
        $roles['petugas_bidang_pasar']->permissions()->sync($permissions->only(['market_data.view', 'market_data.create'])->pluck('id'));
        $roles['petugas_industri']->permissions()->sync($permissions->only(['industry.view', 'industry.create', 'industry.export'])->pluck('id'));
        $roles['petugas_metrologi']->permissions()->sync($permissions->only(['metrology.view', 'metrology.create', 'metrology.export'])->pluck('id'));
        $roles['petugas_sekretariat']->permissions()->sync($permissions->only(['secretariat.view', 'secretariat.create', 'secretariat.export'])->pluck('id'));
        $roles['validator']->permissions()->sync($permissions->only(['dashboard.view', 'prices.view', 'prices.verify', 'stocks.view', 'stocks.verify', 'reports.view', 'market_data.view', 'market_data.verify', 'market_data.export'])->pluck('id'));
        $leadershipPermissions = $permissions->only(['dashboard.view', 'prices.view', 'stocks.view', 'reports.view', 'market_data.view', 'market_data.export'])->pluck('id');
        $roles['kabid']->permissions()->sync($leadershipPermissions);
        $roles['kepala_dinas']->permissions()->sync($leadershipPermissions);

        $markets = collect([
            ['TOS3000', 'Pasar Tos 3000'], ['PUJABAHARI', 'Pasar Pujabahari'],
            ['MEGALEGENDA', 'Pasar Mega Legenda'], ['BOTANIA2', 'Pasar Botania 2'],
        ])->map(fn ($m, $i) => Pasar::create(['kode' => $m[0], 'nama' => $m[1], 'urutan' => $i + 1]));
        $units = collect(['kg' => 'Kilogram', 'ton' => 'Ton', 'ikat' => 'Ikat', 'liter' => 'Liter', 'papan' => 'Papan', 'ekor' => 'Ekor', 'bks' => 'Bungkus', 'gr' => 'Gram'])
            ->mapWithKeys(fn ($nama, $kode) => [$kode => Satuan::create(compact('kode', 'nama'))]);
        collect(['BERAS', 'KEDELAI', 'CABAI', 'BAWANG', 'GULA', 'MINYAK', 'TELUR', 'DAGING', 'IKAN', 'SAYURAN', 'BUAH', 'KACANG', 'LAINNYA'])
            ->mapWithKeys(fn ($nama, $i) => [$nama => KelompokKomoditas::create(['kode' => $nama, 'nama' => ucwords(strtolower($nama)), 'urutan' => $i + 1])]);

        User::create(['name' => 'Administrator SIDAGANG', 'username' => 'admin', 'email' => 'admin@sidagang.test', 'password' => Hash::make('password'), 'role_id' => $roles['super_admin']->id]);
        User::create(['name' => 'Validator Disperindag', 'username' => 'validator', 'email' => 'validator@sidagang.test', 'password' => Hash::make('password'), 'role_id' => $roles['validator']->id]);
        $markets->each(fn ($m) => User::create(['name' => 'Petugas '.$m->nama, 'username' => strtolower($m->kode), 'email' => strtolower($m->kode).'@sidagang.test', 'password' => Hash::make('password'), 'role_id' => $roles['petugas_pasar']->id, 'pasar_id' => $m->id]));
        User::create(['name' => 'Petugas Stok', 'username' => 'petugasstok', 'email' => 'stok@sidagang.test', 'password' => Hash::make('password'), 'role_id' => $roles['petugas_stok']->id]);
        User::updateOrCreate(['username' => 'petugasbidangpasar'], ['name' => 'Petugas Bidang Pasar', 'email' => 'bidangpasar@sidagang.test', 'password' => Hash::make('password'), 'role_id' => $roles['petugas_bidang_pasar']->id, 'is_active' => true]);
        User::updateOrCreate(['username' => 'petugasindustri'], ['name' => 'Petugas Bidang Perindustrian', 'email' => 'industri@sidagang.test', 'password' => Hash::make('password'), 'role_id' => $roles['petugas_industri']->id, 'is_active' => true]);
        User::updateOrCreate(['username' => 'petugasmetrologi'], ['name' => 'Petugas Tertib Niaga dan Metrologi', 'email' => 'metrologi@sidagang.test', 'password' => Hash::make('password'), 'role_id' => $roles['petugas_metrologi']->id, 'is_active' => true]);
        User::updateOrCreate(['username' => 'petugassekretariat'], ['name' => 'Petugas Sekretariat', 'email' => 'sekretariat@sidagang.test', 'password' => Hash::make('password'), 'role_id' => $roles['petugas_sekretariat']->id, 'is_active' => true]);

        DB::table('settings')->insert([['key' => 'price_anomaly_threshold', 'value' => '20', 'type' => 'number', 'created_at' => now(), 'updated_at' => now()], ['key' => 'stock_warning_threshold', 'value' => '20', 'type' => 'number', 'created_at' => now(), 'updated_at' => now()]]);

        $this->call(KomoditasHargaSeeder::class);
        $this->call(BidangPasarDemoSeeder::class);
    }
}
