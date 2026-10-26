<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 30)->default('viewer')->index();
            $table->boolean('active')->default(true);
        });
        foreach (config('construction.modules') as $name => $module) {
            if ($name === 'users') continue;
            Schema::create($name, function (Blueprint $table) use ($module, $name) {
                $table->id();
                foreach ($module['fields'] as $key => $field) {
                    $column = match ($field['type']) {
                        'relation' => $table->foreignId($key)->nullable(! $field['required'])->constrained($field['table'])->restrictOnDelete(),
                        'money' => $table->decimal($key, 15, 2),
                        'number' => $table->unsignedInteger($key),
                        'date' => $table->date($key),
                        'textarea' => $table->text($key),
                        default => $table->string($key),
                    };
                    if (! $field['required']) $column->nullable();
                    if (in_array($key, ['status','due_date','name'])) $column->index();
                    if ($name === 'projects' && $key === 'code') $column->unique();
                }
                if ($name === 'documents') { $table->string('file_path'); $table->string('file_name'); }
                $table->timestamps();
            });
        }
        Schema::create('business_settings', function (Blueprint $table) {
            $table->id(); $table->string('name')->default('Forma Construction'); $table->string('email')->nullable();
            $table->string('phone')->nullable(); $table->text('address')->nullable(); $table->string('currency',3)->default('PHP');
            $table->string('logo_path')->nullable(); $table->string('accent_color',7)->default('#d9f36c'); $table->timestamps();
        });
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id(); $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('actor'); $table->string('action'); $table->string('module'); $table->unsignedBigInteger('record_id')->nullable();
            $table->string('ip',45)->nullable(); $table->timestamp('created_at')->useCurrent();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('audit_logs'); Schema::dropIfExists('business_settings');
        foreach (array_reverse(array_keys(config('construction.modules'))) as $name) if ($name !== 'users') Schema::dropIfExists($name);
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn(['role','active']));
    }
};
