<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('workspace:admin {email} {--name=Administrator}', function () {
    $email = $this->argument('email');
    if (! filter_var($email,FILTER_VALIDATE_EMAIL)) { $this->error('Enter a valid email.'); return 1; }
    if (\App\Models\User::where('email',$email)->exists()) { $this->error('That email is already in use. Manage the existing user from Team & access.'); return 1; }
    $password = $this->secret('Password (12+ characters, uppercase, lowercase, number and symbol)');
    $validator = validator(['password'=>$password],['password'=>['required',\Illuminate\Validation\Rules\Password::min(12)->mixedCase()->numbers()->symbols()]]);
    if ($validator->fails()) { $this->error($validator->errors()->first()); return 1; }
    $user = new \App\Models\User; $user->name=$this->option('name'); $user->email=$email; $user->password=$password; $user->role='admin'; $user->active=true; $user->save();
    $this->info('Administrator created.');
})->purpose('Create the initial administrator without a default password');

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
