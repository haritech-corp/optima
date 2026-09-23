<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Interaction;
use App\Services\AccessService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClientController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:crm.client.view')->only(['index', 'show']);
        $this->middleware('permission:crm.client.create')->only(['create', 'store']);
        $this->middleware('permission:crm.client.edit')->only(['edit', 'update']);
    }

    public function index(Request $request): View
    {
        $query = Client::query()->with('owner')->whereNull('archived_at');

        if (AccessService::moduleScope('crm') === 'own') {
            $query->where('owner_employee_id', AccessService::employeeId());
        }

        if ($search = $request->string('q')->trim()->toString()) {
            $query->where(fn ($q) => $q->where('name', 'ilike', "%{$search}%")->orWhere('sector', 'ilike', "%{$search}%"));
        }
        if ($request->filled('sector')) {
            $query->where('sector', $request->string('sector')->toString());
        }

        return view('clients.index', [
            'clients' => $query->latest()->paginate(15)->withQueryString(),
        ]);
    }

    public function create(): View
    {
        return view('clients.form', ['client' => new Client]);
    }

    public function store(Request $request): RedirectResponse
    {
        $client = Client::create($this->validated($request) + [
            'owner_employee_id' => $request->input('owner_employee_id') ?: AccessService::employeeId(),
            'created_by' => AccessService::employeeId(),
        ]);

        return redirect()->route('clients.show', $client)->with('status', 'Client berhasil dibuat.');
    }

    public function show(Client $client): View
    {
        $client->load(['owner', 'deals.pic', 'projects', 'interactions.pic']);

        return view('clients.show', compact('client'));
    }

    public function edit(Client $client): View
    {
        return view('clients.form', compact('client'));
    }

    public function update(Request $request, Client $client): RedirectResponse
    {
        $client->update($this->validated($request));

        return redirect()->route('clients.show', $client)->with('status', 'Client berhasil diperbarui.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:200'],
            'legal_name' => ['nullable', 'string', 'max:200'],
            'sector' => ['nullable', 'string', 'max:100'],
            'contact_email' => ['nullable', 'email', 'max:200'],
            'contact_phone' => ['nullable', 'string', 'max:40'],
            'owner_employee_id' => ['nullable', 'exists:employees,employee_id'],
            'total_contract' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', 'string', 'max:40'],
        ]);
    }
}