<?php
namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class WorkspaceTest extends TestCase
{
    use RefreshDatabase;
    private function user(string $role='admin', bool $active=true): User
    {
        $user=User::factory()->create(['password'=>Hash::make('StrongPassword!2026')]);
        $user->role=$role; $user->active=$active; $user->save(); return $user;
    }
    private function record(string $module, array $overrides=[]): int
    {
        $data=[];
        foreach(config("construction.modules.$module.fields") as $key=>$field) {
            $data[$key]=match($field['type']) {
                'relation'=>$field['required'] ? $this->record($field['table']) : null,
                'select'=>$field['options'][0], 'money'=>1000, 'number'=>10, 'date'=>'2026-10-26',
                'email'=>'test'.uniqid().'@example.test', 'password'=>Hash::make('StrongPassword!2026'), default=>'Sample '.uniqid(),
            };
        }
        return DB::table($module)->insertGetId([...$data,...$overrides,'created_at'=>now(),'updated_at'=>now()]);
    }
    public function test_guests_cannot_access_data(): void
    {
        foreach(['/', '/settings','/audit','/reports','/manage/projects','/documents/1/download'] as $path) $this->get($path)->assertRedirect('/login');
        $this->post('/manage/projects',[])->assertRedirect('/login');
    }
    public function test_login_logout_and_inactive_account(): void
    {
        $user=$this->user();
        $this->post('/login',['email'=>$user->email,'password'=>'incorrect'])->assertSessionHasErrors('email');
        $this->post('/login',['email'=>$user->email,'password'=>'StrongPassword!2026'])->assertRedirect('/');
        $this->assertAuthenticatedAs($user);
        $this->post('/logout')->assertRedirect('/login'); $this->assertGuest();
        $user->active=false; $user->save();
        $this->post('/login',['email'=>$user->email,'password'=>'StrongPassword!2026'])->assertSessionHasErrors('email');
    }
    public function test_login_is_rate_limited(): void
    {
        for($i=0;$i<6;$i++) $response=$this->post('/login',['email'=>'rate@example.test','password'=>'bad']);
        $response->assertSessionHasErrors('email'); $this->assertStringContainsString('Too many',session('errors')->first('email'));
    }
    public function test_all_admin_module_pages_and_forms_render(): void
    {
        $this->actingAs($this->user());
        foreach(array_keys(config('construction.modules')) as $module) {
            $this->get('/manage/'.$module)->assertOk();
            $this->get('/manage/'.$module.'/create')->assertOk();
        }
        foreach(['/','/settings','/audit','/reports','/profile'] as $path) $this->get($path)->assertOk();
    }
    public function test_viewer_cannot_write_or_read_financial_and_admin_modules(): void
    {
        $this->actingAs($this->user('viewer'));
        foreach(array_keys(config('construction.modules')) as $module) $this->post('/manage/'.$module,[])->assertForbidden();
        foreach(['/settings','/audit','/reports','/manage/users','/manage/invoices','/manage/payments','/manage/expenses'] as $path) $this->get($path)->assertForbidden();
        $this->get('/manage/projects')->assertOk(); $this->get('/')->assertOk()->assertDontSee('Budget snapshot');
    }
    public function test_supervisor_cannot_manage_projects_or_approve(): void
    {
        $this->actingAs($this->user('supervisor'));
        $this->post('/manage/projects',[])->assertForbidden();
        $this->post('/manage/contracts/1/approval',['status'=>'Approved'])->assertForbidden();
        $this->get('/manage/tasks/create')->assertOk();
    }
    public function test_manager_can_approve_but_cannot_enter_payments(): void
    {
        $invoice=$this->record('invoices',['status'=>'Submitted']);
        $this->actingAs($this->user('manager'));
        $this->post('/manage/invoices/'.$invoice.'/approval',['status'=>'Approved'])->assertRedirect();
        $this->assertDatabaseHas('invoices',['id'=>$invoice,'status'=>'Approved']);
        $this->post('/manage/payments',[])->assertForbidden();
    }
    public function test_project_creation_validation_and_audit(): void
    {
        $this->actingAs($this->user());
        $data=['name'=>'New site','code'=>'P-001','client'=>'Client','location'=>'Manila','status'=>'Active','start_date'=>'2026-10-26','end_date'=>'2026-12-01','budget'=>'1200000.50','progress'=>0];
        $this->post('/manage/projects',$data)->assertRedirect('/manage/projects');
        $this->assertDatabaseHas('projects',['code'=>'P-001']); $this->assertDatabaseHas('audit_logs',['module'=>'projects','action'=>'Created']);
        $this->post('/manage/projects',$data)->assertSessionHasErrors('code');
        $this->post('/manage/projects',[...$data,'code'=>'P-002','end_date'=>'2026-01-01','progress'=>101])->assertSessionHasErrors(['end_date','progress']);
    }
    public function test_invalid_modules_and_relations_are_rejected(): void
    {
        $this->actingAs($this->user());
        $this->get('/manage/password_reset_tokens')->assertNotFound();
        $this->post('/manage/tasks',['project_id'=>99999])->assertSessionHasErrors('project_id');
    }
    public function test_accountant_cannot_self_approve_or_modify_approved_expense(): void
    {
        $id=$this->record('expenses',['status'=>'Approved']); $row=(array)DB::table('expenses')->find($id);
        $this->actingAs($this->user('accountant'));
        $this->post('/manage/expenses',[...$row,'name'=>'Another','status'=>'Approved'])->assertSessionHasErrors('status');
        $this->put('/manage/expenses/'.$id,[...$row,'status'=>'Submitted','amount'=>'5'])->assertSessionHasErrors('status');
        $this->post('/manage/expenses/'.$id.'/approval',['status'=>'Approved'])->assertForbidden();
    }
    public function test_payment_balance_approval_and_immutability(): void
    {
        $invoice=$this->record('invoices',['status'=>'Submitted','amount'=>'1000.00']);
        $this->actingAs($this->user('accountant'));
        $payment=['name'=>'Payment','invoice_id'=>$invoice,'amount'=>'600.00','payment_date'=>'2026-10-26','method'=>'Bank transfer','reference'=>'TRF-001'];
        $this->post('/manage/payments',$payment)->assertSessionHasErrors('amount');
        DB::table('invoices')->where('id',$invoice)->update(['status'=>'Approved']);
        $this->post('/manage/payments',$payment)->assertRedirect('/manage/payments');
        $this->post('/manage/payments',$payment)->assertSessionHasErrors('reference');
        $this->post('/manage/payments',[...$payment,'reference'=>'TRF-002','amount'=>'400.00'])->assertRedirect('/manage/payments');
        $this->post('/manage/payments',[...$payment,'reference'=>'TRF-003','amount'=>'0.01'])->assertSessionHasErrors('amount');
        $this->put('/manage/payments/1',$payment)->assertForbidden();
        $this->flushSession(); $this->actingAs($this->user()); $this->delete('/manage/payments/1')->assertForbidden();
        $this->post('/manage/invoices/'.$invoice.'/approval',['status'=>'Rejected'])->assertSessionHasErrors('status');
        $this->delete('/manage/invoices/'.$invoice)->assertSessionHasErrors('record');
    }
    public function test_private_file_download_and_invalid_upload(): void
    {
        Storage::fake('local'); $project=$this->record('projects'); $this->actingAs($this->user('supervisor'));
        $data=['name'=>'Site permit','project_id'=>$project,'category'=>'Permit','version'=>'v1'];
        $this->post('/manage/documents',[...$data,'file'=>UploadedFile::fake()->create('evil.php',1,'application/x-httpd-php')])->assertSessionHasErrors('file');
        $this->post('/manage/documents',[...$data,'file'=>UploadedFile::fake()->createWithContent('notes.txt','Site instructions')])->assertRedirect('/manage/documents');
        $doc=DB::table('documents')->first(); Storage::disk('local')->assertExists($doc->file_path);
        $this->get('/documents/'.$doc->id.'/download')->assertDownload('notes.txt');
        $this->get('/storage/'.$doc->file_path)->assertNotFound();
    }
    public function test_user_password_is_hashed_and_privilege_escalation_blocked(): void
    {
        $admin=$this->user(); $this->actingAs($admin);
        $this->post('/manage/users',['name'=>'Test Viewer','email'=>'viewer@test.example','role'=>'viewer','active'=>'1','password'=>'StrongPassword!2026'])->assertRedirect('/manage/users');
        $viewer=User::where('email','viewer@test.example')->first(); $this->assertTrue(Hash::check('StrongPassword!2026',$viewer->password));
        $this->put('/manage/users/'.$admin->id,['name'=>$admin->name,'email'=>$admin->email,'role'=>'viewer','active'=>'1'])->assertSessionHasErrors('role');
        $this->flushSession(); $this->actingAs($viewer)->put('/manage/users/'.$viewer->id,['role'=>'admin'])->assertForbidden();
    }
    public function test_deactivated_session_is_revoked(): void
    {
        $this->actingAs($this->user('manager',false)); $this->get('/')->assertRedirect('/login'); $this->assertGuest();
    }
    public function test_branding_security_and_logo_validation(): void
    {
        Storage::fake('local'); $this->actingAs($this->user());
        $data=['name'=>'Custom Builders','currency'=>'SGD','accent_color'=>'#ccddaa','email'=>'hello@example.test'];
        $this->put('/settings',[...$data,'logo'=>UploadedFile::fake()->image('brand.png')])->assertRedirect();
        $this->get('/')->assertOk()->assertSee('Custom Builders')->assertHeader('X-Frame-Options','DENY')->assertHeader('X-Content-Type-Options','nosniff');
        $this->put('/settings',[...$data,'accent_color'=>'red; background:url(evil)'])->assertSessionHasErrors('accent_color');
    }
    public function test_read_details_and_escape_stored_html(): void
    {
        $project=$this->record('projects',['name'=>'<script>alert(1)</script>']); $this->actingAs($this->user('viewer'));
        $this->get('/manage/projects/'.$project)->assertOk()->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;',false)->assertDontSee('<script>alert(1)</script>',false);
    }
    public function test_csv_prevents_formula_injection_and_reports_use_payments(): void
    {
        $this->record('projects',['name'=>'=1+1','budget'=>1000]); $this->actingAs($this->user('accountant'));
        $response=$this->get('/reports?export=csv')->assertOk()->assertDownload();
        $this->assertStringContainsString("'=1+1",$response->streamedContent());
        $this->get('/reports')->assertOk()->assertSee('Remaining budget');
    }
    public function test_password_change_requires_current_password(): void
    {
        $user=$this->user(); $this->actingAs($user);
        $this->put('/profile/password',['current_password'=>'wrong','password'=>'NewStrongPassword!2026','password_confirmation'=>'NewStrongPassword!2026'])->assertSessionHasErrors('current_password');
        $this->put('/profile/password',['current_password'=>'StrongPassword!2026','password'=>'NewStrongPassword!2026','password_confirmation'=>'NewStrongPassword!2026'])->assertRedirect();
        $this->assertTrue(Hash::check('NewStrongPassword!2026',$user->fresh()->password));
    }
    public function test_populated_demo_details_lists_and_edit_forms_render(): void
    {
        $this->seed(\Database\Seeders\DemoSeeder::class);
        $this->actingAs(User::where('email','admin@forma.test')->first());
        foreach(array_keys(config('construction.modules')) as $module) {
            $this->get('/manage/'.$module)->assertOk();
            $row=DB::table($module)->first();
            $this->get('/manage/'.$module.'/'.$row->id)->assertOk();
            if($module!=='payments') $this->get('/manage/'.$module.'/'.$row->id.'/edit')->assertOk();
        }
        $this->get('/')->assertOk(); $this->get('/reports')->assertOk();
    }
}
