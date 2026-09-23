<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\View\View;

class ClientController extends Controller
{
    public function index(): View
    {
        return view('clients.index', ['clients' => Client::query()->whereNull('archived_at')->latest()->paginate(15)]);
    }

    public function show(Client $client): View
    {
        return view('clients.show', ['client' => $client->load(['sourceLead', 'owner'])]);
    }
}
