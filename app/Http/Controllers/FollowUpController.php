<?php

namespace App\Http\Controllers;

use App\Models\FollowUp;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FollowUpController extends Controller
{
    public function index(Request $request): View
    {
        $query = FollowUp::query()->with(['lead', 'assignee'])->orderBy('due_at');
        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }
        return view('follow-ups.index', ['followUps' => $query->paginate(20)->withQueryString()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'lead_id' => ['required', 'uuid', 'exists:leads,id'],
            'title' => ['required', 'string', 'max:200'],
            'due_at' => ['required', 'date'],
            'priority' => ['required', 'in:low,normal,high,urgent'],
        ]);
        $data['assignee_id'] = data_get($request->session()->get('optima_user'), 'id');
        $data['created_by'] = $data['assignee_id'];
        $data['status'] = 'open';
        FollowUp::create($data);
        return back()->with('status', 'Follow-up berhasil dibuat.');
    }

    public function update(Request $request, FollowUp $followUp): RedirectResponse
    {
        $data = $request->validate(['status' => ['required', 'in:open,completed,cancelled']]);
        $data['completed_at'] = $data['status'] === 'completed' ? now() : null;
        $followUp->update($data);
        return back()->with('status', 'Status follow-up diperbarui.');
    }
}
