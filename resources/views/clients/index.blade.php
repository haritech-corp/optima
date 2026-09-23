@extends('layouts.app')
@section('title','Clients | OPTIMA')
@section('content')
<div class="topbar"><div><div class="eyebrow">CRM</div><h1 class="title">Clients</h1><div class="subtitle">Client aktif hasil konversi lead.</div></div></div><div class="table-wrap"><table><thead><tr><th>Client</th><th>Sektor</th><th>Status</th><th>Owner</th><th>Dibuat</th></tr></thead><tbody>@forelse($clients as $client)<tr><td><a href="{{ route('clients.show',$client) }}"><strong>{{ $client->display_name }}</strong></a><div class="meta">{{ $client->legal_name }}</div></td><td>{{ $client->sector ?: '-' }}</td><td><span class="badge">{{ ucfirst($client->status) }}</span></td><td>{{ $client->owner?->name ?: '-' }}</td><td>{{ $client->created_at->format('d M Y') }}</td></tr>@empty<tr><td colspan="5" class="empty">Belum ada client.</td></tr>@endforelse</tbody></table></div>{{ $clients->links() }}
@endsection
