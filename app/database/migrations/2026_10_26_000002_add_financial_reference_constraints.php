<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('payments',fn(Blueprint $t)=>$t->unique('reference'));
        Schema::table('invoices',fn(Blueprint $t)=>$t->unique(['contractor_id','name']));
    }
    public function down(): void
    {
        Schema::table('payments',fn(Blueprint $t)=>$t->dropUnique(['reference']));
        Schema::table('invoices',fn(Blueprint $t)=>$t->dropUnique(['contractor_id','name']));
    }
};
