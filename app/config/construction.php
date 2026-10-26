<?php

// The registry is the single allowlist for storage, forms, validation and permissions.
$text = fn ($required = true) => ['type' => 'text', 'required' => $required];
$select = fn ($options) => ['type' => 'select', 'options' => $options, 'required' => true];
$relation = fn ($table, $required = true) => ['type' => 'relation', 'table' => $table, 'required' => $required];
$date = fn ($required = true) => ['type' => 'date', 'required' => $required];
$money = ['type' => 'money', 'required' => true];
$notes = ['type' => 'textarea', 'required' => false];

return [
    'roles' => ['admin' => 'Administrator', 'manager' => 'Project manager', 'accountant' => 'Accountant', 'supervisor' => 'Site supervisor', 'viewer' => 'Read-only viewer'],
    'modules' => [
        'projects' => ['label' => 'Projects', 'group' => 'Workspace', 'icon' => '▦', 'write' => ['admin','manager'], 'fields' => [
            'name' => $text(), 'code' => $text(), 'client' => $text(), 'location' => $text(), 'status' => $select(['Planning','Active','On hold','Completed','Cancelled']), 'start_date' => $date(), 'end_date' => $date(), 'budget' => $money, 'progress' => ['type'=>'number','required'=>true,'max'=>100], 'manager_id' => $relation('users',false), 'description' => $notes]],
        'contractors' => ['label' => 'Contractors', 'group' => 'Workspace', 'icon' => '◈', 'write' => ['admin','manager'], 'fields' => [
            'name' => $text(), 'trade' => $select(['General contractor','Electrical','Plumbing','Civil & structural','Finishing','HVAC','Landscaping','Other']), 'contact_person' => $text(), 'email' => ['type'=>'email','required'=>true], 'phone' => $text(), 'license_number' => $text(false), 'license_expiry' => $date(false), 'status' => $select(['Active','Pending review','Suspended']), 'address' => $notes, 'notes' => $notes]],
        'contracts' => ['label' => 'Contracts', 'group' => 'Workspace', 'icon' => '▤', 'write' => ['admin','manager'], 'fields' => [
            'name' => $text(), 'project_id' => $relation('projects'), 'contractor_id' => $relation('contractors'), 'amount' => $money, 'start_date' => $date(), 'end_date' => $date(), 'status' => $select(['Draft','Approved','Rejected','Active','Completed','Terminated']), 'scope' => $notes]],
        'tasks' => ['label' => 'Tasks & milestones', 'group' => 'Delivery', 'icon' => '✓', 'write' => ['admin','manager','supervisor'], 'fields' => [
            'name' => $text(), 'project_id' => $relation('projects'), 'contractor_id' => $relation('contractors',false), 'assigned_to' => $relation('users',false), 'priority' => $select(['Low','Medium','High','Critical']), 'status' => $select(['To do','In progress','Blocked','Done']), 'due_date' => $date(), 'is_milestone' => $select(['No','Yes']), 'notes' => $notes]],
        'daily_logs' => ['label' => 'Daily site logs', 'group' => 'Delivery', 'icon' => '☷', 'write' => ['admin','manager','supervisor'], 'fields' => [
            'name' => $text(), 'project_id' => $relation('projects'), 'log_date' => $date(), 'weather' => $select(['Clear','Cloudy','Rain','Storm']), 'workers' => ['type'=>'number','required'=>true], 'hours_worked' => ['type'=>'number','required'=>true,'max'=>24], 'work_completed' => ['type'=>'textarea','required'=>true], 'issues' => $notes]],
        'safety_incidents' => ['label' => 'Safety & issues', 'group' => 'Delivery', 'icon' => '△', 'write' => ['admin','manager','supervisor'], 'fields' => [
            'name' => $text(), 'project_id' => $relation('projects'), 'reported_date' => $date(), 'severity' => $select(['Low','Medium','High','Critical']), 'status' => $select(['Open','Investigating','Resolved']), 'description' => ['type'=>'textarea','required'=>true], 'corrective_action' => $notes]],
        'change_orders' => ['label' => 'Change orders', 'group' => 'Delivery', 'icon' => '⇄', 'write' => ['admin','manager'], 'fields' => [
            'name' => $text(), 'project_id' => $relation('projects'), 'amount' => $money, 'days_impact' => ['type'=>'number','required'=>true], 'status' => $select(['Draft','Submitted','Approved','Rejected']), 'reason' => ['type'=>'textarea','required'=>true]]],
        'materials' => ['label' => 'Materials', 'group' => 'Resources', 'icon' => '▧', 'write' => ['admin','manager','supervisor'], 'fields' => [
            'name' => $text(), 'project_id' => $relation('projects'), 'quantity' => ['type'=>'number','required'=>true], 'unit' => $text(), 'unit_cost' => $money, 'supplier' => $text(), 'status' => $select(['Requested','Ordered','Delivered','Used']), 'notes' => $notes]],
        'equipment' => ['label' => 'Equipment', 'group' => 'Resources', 'icon' => '⚒', 'write' => ['admin','manager','supervisor'], 'fields' => [
            'name' => $text(), 'project_id' => $relation('projects',false), 'serial_number' => $text(), 'status' => $select(['Available','In use','Maintenance','Retired']), 'next_service' => $date(false), 'daily_cost' => $money, 'notes' => $notes]],
        'documents' => ['label' => 'Documents', 'group' => 'Resources', 'icon' => '▱', 'write' => ['admin','manager','supervisor'], 'fields' => [
            'name' => $text(), 'project_id' => $relation('projects'), 'category' => $select(['Drawing','Permit','Contract','Photo','Report','Other']), 'version' => $text(), 'notes' => $notes]],
        'expenses' => ['label' => 'Expenses', 'group' => 'Financials', 'icon' => '↗', 'read' => ['admin','manager','accountant'], 'write' => ['admin','accountant'], 'fields' => [
            'name' => $text(), 'project_id' => $relation('projects'), 'category' => $select(['Labor','Materials','Equipment','Subcontractor','Overhead','Other']), 'amount' => $money, 'expense_date' => $date(), 'status' => $select(['Draft','Submitted','Approved','Rejected']), 'notes' => $notes]],
        'invoices' => ['label' => 'Contractor invoices', 'group' => 'Financials', 'icon' => '▥', 'read' => ['admin','manager','accountant'], 'write' => ['admin','accountant'], 'fields' => [
            'name' => $text(), 'project_id' => $relation('projects'), 'contractor_id' => $relation('contractors'), 'amount' => $money, 'due_date' => $date(), 'status' => $select(['Draft','Submitted','Approved','Rejected']), 'notes' => $notes]],
        'payments' => ['label' => 'Payments', 'group' => 'Financials', 'icon' => '⇥', 'read' => ['admin','manager','accountant'], 'write' => ['admin','accountant'], 'fields' => [
            'name' => $text(), 'invoice_id' => $relation('invoices'), 'amount' => $money, 'payment_date' => $date(), 'method' => $select(['Bank transfer','Cash','Cheque','Other']), 'reference' => $text(), 'notes' => $notes]],
        'users' => ['label' => 'Team & access', 'group' => 'Administration', 'icon' => '♙', 'read' => ['admin'], 'write' => ['admin'], 'fields' => [
            'name' => $text(), 'email' => ['type'=>'email','required'=>true], 'role' => $select(['admin','manager','accountant','supervisor','viewer']), 'active' => $select(['1','0']), 'password' => ['type'=>'password','required'=>true]]],
    ],
];
