<!DOCTYPE html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="csrf-token" content="{{ csrf_token() }}"><title>@yield('title', 'Overview') · {{ $business->name }}</title><link rel="icon" href="/logo.svg" type="image/svg+xml"><link rel="stylesheet" href="/css/app.css"><script src="/js/app.js" defer></script></head>
<body style="--accent:{{ $business->accent_color }}">
<aside class="sidebar" id="sidebar">
    <a href="/" class="brand"><img src="{{ $business->logo_path ? '/branding/logo' : '/logo.svg' }}" alt="Business logo"><span>{{ $business->name }}<small>CONSTRUCTION WORKSPACE</small></span></a>
    <div class="workspace-label"><span class="workspace-dot"></span> Main workspace <span>⌄</span></div>
    <nav aria-label="Main navigation"><p class="nav-label">WORKSPACE</p><a href="/" class="nav-link {{ request()->is('/') ? 'selected' : '' }}"><span>◫</span> Overview</a>
    @php($group = 'Workspace')
    @foreach(config('construction.modules') as $key => $item)
        @if(\App\Support\Access::can($key))
            @if($group !== $item['group']) @php($group=$item['group']) <p class="nav-label">{{ strtoupper($group) }}</p> @endif
            <a href="/manage/{{ $key }}" class="nav-link {{ request()->is('manage/'.$key.'*') ? 'selected' : '' }}"><span>{{ $item['icon'] }}</span>{{ $item['label'] }}</a>
        @endif
    @endforeach
    @if(\App\Support\Access::can('expenses'))<a href="/reports" class="nav-link {{ request()->is('reports') ? 'selected' : '' }}"><span>▥</span> Reports</a>@endif
    @if(auth()->user()->role === 'admin')<a href="/audit" class="nav-link {{ request()->is('audit') ? 'selected' : '' }}"><span>◷</span> Audit trail</a><a href="/settings" class="nav-link {{ request()->is('settings') ? 'selected' : '' }}"><span>⚙</span> Business settings</a>@endif
    </nav>
    <div class="sidebar-foot"><span class="secure-dot"></span> Your workspace is up to date<a href="/help">User manual ↗</a><a href="/profile">Account & password ↗</a></div>
</aside>
<div class="main-wrap"><header class="topbar"><div class="breadcrumb"><button class="menu-toggle icon-button" aria-label="Toggle navigation" aria-controls="sidebar" aria-expanded="false">☰</button><span>Workspace</span><span class="slash">/</span><strong>@yield('title','Overview')</strong></div><div class="top-actions"><span class="today">{{ now()->format('D, M j, Y') }}</span><a class="avatar" href="/profile" aria-label="My account">{{ strtoupper(substr(auth()->user()->name,0,1)) }}</a><div class="user-name">{{ auth()->user()->name }}<small>{{ config('construction.roles.'.auth()->user()->role) }}</small></div><form method="POST" action="/logout">@csrf<button class="icon-button" title="Sign out" aria-label="Sign out">↪</button></form></div></header>
<main id="main-content">
@if(session('success'))<div class="alert success" role="status">✓ {{ session('success') }}</div>@endif
@if($errors->any())<div class="alert error" role="alert"><strong>Please check the following:</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
@yield('content')
<footer class="page-footer"><span>{{ $business->name }} · Built for better projects.</span><span>Construction management / v1.0</span></footer>
</main></div>
</body></html>
