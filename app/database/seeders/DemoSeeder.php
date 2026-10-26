<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local','testing'])) throw new \RuntimeException('Demo data is restricted to local and testing environments.');
        if (DB::table('projects')->exists()) { $this->command?->warn('Demo skipped: projects already exist.'); return; }
        DB::transaction(function () {
            $this->call(DatabaseSeeder::class);
            $users = [];
            foreach (['admin'=>'Alex Morgan','manager'=>'Jamie Santos','accountant'=>'Sam Rivera','supervisor'=>'Casey Lim','viewer'=>'Taylor Cruz'] as $role=>$name) {
                $email = $role.'@forma.test';
                $existing = DB::table('users')->where('email',$email)->first();
                $users[$role] = $existing?->id ?? DB::table('users')->insertGetId(['name'=>$name,'email'=>$email,'password'=>Hash::make('BuildTogether!2026'),'role'=>$role,'active'=>true,'created_at'=>now(),'updated_at'=>now()]);
            }
            $add = fn($table,$values) => DB::table($table)->insertGetId([...$values,'created_at'=>now(),'updated_at'=>now()]);
            $projects=[];
            foreach ([['The Oakridge Residences','PRJ-2026-001','Oakridge Development','Makati City','Active',12500000,68],['Northline Commercial Hub','PRJ-2026-002','Northline Properties','Quezon City','Active',8400000,42],['Riverpark Community Center','PRJ-2026-003','City Development Office','Pasig City','Planning',6200000,12],['Westgate Office Renovation','PRJ-2026-004','Westgate Holdings','Taguig City','On hold',3150000,31],['Greenfield Warehouse','PRJ-2026-005','Greenfield Logistics','Laguna','Completed',4700000,100]] as [$name,$code,$client,$location,$status,$budget,$progress]) {
                $projects[]=$add('projects',compact('name','code','client','location','status','budget','progress')+['start_date'=>today()->subDays(90)->format('Y-m-d'),'end_date'=>today()->addDays(60+count($projects)*25)->format('Y-m-d'),'manager_id'=>$users['manager'],'description'=>'Sample project for exploring construction workflows. Replace with your business records.']);
            }
            $contractors=[];
            foreach ([['Apex Structural Works','Civil & structural','Miguel Reyes'],['Voltline Electrical','Electrical','Patricia Garcia'],['Blueflow Plumbing','Plumbing','Daniel Tan'],['Precision Finishing Co.','Finishing','Anna Ramos']] as [$name,$trade,$contact_person]) $contractors[]=$add('contractors',compact('name','trade','contact_person')+['email'=>'contact'.count($contractors).'@example.test','phone'=>'+63 917 555 010'.count($contractors),'license_number'=>'LIC-2026-00'.count($contractors),'license_expiry'=>today()->addYear()->format('Y-m-d'),'status'=>'Active','address'=>'Metro Manila','notes'=>'Fictional demonstration contractor.']);
            foreach ([['Complete foundation inspection',0,'High',-2],['Install ground floor electrical',0,'High',2],['Review structural drawings',1,'Medium',3],['Approve material samples',2,'Medium',5],['Complete site safety briefing',1,'Low',1],['Roofing milestone',0,'High',20]] as [$name,$p,$priority,$days]) $add('tasks',compact('name','priority')+['project_id'=>$projects[$p],'contractor_id'=>$contractors[$p%4],'assigned_to'=>$users['supervisor'],'status'=>'In progress','due_date'=>today()->addDays($days)->format('Y-m-d'),'is_milestone'=>str_contains($name,'milestone') ? 'Yes' : 'No','notes'=>'Coordinate with the site team before completion.']);
            $add('contracts',['name'=>'Oakridge structural package','project_id'=>$projects[0],'contractor_id'=>$contractors[0],'amount'=>2800000,'start_date'=>today()->subDays(60)->format('Y-m-d'),'end_date'=>today()->addDays(90)->format('Y-m-d'),'status'=>'Active','scope'=>'Foundation, concrete works, and structural framing.']);
            foreach ([['Concrete supply delivery',0,'Materials',385000],['Site supervision',1,'Labor',85000],['Crane rental',0,'Equipment',110000],['Permit processing',2,'Overhead',25000]] as [$name,$p,$category,$amount]) $add('expenses',compact('name','category','amount')+['project_id'=>$projects[$p],'expense_date'=>today()->subDays(5)->format('Y-m-d'),'status'=>'Approved','notes'=>'Demo expense.']);
            $invoice=$add('invoices',['name'=>'INV-APEX-2026-001','project_id'=>$projects[0],'contractor_id'=>$contractors[0],'amount'=>750000,'due_date'=>today()->addDays(14)->format('Y-m-d'),'status'=>'Approved','notes'=>'Structural progress billing.']);
            $add('payments',['name'=>'Foundation progress payment','invoice_id'=>$invoice,'amount'=>450000,'payment_date'=>today()->subDays(2)->format('Y-m-d'),'method'=>'Bank transfer','reference'=>'DEMO-TRF-001','notes'=>'Partial payment.']);
            $add('change_orders',['name'=>'Additional drainage works','project_id'=>$projects[0],'amount'=>125000,'days_impact'=>5,'status'=>'Submitted','reason'=>'Updated drainage plan following site inspection.']);
            $add('daily_logs',['name'=>'Ground floor slab preparation','project_id'=>$projects[0],'log_date'=>today()->format('Y-m-d'),'weather'=>'Clear','workers'=>24,'hours_worked'=>8,'work_completed'=>'Completed rebar placement and formwork inspection.','issues'=>'Awaiting concrete delivery confirmation.']);
            $add('safety_incidents',['name'=>'Access walkway obstruction','project_id'=>$projects[1],'reported_date'=>today()->format('Y-m-d'),'severity'=>'Low','status'=>'Resolved','description'=>'Materials temporarily blocking a designated walkway.','corrective_action'=>'Relocated materials and marked storage zone.']);
            $add('materials',['name'=>'Portland cement','project_id'=>$projects[0],'quantity'=>500,'unit'=>'bags','unit_cost'=>265,'supplier'=>'Metro Building Supply','status'=>'Delivered','notes'=>'Store in dry area.']);
            $add('equipment',['name'=>'Mobile crane 25T','project_id'=>$projects[0],'serial_number'=>'EQ-CR-001','status'=>'In use','next_service'=>today()->addDays(20)->format('Y-m-d'),'daily_cost'=>15000,'notes'=>'Daily inspection required.']);
            \Illuminate\Support\Facades\Storage::disk('local')->put('documents/demo-site-plan.txt','FORMA CONSTRUCTION — SAMPLE SITE PLAN NOTES. Fictional demo document. Replace with approved project drawings.');
            $add('documents',['name'=>'Oakridge preliminary site notes','project_id'=>$projects[0],'category'=>'Drawing','version'=>'v1','notes'=>'Demonstration document only.','file_path'=>'documents/demo-site-plan.txt','file_name'=>'sample-site-notes.txt']);
            foreach ([['Created','projects',$projects[0]],['Updated','tasks',1],['Created','daily_logs',1],['Approved','expenses',1],['Created','payments',1]] as [$action,$module,$record]) DB::table('audit_logs')->insert(['user_id'=>$users['admin'],'actor'=>'Alex Morgan','action'=>$action,'module'=>$module,'record_id'=>$record,'ip'=>'127.0.0.1','created_at'=>now()->subMinutes(rand(5,120))]);
        });
        $this->command?->info('Local demo loaded. admin@forma.test / BuildTogether!2026 — change before real use.');
    }
}
