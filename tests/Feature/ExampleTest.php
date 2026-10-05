<?php

namespace Tests\Feature;

use App\Models\KomoditasHarga;
use App\Models\KelompokKomoditas;
use App\Models\LaporanHarga;
use App\Models\LaporanStok;
use App\Models\Pasar;
use App\Models\Permission;
use App\Models\PeriodeSurveyHarga;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_home_is_available_and_dashboard_requires_login(): void
    {
        $this->get('/')->assertOk()
            ->assertSee('Sistem Informasi Digital Integrasi Perdagangan')
            ->assertSee('Bidang Perdagangan')
            ->assertSee('href="'.route('market-data.index').'"', false)
            ->assertSee('href="'.route('secretariat.dashboard').'"', false);
        $this->get('/dashboard')->assertRedirect('/login');
        $this->get('/login')->assertOk()->assertSee('SIDAGANG')->assertSee('Coba akun demo')->assertSee('data-demo-user="admin"', false)->assertDontSee('data-demo-user="petugasbidangpasar"', false);
        $this->get('/login?service=market')->assertOk()->assertSee('Masuk Bidang Pasar')->assertSee('data-demo-user="admin"', false)->assertSee('data-demo-user="validator"', false)->assertSee('data-demo-user="petugasbidangpasar"', false)->assertDontSee('data-demo-user="petugasstok"', false);
    }

    public function test_demo_accounts_can_login(): void
    {
        $this->seed();
        foreach (['admin', 'validator', 'tos3000', 'petugasstok'] as $username) {
            $this->post('/login', ['username' => $username, 'password' => 'password'])->assertRedirect('/dashboard');
            $this->post('/logout')->assertRedirect('https://sidagang.appcatalog.id/');
        }
    }

    public function test_market_service_has_a_separate_demo_login_and_destination(): void
    {
        $this->seed();
        $this->get('/bidang-pasar')->assertRedirect('/login');
        $this->get('/login')->assertOk()->assertSee('Masuk Bidang Pasar')->assertSee('Petugas Bidang Pasar');
        $this->post('/login', ['username' => 'petugasbidangpasar', 'password' => 'password'])->assertRedirect('/bidang-pasar');
        $this->get('/bidang-pasar')->assertOk()->assertSee('BIDANG PASAR')->assertDontSee('HARGA BAHAN POKOK');
        $this->post('/bidang-pasar/pengelola', ['nama_perusahaan' => 'Pengelola Demo', 'alamat' => 'Batam', 'no_telepon' => '0778000001', 'kontak_person' => 'Andi', 'kapasitas_tenant' => 25])->assertRedirect();
        $this->assertDatabaseHas('pengelola_pasar', ['nama_perusahaan' => 'Pengelola Demo', 'status' => 'verified']);
        $managerId = \App\Models\PengelolaPasar::where('nama_perusahaan', 'Pengelola Demo')->value('id');
        $this->post('/bidang-pasar/pedagang', ['pengelola_pasar_id' => $managerId, 'nama_toko' => 'Toko Demo', 'penanggung_jawab' => 'Siti', 'no_telepon' => '0812000000', 'bidang_usaha' => 'Sembako'])->assertRedirect();
        $this->assertDatabaseHas('pedagang_pasar', ['nama_toko' => 'Toko Demo', 'status' => 'verified']);
    }

    public function test_admin_can_login_and_open_core_pages(): void
    {
        $this->seed();
        $this->assertSame(19, KomoditasHarga::where('is_active', true)->count());
        $this->assertSame(0, LaporanHarga::count());
        $this->assertSame(0, LaporanStok::count());
        $this->post('/login', ['username' => 'admin', 'password' => 'password'])->assertRedirect('/dashboard');
        $this->get('/dashboard')->assertOk()->assertSee('Kondisi Bahan Pokok Kota Batam');
        $this->get('/harga')->assertOk()->assertSee('Monitoring Laporan Harga');
        $this->get('/stok')->assertOk()
            ->assertSee('Monitoring Stok Mingguan')
            ->assertSee('stockSearchSuggestions')
            ->assertSee('Cari distributor, contact person, alamat, atau petugas...');
        $this->get('/stok/input')->assertOk()
            ->assertDontSee('1.656,000')
            ->assertSee('mobile-stock-actions')
            ->assertSee('Nama Distributor')
            ->assertSee('Contact Person')
            ->assertSee('Alamat');
        $this->get('/harga/input')->assertOk()
            ->assertSee('mobile-actions')
            ->assertSee('Progress pengisian')
            ->assertSee('type="date" name="date"', false)
            ->assertSee('value="'.now()->toDateString().'"', false);
        $this->get('/master')->assertOk()->assertSee('Master Data SIDAGANG');
        $this->get('/laporan/bulanan')->assertOk()
            ->assertSee('Rekap Bulanan Harga')
            ->assertSee('Tanggal Awal')
            ->assertSee('Tanggal Akhir');
        $this->get('/laporan/stok')->assertOk()
            ->assertSee('Rekap Bulanan Stok')
            ->assertSee('Tanggal Awal')
            ->assertSee('Tanggal Akhir');
        $this->get('/laporan/bulanan/csv?start_date=2026-09-01&end_date=2026-09-30')->assertOk();
        $this->get('/laporan/stok/csv?start_date=2026-09-01&end_date=2026-09-30')->assertOk();
        $this->get('/bidang-pasar')->assertOk()->assertSee('Data Pengelola dan Pedagang Pasar')->assertSee('Tambah Data Pengelola')->assertSee('Pilih Bidang Layanan')->assertDontSee('HARGA BAHAN POKOK');
        $this->post('/bidang-pasar/pengelola', ['nama_perusahaan' => 'PT Pasar Batam', 'alamat' => 'Batam Centre', 'no_telepon' => '0778123456', 'kontak_person' => 'Budi', 'kapasitas_tenant' => 100])->assertRedirect();
        $managerId = \App\Models\PengelolaPasar::where('nama_perusahaan', 'PT Pasar Batam')->value('id');
        $this->post('/bidang-pasar/pedagang', ['pengelola_pasar_id' => $managerId, 'nama_toko' => 'Toko Sejahtera', 'penanggung_jawab' => 'Siti', 'no_telepon' => '08123456789', 'bidang_usaha' => 'Sembako', 'nomor_kios' => 'A-01'])->assertRedirect();
        $this->assertDatabaseHas('pengelola_pasar', ['nama_perusahaan' => 'PT Pasar Batam', 'status' => 'verified']);
        $this->assertDatabaseHas('pedagang_pasar', ['nama_toko' => 'Toko Sejahtera', 'status' => 'verified']);
        $merchantId = \App\Models\PedagangPasar::where('nama_toko', 'Toko Sejahtera')->value('id');
        $this->get('/bidang-pasar?edit_manager='.$managerId)->assertOk()->assertSee('Edit Data Pengelola')->assertSee('PT Pasar Batam');
        $this->put('/bidang-pasar/pengelola/'.$managerId, ['nama_perusahaan' => 'PT Pasar Batam Baru', 'alamat' => 'Batam Centre', 'no_telepon' => '0778123456', 'kontak_person' => 'Budi', 'kapasitas_tenant' => 110])->assertRedirect('/bidang-pasar');
        $this->get('/bidang-pasar?edit_merchant='.$merchantId)->assertOk()->assertSee('Edit Data Pedagang')->assertSee('Toko Sejahtera');
        $this->put('/bidang-pasar/pedagang/'.$merchantId, ['pengelola_pasar_id' => $managerId, 'nama_toko' => 'Toko Sejahtera Baru', 'penanggung_jawab' => 'Siti', 'no_telepon' => '08123456789', 'bidang_usaha' => 'Sembako', 'nomor_kios' => 'A-02'])->assertRedirect('/bidang-pasar');
        $this->assertDatabaseHas('pengelola_pasar', ['id' => $managerId, 'nama_perusahaan' => 'PT Pasar Batam Baru']);
        $this->assertDatabaseHas('pedagang_pasar', ['id' => $merchantId, 'nama_toko' => 'Toko Sejahtera Baru']);
        $this->get('/bidang-pasar?q=Sejahtera')->assertOk()->assertSee('Toko Sejahtera');
        $this->get('/bidang-pasar/export/excel')->assertOk()->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $this->get('/bidang-pasar/dashboard')->assertOk()->assertSee('Ikhtisar Bidang Pasar');
        $this->get('/bidang-pasar/master')->assertOk()->assertSee('Master Data Bidang Pasar');
        $this->post('/bidang-pasar/master/bidang-usaha', ['nama' => 'Elektronik'])->assertRedirect();
        $this->assertDatabaseHas('bidang_usaha_pasar', ['nama' => 'Elektronik']);
        $this->get('/bidang-pasar/akun')->assertOk()->assertSee('Akun Petugas Bidang Pasar');
        $this->post('/bidang-pasar/akun', ['name' => 'Petugas Pasar Baru', 'username' => 'petugasbaru', 'email' => 'petugasbaru@example.test', 'password' => 'password123', 'password_confirmation' => 'password123'])->assertRedirect();
        $this->assertDatabaseHas('users', ['username' => 'petugasbaru', 'is_active' => true]);
        $this->getJson('/api/dashboard')->assertOk()->assertJsonStructure(['tanggal', 'pasar_melapor', 'pasar_total']);
        $this->getJson('/api/harga/latest')->assertOk()->assertJsonStructure(['date', 'data']);
        $this->getJson('/api/harga/trend')->assertOk();
        $this->getJson('/api/harga/compare')->assertOk();
        $this->getJson('/api/stok/latest')->assertOk()->assertJsonStructure(['date', 'data']);
        $this->getJson('/api/stok/trend')->assertOk();
    }

    public function test_role_access_and_navigation_are_enforced(): void
    {
        $this->seed();

        $stockOfficer = User::where('username', 'petugasstok')->firstOrFail();
        $this->actingAs($stockOfficer);
        $this->get('/dashboard')->assertRedirect('/stok');
        $this->get('/stok')->assertOk()->assertSee('STOK BAHAN POKOK')->assertDontSee('HARGA BAHAN POKOK');
        $this->get('/stok/input')->assertOk();
        $this->get('/harga')->assertForbidden();
        $this->get('/harga/input')->assertForbidden();
        $this->getJson('/api/harga/latest')->assertForbidden();

        $marketOfficer = User::where('username', 'tos3000')->firstOrFail();
        $this->actingAs($marketOfficer);
        $this->get('/dashboard')->assertRedirect('/harga');
        $this->get('/harga')->assertOk()->assertSee('HARGA BAHAN POKOK')->assertDontSee('STOK BAHAN POKOK');
        $this->get('/harga/input')->assertOk();
        $this->get('/stok')->assertForbidden();
        $this->get('/stok/input')->assertForbidden();
        $this->getJson('/api/stok/latest')->assertForbidden();

        $validator = User::where('username', 'validator')->firstOrFail();
        $this->actingAs($validator);
        $this->get('/harga')->assertOk();
        $this->get('/stok')->assertOk();
        $this->get('/harga/input')->assertForbidden();
        $this->get('/stok/input')->assertForbidden();
        $this->get('/master')->assertForbidden();
        $this->get('/laporan/bulanan')->assertOk();
    }

    public function test_price_report_can_be_saved_as_draft(): void
    {
        $this->seed();
        $officer = User::where('username', 'tos3000')->firstOrFail();
        $market = Pasar::where('kode', 'TOS3000')->firstOrFail();
        $period = PeriodeSurveyHarga::create(['tanggal' => now()->addDay()->toDateString(), 'created_by' => User::where('username', 'admin')->value('id')]);
        $this->actingAs($officer)->post('/harga', ['tanggal' => $period->tanggal->format('Y-m-d'), 'pasar_id' => $market->id, 'intent' => 'draft', 'harga' => [], 'status_data' => []])->assertRedirect('/harga');
        $this->assertTrue(LaporanHarga::whereDate('tanggal', $period->tanggal)->where('pasar_id', $market->id)->where('status', 'draft')->exists());
    }

    public function test_existing_price_report_with_datetime_value_can_be_submitted(): void
    {
        $this->seed();
        $officer = User::where('username', 'tos3000')->firstOrFail();
        $market = Pasar::where('kode', 'TOS3000')->firstOrFail();
        $commodity = KomoditasHarga::where('is_active', true)->firstOrFail();
        $date = now()->addDays(2)->toDateString();
        $report = LaporanHarga::create([
            'tanggal' => $date.' 00:00:00',
            'pasar_id' => $market->id,
            'petugas_id' => $officer->id,
            'status' => 'rejected',
        ]);

        $this->actingAs($officer)->post('/harga', [
            'tanggal' => $date,
            'pasar_id' => $market->id,
            'intent' => 'submit',
            'harga' => [$commodity->id => 15000],
            'status_data' => [$commodity->id => 'available'],
        ])->assertRedirect('/harga');

        $this->assertSame(1, LaporanHarga::whereDate('tanggal', $date)->where('pasar_id', $market->id)->count());
        $this->assertDatabaseHas('laporan_harga', ['id' => $report->id, 'status' => 'submitted']);
        $this->assertDatabaseHas('laporan_harga_detail', [
            'laporan_harga_id' => $report->id,
            'komoditas_harga_id' => $commodity->id,
            'harga' => 15000,
            'status_data' => 'available',
        ]);
    }

    public function test_submitted_price_report_cannot_be_resubmitted_and_does_not_return_422(): void
    {
        $this->seed();
        $officer = User::where('username', 'tos3000')->firstOrFail();
        $market = Pasar::where('kode', 'TOS3000')->firstOrFail();
        $date = now()->addDays(3)->toDateString();
        $report = LaporanHarga::create([
            'tanggal' => $date,
            'pasar_id' => $market->id,
            'petugas_id' => $officer->id,
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        $this->actingAs($officer)->post('/harga', [
            'tanggal' => $date,
            'pasar_id' => $market->id,
            'intent' => 'submit',
            'harga' => [],
            'status_data' => [],
        ])->assertRedirect('/harga/'.$report->id)
            ->assertSessionHas('success', 'Laporan ini sudah pernah dikirim dan tidak diproses ulang.');

        $this->assertDatabaseHas('laporan_harga', ['id' => $report->id, 'status' => 'submitted']);
    }

    public function test_admin_can_complete_master_data_crud_safely(): void
    {
        $this->seed();
        $admin = User::where('username', 'admin')->firstOrFail();

        $this->actingAs($admin)->get('/master?tab=group')
            ->assertOk()
            ->assertSee('Kelompok Komoditas')
            ->assertSee('Tambah Petugas / Pengguna');

        $this->actingAs($admin)->post('/master/market', [
            'kode' => 'UJI01',
            'nama' => 'Pasar Pengujian',
            'kecamatan' => 'Batam Kota',
            'alamat' => 'Alamat pengujian',
        ])->assertRedirect('/master?tab=market');

        $market = Pasar::where('kode', 'UJI01')->firstOrFail();
        $this->actingAs($admin)->patch("/master/market/{$market->id}", [
            'kode' => 'UJI01',
            'nama' => 'Pasar Pengujian Diperbarui',
            'kecamatan' => 'Batam Kota',
        ])->assertRedirect('/master?tab=market');
        $this->assertDatabaseHas('pasar', ['id' => $market->id, 'nama' => 'Pasar Pengujian Diperbarui']);

        $this->actingAs($admin)->patch("/master/market/{$market->id}/toggle")
            ->assertRedirect('/master?tab=market');
        $this->assertDatabaseHas('pasar', ['id' => $market->id, 'is_active' => false]);

        $this->actingAs($admin)->delete("/master/market/{$market->id}")
            ->assertRedirect('/master?tab=market');
        $this->assertDatabaseMissing('pasar', ['id' => $market->id]);

        $this->actingAs($admin)->post('/master/group', ['kode' => 'UJI-KLP', 'nama' => 'Kelompok Uji'])
            ->assertRedirect('/master?tab=group');
        $group = KelompokKomoditas::where('kode', 'UJI-KLP')->firstOrFail();
        $this->actingAs($admin)->delete("/master/group/{$group->id}")
            ->assertRedirect('/master?tab=group');
        $this->assertDatabaseMissing('kelompok_komoditas', ['id' => $group->id]);
    }

    public function test_used_master_data_cannot_be_deleted_and_officer_crud_works(): void
    {
        $this->seed();
        $admin = User::where('username', 'admin')->firstOrFail();
        $usedMarket = Pasar::where('kode', 'TOS3000')->firstOrFail();

        $this->actingAs($admin)->delete("/master/market/{$usedMarket->id}")
            ->assertSessionHasErrors('hapus');
        $this->assertDatabaseHas('pasar', ['id' => $usedMarket->id]);

        $stockRole = Role::where('name', 'petugas_stok')->firstOrFail();
        $this->actingAs($admin)->post('/master/officer', [
            'name' => 'Petugas Pengujian',
            'username' => 'petugasuji',
            'email' => 'petugasuji@example.test',
            'role_id' => $stockRole->id,
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
        ])->assertRedirect('/master?tab=officer');

        $officer = User::where('username', 'petugasuji')->firstOrFail();
        $this->actingAs($admin)->patch("/master/officer/{$officer->id}", [
            'name' => 'Petugas Uji Diperbarui',
            'username' => 'petugasuji',
            'email' => 'petugasuji@example.test',
            'role_id' => $stockRole->id,
        ])->assertRedirect('/master?tab=officer');
        $this->assertDatabaseHas('users', ['id' => $officer->id, 'name' => 'Petugas Uji Diperbarui']);

        $this->actingAs($admin)->delete("/master/officer/{$officer->id}")
            ->assertRedirect('/master?tab=officer');
        $this->assertDatabaseMissing('users', ['id' => $officer->id]);
    }

    public function test_administrator_can_create_account_and_manage_role_permissions(): void
    {
        $this->seed();
        $admin = User::where('username', 'admin')->firstOrFail();
        $kabidRole = Role::where('name', 'kabid')->firstOrFail();

        $this->actingAs($admin)->get('/administrator')
            ->assertOk()
            ->assertSee('Buat Akun Baru')
            ->assertSee('Matriks Hak Akses');

        $this->actingAs($admin)->post('/administrator/accounts', [
            'name' => 'Kepala Bidang Baru',
            'username' => 'kabidbaru',
            'email' => 'kabidbaru@example.test',
            'role_id' => $kabidRole->id,
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
        ])->assertRedirect('/administrator?tab=accounts');
        $this->assertDatabaseHas('users', ['username' => 'kabidbaru', 'role_id' => $kabidRole->id]);

        $selectedPermissions = Permission::whereIn('name', ['dashboard.view', 'reports.view'])->pluck('id')->all();
        $this->actingAs($admin)->put("/administrator/roles/{$kabidRole->id}/permissions", [
            'permissions' => $selectedPermissions,
        ])->assertRedirect('/administrator?tab=access');
        $this->assertEqualsCanonicalizing($selectedPermissions, $kabidRole->permissions()->pluck('permissions.id')->all());
    }

    public function test_permission_changes_are_enforced_on_routes_and_navigation(): void
    {
        $this->seed();
        $stockOfficer = User::where('username', 'petugasstok')->firstOrFail();
        $priceView = Permission::where('name', 'prices.view')->firstOrFail();
        $dashboardView = Permission::where('name', 'dashboard.view')->firstOrFail();
        $stockOfficer->role->permissions()->sync([$dashboardView->id, $priceView->id]);
        $stockOfficer->unsetRelation('role');

        $this->actingAs($stockOfficer)->get('/harga')
            ->assertOk()
            ->assertSee('HARGA BAHAN POKOK')
            ->assertDontSee('STOK BAHAN POKOK');
        $this->actingAs($stockOfficer)->get('/stok')->assertForbidden();
        $this->actingAs($stockOfficer)->get('/administrator')->assertForbidden();
    }
}
