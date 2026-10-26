@extends('layouts.app')
@section('title','Audit trail')
@section('content')
<div class="page-heading"><div><div class="eyebrow">ADMINISTRATION</div><h1>Audit trail<span class="heading-dot">.</span></h1><p>A record of sign-ins, data changes, approvals, downloads, and exports.</p></div></div><section class="panel"><div class="table-scroll"><table><thead><tr><th>TIME</th><th>ACTOR</th><th>ACTION</th><th>MODULE</th><th>RECORD</th><th>IP ADDRESS</th></tr></thead><tbody>@forelse($rows as $row)<tr><td>{{ $row->created_at }}</td><td><strong>{{ $row->actor }}</strong></td><td>{{ $row->action }}</td><td>{{ str_replace('_',' ',$row->module) }}</td><td>{{ $row->record_id ?? '—' }}</td><td>{{ $row->ip }}</td></tr>@empty<tr><td colspan="6" class="empty-state">No activity recorded yet.</td></tr>@endforelse</tbody></table></div><div class="pagination">{{ $rows->links('pagination') }}</div></section>
@endsection
