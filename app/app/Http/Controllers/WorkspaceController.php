<?php
namespace App\Http\Controllers;

use App\Support\Access;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class WorkspaceController extends Controller
{
    public function dashboard()
    {
        $projects = DB::table('projects')->latest('updated_at')->limit(6)->get();
        $stats = ['projects'=>DB::table('projects')->where('status','Active')->count(),'contractors'=>DB::table('contractors')->where('status','Active')->count(),'tasks'=>DB::table('tasks')->whereNot('status','Done')->count(),'overdue'=>DB::table('tasks')->whereNot('status','Done')->where('due_date','<',today())->count()];
        $tasks = DB::table('tasks')->leftJoin('projects','tasks.project_id','=','projects.id')->select('tasks.*','projects.name as project_name')->whereNot('tasks.status','Done')->orderBy('due_date')->limit(5)->get();
        $visible = array_keys(array_filter(config('construction.modules'),fn($m,$key)=>$key!=='users' && Access::can($key),ARRAY_FILTER_USE_BOTH));
        $activity = DB::table('audit_logs')->whereIn('module',$visible)->latest('id')->limit(5)->get();
        $finance = Access::can('expenses') ? $this->financialSummary() : null;
        return view('dashboard',compact('projects','stats','tasks','activity','finance'));
    }
    public function index(Request $request, string $module)
    {
        $definition = Access::check($module);
        $request->validate(['q'=>'nullable|string|max:100','status'=>'nullable|string|max:50','project_id'=>'nullable|integer']);
        $query = DB::table($module);
        if ($request->filled('q')) $query->where('name','like','%'.$request->string('q').'%');
        if ($request->filled('status') && isset($definition['fields']['status'])) $query->where('status',$request->string('status'));
        if ($request->filled('project_id') && isset($definition['fields']['project_id'])) $query->where('project_id',$request->integer('project_id'));
        $rows = $query->latest('id')->paginate(12)->withQueryString();
        if ($module === 'users') $rows->through(function ($row) { unset($row->password,$row->remember_token); return $row; });
        $lookups = $this->lookups($definition);
        return view('records.index',compact('module','definition','rows','lookups'));
    }
    public function form(string $module, ?int $id = null)
    {
        $definition = Access::check($module,'write');
        abort_if($module === 'payments' && $id,403,'Payments are immutable.');
        $row = $id ? DB::table($module)->find($id) : null; abort_if($id && ! $row,404);
        $lookups = $this->lookups($definition);
        return view('records.form',compact('module','definition','row','lookups'));
    }
    private function lookups(array $definition): array
    {
        $lookups = [];
        foreach ($definition['fields'] as $key => $field) if ($field['type'] === 'relation') $lookups[$key] = DB::table($field['table'])->orderBy('name')->pluck('name','id');
        return $lookups;
    }
    public function show(string $module, int $id)
    {
        $definition = Access::check($module); $row = DB::table($module)->find($id); abort_unless($row,404);
        $lookups = $this->lookups($definition);
        $related = [];
        if ($module === 'projects') foreach (config('construction.modules') as $key=>$item) {
            if (isset($item['fields']['project_id']) && Access::can($key)) $related[$key] = DB::table($key)->where('project_id',$id)->count();
        }
        $schedule = $module === 'projects' ? DB::table('tasks')->where('project_id',$id)->orderBy('due_date')->limit(30)->get() : collect();
        $balance = $module === 'invoices' ? bcsub((string)$row->amount,(string)DB::table('payments')->where('invoice_id',$id)->sum('amount'),2) : null;
        return view('records.show',compact('module','definition','row','lookups','related','schedule','balance'));
    }
    public function save(Request $request, string $module, ?int $id = null)
    {
        $definition = Access::check($module,'write'); abort_if($module === 'payments' && $id,403);
        $row = $id ? DB::table($module)->find($id) : null; abort_if($id && ! $row,404);
        $rules = [];
        foreach ($definition['fields'] as $key => $field) {
            $required = $field['required'] && ! ($key === 'password' && $id);
            $rules[$key] = [$required ? 'required' : 'nullable', ...match ($field['type']) {
                'email' => ['email','max:255'], 'password' => ['string','max:255',Password::min(12)->mixedCase()->numbers()->symbols()],
                'money' => ['numeric','min:0','max:999999999999.99','decimal:0,2'],
                'number' => ['integer','min:0','max:'.($field['max'] ?? 1000000)], 'date' => ['date_format:Y-m-d'],
                'relation' => ['integer',Rule::exists($field['table'],'id')], 'select' => [Rule::in($field['options'])],
                'textarea' => ['string','max:10000'], default => ['string','max:255'],
            }];
        }
        if (isset($rules['end_date'])) $rules['end_date'][] = 'after_or_equal:start_date';
        if ($module === 'users') $rules['email'][] = Rule::unique('users','email')->ignore($id);
        if ($module === 'projects') $rules['code'][] = Rule::unique('projects','code')->ignore($id);
        if ($module === 'payments') $rules['reference'][] = Rule::unique('payments','reference');
        if ($module === 'invoices') $rules['name'][] = Rule::unique('invoices','name')->where('contractor_id',$request->input('contractor_id'))->ignore($id);
        if ($module === 'documents') $rules['file'] = [$id ? 'nullable' : 'required','file','mimes:pdf,jpg,jpeg,png,webp,docx,xlsx,txt','max:10240'];
        $data = $request->validate($rules); unset($data['file']);
        if ($module === 'users') {
            if (! empty($data['password'])) $data['password'] = Hash::make($data['password']); else unset($data['password']);
            if ($id === auth()->id() && ($data['role'] !== 'admin' || (string)$data['active'] !== '1')) throw ValidationException::withMessages(['role'=>'You cannot disable or demote your own administrator account.']);
        }
        if (in_array($module,['expenses','invoices','change_orders','contracts']) && in_array($data['status'],['Approved','Rejected']) && ! in_array(auth()->user()->role,['admin','manager'])) throw ValidationException::withMessages(['status'=>'Only a manager or administrator can approve or reject this record.']);
        $newPath = null;
        if ($request->hasFile('file')) {
            $newPath = $request->file('file')->store('documents','local');
            $data['file_path'] = $newPath; $data['file_name'] = $request->file('file')->getClientOriginalName();
        }
        try {
            DB::transaction(function () use ($module,$id,$data) {
                if ($module === 'invoices' && $id) {
                    DB::table('invoices')->where('id',$id)->lockForUpdate()->first();
                    if (DB::table('payments')->where('invoice_id',$id)->exists()) throw ValidationException::withMessages(['amount'=>'An invoice with payments cannot be edited.']);
                }
                if (in_array($module,['invoices','expenses']) && $id) {
                    $existing = DB::table($module)->where('id',$id)->lockForUpdate()->first();
                    if ($existing->status === 'Approved' && ! in_array(auth()->user()->role,['admin','manager'])) throw ValidationException::withMessages(['status'=>'An approved record is locked. Ask a manager to review it.']);
                }
                if ($module === 'payments') {
                    $invoice = DB::table('invoices')->where('id',$data['invoice_id'])->lockForUpdate()->first();
                    $paid = DB::table('payments')->where('invoice_id',$invoice->id)->sum('amount');
                    $remaining = bcsub((string)$invoice->amount,(string)$paid,2);
                    if ($invoice->status !== 'Approved' || bccomp((string)$data['amount'],'0',2) <= 0 || bccomp((string)$data['amount'],$remaining,2) > 0) throw ValidationException::withMessages(['amount'=>'Enter a positive amount within the remaining balance of an approved invoice ('.$remaining.').']);
                }
                $values = [...$data,'updated_at'=>now()];
                if ($id) { DB::table($module)->where('id',$id)->update($values); $recordId = $id; }
                else $recordId = DB::table($module)->insertGetId([...$values,'created_at'=>now()]);
                Access::audit($id ? 'Updated' : 'Created',$module,$recordId);
            });
        } catch (\Throwable $e) {
            if ($newPath) Storage::disk('local')->delete($newPath);
            if ($e instanceof QueryException && in_array($e->getCode(),['23000','23505'])) throw ValidationException::withMessages(['record'=>'This reference already exists or a related record changed. Refresh the list and review before trying again.']);
            throw $e;
        }
        if ($newPath && $row?->file_path) Storage::disk('local')->delete($row->file_path);
        if ($module === 'users' && $id) DB::table('sessions')->where('user_id',$id)->where('id','!=',$request->session()->getId())->delete();
        if ($module === 'users' && $id === auth()->id()) $request->user()->refresh();
        return redirect('/manage/'.$module)->with('success','Record '.($id ? 'updated' : 'created').' successfully.');
    }
    public function destroy(string $module, int $id)
    {
        Access::check($module,'write'); abort_unless(auth()->user()->role === 'admin',403);
        abort_if($module === 'payments',403,'Payments are retained for accountability.'); abort_if($module === 'users' && $id === auth()->id(),403);
        $row = DB::table($module)->find($id); abort_unless($row,404);
        try {
            DB::transaction(function () use ($module,$id) {
                if ($module === 'invoices') DB::table('invoices')->where('id',$id)->lockForUpdate()->first();
                DB::table($module)->where('id',$id)->delete(); Access::audit('Deleted',$module,$id);
            });
        } catch (QueryException $e) {
            if (in_array($e->getCode(), ['23000','23503'])) return back()->withErrors(['record'=>'This record is used elsewhere. Update its status instead of deleting it.']);
            throw $e;
        }
        if ($module === 'documents') Storage::disk('local')->delete($row->file_path);
        return back()->with('success','Record deleted.');
    }
    public function approve(Request $request, string $module, int $id)
    {
        Access::check($module);
        abort_unless(in_array(auth()->user()->role,['admin','manager']) && in_array($module,['expenses','invoices','change_orders','contracts']),403);
        $data = $request->validate(['status'=>['required',Rule::in(['Approved','Rejected'])]]);
        DB::transaction(function () use ($module,$id,$data) {
            $row = DB::table($module)->where('id',$id)->lockForUpdate()->first(); abort_unless($row,404);
            if ($module === 'invoices' && DB::table('payments')->where('invoice_id',$id)->exists()) throw ValidationException::withMessages(['status'=>'An invoice with payments cannot change approval status.']);
            DB::table($module)->where('id',$id)->update([...$data,'updated_at'=>now()]); Access::audit($data['status'],$module,$id);
        });
        return back()->with('success','Approval decision recorded.');
    }
    public function download(int $id)
    {
        Access::check('documents'); $row = DB::table('documents')->find($id); abort_unless($row,404);
        abort_unless(Storage::disk('local')->exists($row->file_path),404); Access::audit('Downloaded','documents',$id);
        return Storage::disk('local')->download($row->file_path,basename($row->file_name),['X-Content-Type-Options'=>'nosniff']);
    }
    private function financialSummary(): array
    {
        return ['budget'=>(float)DB::table('projects')->sum('budget'),'changes'=>(float)DB::table('change_orders')->where('status','Approved')->sum('amount'),'expenses'=>(float)DB::table('expenses')->where('status','Approved')->sum('amount'),'invoices'=>(float)DB::table('invoices')->where('status','Approved')->sum('amount'),'paid'=>(float)DB::table('payments')->sum('amount')];
    }
    public function reports(Request $request)
    {
        Access::check('expenses'); $finance = $this->financialSummary();
        $projects = DB::table('projects')->select('projects.*')
            ->selectSub(DB::table('change_orders')->selectRaw('COALESCE(SUM(amount),0)')->whereColumn('project_id','projects.id')->where('status','Approved'),'approved_changes')
            ->selectSub(DB::table('expenses')->selectRaw('COALESCE(SUM(amount),0)')->whereColumn('project_id','projects.id')->where('status','Approved'),'expenses')
            ->selectSub(DB::table('invoices')->selectRaw('COALESCE(SUM(amount),0)')->whereColumn('project_id','projects.id')->where('status','Approved'),'invoices')
            ->selectSub(DB::table('payments')->join('invoices','payments.invoice_id','=','invoices.id')->selectRaw('COALESCE(SUM(payments.amount),0)')->whereColumn('invoices.project_id','projects.id'),'paid')
            ->orderBy('name')->get();
        if ($request->query('export') === 'csv') {
            Access::audit('Exported','reports');
            return response()->streamDownload(function () use ($projects) {
                $handle = fopen('php://output','w'); fputcsv($handle,['Project','Base budget','Approved changes','Approved expenses','Approved contractor invoices','Payments','Remaining budget']);
                foreach ($projects as $p) fputcsv($handle,[$this->safeCsv($p->name),$p->budget,$p->approved_changes,$p->expenses,$p->invoices,$p->paid,$p->budget+$p->approved_changes-$p->expenses-$p->paid]); fclose($handle);
            },'project-financials-'.today()->format('Y-m-d').'.csv',['Content-Type'=>'text/csv']);
        }
        return view('reports',compact('finance','projects'));
    }
    private function safeCsv(string $value): string { return preg_match('/^[\s]*[=+@\-]/',$value) ? "'".$value : $value; }
    public function audit() { abort_unless(auth()->user()->role === 'admin',403); return view('audit',['rows'=>DB::table('audit_logs')->latest('id')->paginate(25)]); }
    public function settings() { abort_unless(auth()->user()->role === 'admin',403); return view('settings'); }
    public function saveSettings(Request $request)
    {
        abort_unless(auth()->user()->role === 'admin',403);
        $data = $request->validate(['name'=>'required|string|max:120','email'=>'nullable|email|max:255','phone'=>'nullable|string|max:50','address'=>'nullable|string|max:1000','currency'=>['required',Rule::in(['PHP','USD','SGD','EUR','GBP','AUD','MYR','IDR','INR','AED'])],'accent_color'=>'required|hex_color','logo'=>'nullable|image|mimes:png,jpg,jpeg,webp|max:2048']); unset($data['logo']);
        $old = DB::table('business_settings')->first(); $path = $request->hasFile('logo') ? $request->file('logo')->store('branding','local') : null;
        if ($path) $data['logo_path'] = $path;
        try { DB::transaction(function () use ($old,$data) { DB::table('business_settings')->updateOrInsert(['id'=>$old?->id ?? 1],[...$data,'updated_at'=>now()]); Access::audit('Updated','settings'); }); }
        catch (\Throwable $e) { if ($path) Storage::disk('local')->delete($path); throw $e; }
        if ($path && $old?->logo_path) Storage::disk('local')->delete($old->logo_path);
        return back()->with('success','Business settings saved.');
    }
    public function logo()
    {
        $path = DB::table('business_settings')->value('logo_path'); abort_unless($path && Storage::disk('local')->exists($path),404);
        return response()->file(Storage::disk('local')->path($path));
    }
}
