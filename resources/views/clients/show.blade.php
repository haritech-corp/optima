@extends('layouts.app')
@section('title',$client->display_name.' | OPTIMA')
@section('content')
<div class="topbar"><div><div class="eyebrow">Client Detail</div><h1 class="title">{{ $client->display_name }}</h1><div class="subtitle">{{ $client->legal_name ?: 'Nama legal belum diisi' }}</div></div></div><div class="card"><div class="grid stats" style="margin:0"><div><div class="stat-label">Status</div><strong>{{ ucfirst($client->status) }}</strong></div><div><div class="stat-label">Sektor</div><strong>{{ $client->sector ?: '-' }}</strong></div><div><div class="stat-label">Owner</div><strong>{{ $client->owner?->name ?: '-' }}</strong></div><div><div class="stat-label">Sumber</div>@if($client->sourceLead)<a href="{{ route('leads.show',$client->sourceLead) }}"><strong>{{ $client->sourceLead->name }}</strong></a>@else<strong>-</strong>@endif</div></div></div>
@endsection
