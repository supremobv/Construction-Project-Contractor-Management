<?php
namespace App\Support;

class Access
{
    public static function can(string $module, string $action = 'read'): bool
    {
        $user = auth()->user();
        if (! $user || ! $user->active) return false;
        $definition = config("construction.modules.$module");
        if (! $definition) return false;
        return in_array($user->role, $definition[$action] ?? array_keys(config('construction.roles')), true);
    }
    public static function check(string $module, string $action = 'read'): array
    {
        abort_unless(config("construction.modules.$module"), 404);
        abort_unless(self::can($module, $action), 403);
        return config("construction.modules.$module");
    }
    public static function audit(string $action, string $module, ?int $id = null): void
    {
        \Illuminate\Support\Facades\DB::table('audit_logs')->insert([
            'user_id'=>auth()->id(), 'actor'=>auth()->user()?->name ?? 'System', 'action'=>$action,
            'module'=>$module, 'record_id'=>$id, 'ip'=>request()->ip(), 'created_at'=>now(),
        ]);
    }
}
