<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Lead;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class LeadController extends Controller
{
    public function index(Request $request): View
    {
        $query = Lead::query()->with('owner')->whereNull('archived_at');
        if ($search = $request->string('q')->trim()->toString()) {
            $query->where(fn ($q) => $q->where('name', 'ilike', "%{$search}%")->orWhere('organization', 'ilike', "%{$search}%"));
        }
        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        return view('leads.index', ['leads' => $query->latest()->paginate(15)->withQueryString()]);
    }

    public function create(): View
    {
        return view('leads.form', ['lead' => new Lead]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['owner_id'] = data_get($request->session()->get('optima_user'), 'id');
        $data['created_by'] = $data['owner_id'];
        $data['normalized_email'] = strtolower((string) ($data['email'] ?? ''));
        $data['normalized_phone'] = preg_replace('/\D+/', '', (string) ($data['phone'] ?? ''));

        $duplicate = Lead::query()->whereNull('archived_at')
            ->where(fn ($q) => $q->when($data['normalized_email'], fn ($x) => $x->orWhere('normalized_email', $data['normalized_email']))
                ->when($data['normalized_phone'], fn ($x) => $x->orWhere('normalized_phone', $data['normalized_phone'])))
            ->exists();

        if ($duplicate && ! $request->boolean('confirm_duplicate')) {
            return back()->withInput()->with('duplicate_warning', true);
        }

        $lead = Lead::create($data);
        return redirect()->route('leads.show', $lead)->with('status', 'Lead berhasil dibuat.');
    }

    public function show(Lead $lead): View
    {
        $lead->load(['interactions.creator', 'followUps.assignee']);
        return view('leads.show', compact('lead'));
    }

    public function edit(Lead $lead): View
    {
        return view('leads.form', compact('lead'));
    }

    public function update(Request $request, Lead $lead): RedirectResponse
    {
        $data = $this->validated($request);
        $data['normalized_email'] = strtolower((string) ($data['email'] ?? ''));
        $data['normalized_phone'] = preg_replace('/\D+/', '', (string) ($data['phone'] ?? ''));
        $lead->update($data);
        return redirect()->route('leads.show', $lead)->with('status', 'Lead berhasil diperbarui.');
    }

    public function archive(Lead $lead): RedirectResponse
    {
        $lead->update(['archived_at' => now()]);
        return redirect()->route('leads.index')->with('status', 'Lead diarsipkan.');
    }

    public function convert(Request $request, Lead $lead): RedirectResponse
    {
        $client = DB::transaction(function () use ($lead, $request) {
            $client = Client::create([
                'display_name' => $lead->organization ?: $lead->name,
                'legal_name' => $lead->organization,
                'sector' => $lead->segment,
                'status' => 'active',
                'owner_id' => $lead->owner_id,
                'converted_from_lead_id' => $lead->id,
                'created_by' => data_get($request->session()->get('optima_user'), 'id'),
            ]);
            $lead->update(['status' => 'converted', 'converted_client_id' => $client->id]);
            return $client;
        });

        return redirect()->route('clients.show', $client)->with('status', 'Lead berhasil dikonversi menjadi client.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'organization' => ['nullable', 'string', 'max:200'],
            'email' => ['nullable', 'email', 'max:200'],
            'phone' => ['nullable', 'string', 'max:40'],
            'source' => ['required', 'string', 'max:80'],
            'segment' => ['nullable', 'string', 'max:80'],
            'status' => ['required', 'in:new,contacted,qualified,nurturing,converted,unqualified'],
            'score' => ['nullable', 'integer', 'min:0', 'max:100'],
            'notes' => ['nullable', 'string'],
            'next_follow_up_at' => ['nullable', 'date'],
        ]);
    }
}
