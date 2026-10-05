<?php

namespace App\Http\Controllers;

use App\Models\Industri;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\{Alignment, Border, Fill};
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class IndustryController extends Controller
{
    private array $masters = ['jenis_ikm' => 'Jenis IKM', 'skala_industri' => 'Skala', 'jenis_produk_industri' => 'Jenis Produk', 'legalitas_industri' => 'Legalitas'];

    public function dashboard()
    {
        return view('industry.dashboard', ['total' => Industri::count(), 'aktif' => Industri::where('is_active', true)->count(), 'tenagaKerja' => Industri::sum('jumlah_tenaga_kerja'), 'omset' => Industri::sum('omset'), 'recent' => Industri::latest()->limit(5)->get()]);
    }

    public function index(Request $request)
    {
        $q = trim((string) $request->q);
        $rows = Industri::when($q, fn ($x) => $x->where(fn ($y) => $y->where('nama_ikm', 'like', "%$q%")->orWhere('penanggung_jawab', 'like', "%$q%")->orWhere('jenis_produk', 'like', "%$q%")->orWhere('alamat', 'like', "%$q%")))->latest()->paginate(12)->withQueryString();
        $edit = $request->filled('edit') ? Industri::findOrFail($request->integer('edit')) : null;
        $options = collect($this->masters)->mapWithKeys(fn ($label, $table) => [$table => DB::table($table)->where('is_active', true)->orderBy('nama')->pluck('nama')]);
        return view('industry.index', compact('rows', 'edit', 'options', 'q'));
    }

    public function store(Request $request) { Industri::create($this->data($request) + ['created_by' => $request->user()->id]); return back()->with('success', 'Data IKM berhasil disimpan.'); }
    public function update(Request $request, Industri $industry) { $industry->update($this->data($request, $industry->id)); return redirect()->route('industry.index')->with('success', 'Data IKM berhasil diperbarui.'); }
    public function destroy(Industri $industry) { $industry->delete(); return back()->with('success', 'Data IKM berhasil dihapus.'); }

    public function master()
    {
        $groups = collect($this->masters)->mapWithKeys(fn ($label, $table) => [$table => ['label' => $label, 'rows' => DB::table($table)->orderBy('nama')->get()]]);
        return view('industry.master', compact('groups'));
    }
    public function storeMaster(Request $request, string $type) { $this->masterTable($type); $data=$request->validate(['nama'=>['required','string','max:150',Rule::unique($type)]]); DB::table($type)->insert($data+['is_active'=>true,'created_at'=>now(),'updated_at'=>now()]); return back()->with('success','Master data berhasil ditambahkan.'); }
    public function updateMaster(Request $request, string $type, int $id) { $this->masterTable($type); $data=$request->validate(['nama'=>['required','string','max:150',Rule::unique($type)->ignore($id)]]); DB::table($type)->where('id',$id)->update($data+['updated_at'=>now()]); return back()->with('success','Master data berhasil diperbarui.'); }
    public function toggleMaster(string $type, int $id) { $this->masterTable($type); $row=DB::table($type)->find($id); abort_unless($row,404); DB::table($type)->where('id',$id)->update(['is_active'=>!$row->is_active,'updated_at'=>now()]); return back()->with('success','Status master data diperbarui.'); }

    public function accounts() { $role=Role::where('name','petugas_industri')->firstOrFail(); return view('industry.accounts',['users'=>User::where('role_id',$role->id)->orderBy('name')->get()]); }
    public function storeAccount(Request $request) { $data=$request->validate(['name'=>'required|string|max:150','username'=>'required|alpha_dash|max:100|unique:users','email'=>'nullable|email|max:150|unique:users','password'=>'required|string|min:8|confirmed']); User::create($data+['role_id'=>Role::where('name','petugas_industri')->value('id'),'is_active'=>true]); return back()->with('success','Akun petugas industri berhasil dibuat.'); }
    public function updateAccount(Request $request, User $user) { abort_unless($user->role?->name==='petugas_industri',404); $data=$request->validate(['name'=>'required|string|max:150','username'=>['required','alpha_dash','max:100',Rule::unique('users')->ignore($user->id)],'email'=>['nullable','email','max:150',Rule::unique('users')->ignore($user->id)],'password'=>'nullable|string|min:8|confirmed']); if(empty($data['password']))unset($data['password']); $user->update($data); return back()->with('success','Akun petugas diperbarui.'); }
    public function toggleAccount(User $user) { abort_unless($user->role?->name==='petugas_industri',404); $user->update(['is_active'=>!$user->is_active]); return back()->with('success','Status akun diperbarui.'); }

    public function excel()
    {
        $book=new Spreadsheet; $sheet=$book->getActiveSheet(); $sheet->setTitle('Data IKM');
        $sheet->fromArray(['REKAP DATA INDUSTRI KECIL DAN MENENGAH'],null,'A1');
        $heads=['No','Jenis IKM','Skala','Nama IKM','Penanggung Jawab','Alamat','Telepon','Jenis Produk','Modal Usaha','Omset','Jumlah Tenaga Kerja','Legalitas yang Dimiliki','Keterangan']; $sheet->fromArray($heads,null,'A3');
        foreach(Industri::orderBy('nama_ikm')->get() as $i=>$r){$n=$i+4;$sheet->fromArray([$i+1,$r->jenis_ikm,$r->skala,$r->nama_ikm,$r->penanggung_jawab,$r->alamat,null,$r->jenis_produk,$r->modal_usaha,$r->omset,$r->jumlah_tenaga_kerja,$r->legalitas,$r->keterangan],null,"A$n");$sheet->setCellValueExplicit("G$n",(string)$r->telepon,DataType::TYPE_STRING);}
        $last=max(3,Industri::count()+3);$sheet->mergeCells('A1:M1');$sheet->getStyle('A1:M1')->applyFromArray(['font'=>['bold'=>true,'size'=>16,'color'=>['rgb'=>'FFFFFF']],'fill'=>['fillType'=>Fill::FILL_SOLID,'startColor'=>['rgb'=>'5B3F9B']],'alignment'=>['horizontal'=>Alignment::HORIZONTAL_CENTER]]);$sheet->getStyle('A3:M3')->applyFromArray(['font'=>['bold'=>true,'color'=>['rgb'=>'FFFFFF']],'fill'=>['fillType'=>Fill::FILL_SOLID,'startColor'=>['rgb'=>'7857C5']],'alignment'=>['horizontal'=>Alignment::HORIZONTAL_CENTER,'wrapText'=>true]]);$sheet->getStyle("A3:M$last")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);foreach(range('A','M') as $c)$sheet->getColumnDimension($c)->setAutoSize(true);$sheet->freezePane('A4');$sheet->setAutoFilter("A3:M$last");
        return response()->streamDownload(fn()=>(new Xlsx($book))->save('php://output'),'data-bidang-industri-'.now()->format('Y-m-d').'.xlsx',['Content-Type'=>'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    private function data(Request $request, ?int $id=null): array { return $request->validate(['jenis_ikm'=>'required|string|max:150','skala'=>'required|string|max:150','nama_ikm'=>['required','string','max:255',Rule::unique('data_ikm')->where(fn($q)=>$q->where('telepon',$request->telepon))->ignore($id)],'penanggung_jawab'=>'required|string|max:255','alamat'=>'required|string|max:2000','telepon'=>'required|string|max:50','jenis_produk'=>'required|string|max:255','modal_usaha'=>'nullable|numeric|min:0','omset'=>'nullable|numeric|min:0','jumlah_tenaga_kerja'=>'required|integer|min:0','legalitas'=>'nullable|string|max:2000','keterangan'=>'nullable|string|max:3000']); }
    private function masterTable(string $type): void { abort_unless(array_key_exists($type,$this->masters),404); }
}
