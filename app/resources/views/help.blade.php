@extends('layouts.app')
@section('title','User manual')
@section('content')
<div class="page-heading"><div><div class="eyebrow">WORKSPACE GUIDE</div><h1>Make every day easier<span class="heading-dot">.</span></h1><p>A practical guide to your construction workspace.</p></div></div><article class="panel record-form manual-content">{!! \Illuminate\Support\Str::markdown(file_get_contents(base_path('../docs/USER-MANUAL.md')),['html_input'=>'strip','allow_unsafe_links'=>false]) !!}</article>
@endsection
