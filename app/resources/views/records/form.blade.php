@extends('layouts.app')
@section('title',($row ? 'Edit ' : 'New ').str_replace('_',' ',\Illuminate\Support\Str::singular($module)))
@section('content')
<div class="page-heading"><div><a class="text-link" href="/manage/{{ $module }}">← {{ $definition['label'] }}</a><h1>{{ $row ? 'Edit record' : 'Create a record' }}<span class="heading-dot">.</span></h1><p>{{ $definition['label'] }} · Fields marked * are required.</p></div></div>
<form class="panel record-form" method="POST" enctype="multipart/form-data" action="/manage/{{ $module }}{{ $row ? '/'.$row->id : '' }}">@csrf @if($row) @method('PUT') @endif<div class="form-section-heading"><h2>{{ $row ? $row->name : $definition['label'].' details' }}</h2><p>Provide the information below to keep your workspace accurate.</p></div><div class="form-grid">
@foreach($definition['fields'] as $key=>$field)
@php($value=old($key,$field['type']==='password' ? '' : ($row->$key ?? ($key==='active' ? '1' : ($key==='progress' ? '0' : '')))))
<div class="form-field {{ $field['type']==='textarea' ? 'full-width' : '' }}"><label for="{{ $key }}">{{ ucwords(str_replace(['_id','_'],['',' '],$key)) }} @if($field['required'] && !($row && $key==='password'))<span class="required">*</span>@endif</label>
@if($field['type']==='textarea')<textarea id="{{ $key }}" name="{{ $key }}" rows="4" @required($field['required'])>{{ $value }}</textarea>
@elseif(in_array($field['type'],['select','relation']))<select id="{{ $key }}" name="{{ $key }}" @required($field['required'])><option value="">Select {{ str_replace('_',' ',$key) }}</option>@foreach($field['type']==='relation' ? $lookups[$key] : array_combine($field['options'],$field['options']) as $option=>$label)<option value="{{ $option }}" @selected((string)$value===(string)$option)>{{ $key==='active' ? ($label==='1' ? 'Active' : 'Inactive') : $label }}</option>@endforeach</select>
@else<input id="{{ $key }}" name="{{ $key }}" type="{{ $field['type']==='money' ? 'number' : ($field['type']==='number' ? 'number' : $field['type']) }}" value="{{ $value }}" @if(in_array($field['type'],['money','number'])) min="0" step="{{ $field['type']==='money' ? '0.01' : '1' }}" @if(isset($field['max'])) max="{{ $field['max'] }}" @endif @endif @if($field['type']==='password') autocomplete="new-password" @endif @required($field['required'] && !($row && $key==='password'))>
@endif
@if($key==='password')<small>At least 12 characters with uppercase, lowercase, a number and a symbol. {{ $row ? 'Leave blank to keep the current password.' : '' }}</small>@endif
@error($key)<small class="field-error">{{ $message }}</small>@enderror</div>
@endforeach
@if($module==='documents')<div class="form-field full-width"><label for="file">Document {{ $row ? '(optional replacement)' : '*' }}</label><input type="file" id="file" name="file" accept=".pdf,.jpg,.jpeg,.png,.webp,.docx,.xlsx,.txt" @required(!$row)><small>PDF, JPG, PNG, WebP, DOCX, XLSX or TXT. Maximum 10 MB. Downloads require sign-in.</small>@if($row)<a href="/documents/{{ $row->id }}/download">Download current file: {{ $row->file_name }}</a>@endif</div>@endif
</div><div class="form-actions"><a class="button secondary" href="/manage/{{ $module }}">Cancel</a><button class="button primary">{{ $row ? 'Save changes' : 'Create record' }} ↗</button></div></form>
@endsection
