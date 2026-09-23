<?php

namespace App\Http\Controllers;

use App\Models\FollowUp;
use App\Services\AccessService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FollowUpController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:crm.interaction.view')->only(['index']);
    }

    public function index(Request $request): View
    {
        $query = FollowUp::query()->with('assignee')->where('status', 'open');

        if (AccessService::moduleScope('crm') === 'own') {
            $query->where('assignee_employee_id', AccessService::employeeId());
        }
        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }
        if ($request->boolean('mine')) {
            $query->where('assignee_employee_id', AccessService::employeeId());
        }

        return view('follow-ups.index', [
            'followUps' => $query->orderBy('due_at')->paginate(20)->withQueryString(),
            'mine' => $request->boolean('mine'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'entity_type' => ['required', 'string'],
            'entity_id' => ['required', 'string'],
            'assignee_employee_id' => ['nullable', 'exists:employees,employee_id'],
            'title' => ['required', 'string', 'max:300'],
            'due_at' => ['required', 'date'],
            'priority' => ['required', 'in:low,normal,high'],
        ]);

        FollowUp::create($data + ['status' => 'open', 'created_by' => AccessService::employeeId()]);

        return back()->with('status', 'Follow-up ditambahkan.');
    }

    public function update(Request $request, FollowUp $followUp): RedirectResponse
    {
        $followUp->update([
            'status' => $request->string('status')->toString() === 'done' ? 'done' : $followUp->status,
            'completed_at' => $request->string('status')->toString() === 'done' ? now() : $followUp->completed_at,
        ]);

        return back()->with('status', 'Follow-up diperbarui.');
    }
}