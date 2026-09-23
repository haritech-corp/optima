<?php

namespace App\Http\Controllers;

use App\Models\FollowUp;
use App\Models\Interaction;
use App\Services\AccessService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InteractionController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:crm.interaction.view')->only(['index']);
        $this->middleware('permission:crm.interaction.create')->only(['store']);
    }

    public function index(Request $request): View
    {
        $query = Interaction::query()->with('pic');

        if ($request->filled('entity_type')) {
            $query->where('entity_type', $request->string('entity_type')->toString());
        }
        if ($request->filled('q')) {
            $query->where('activity', 'ilike', '%'.$request->string('q')->toString().'%');
        }

        return view('interactions.index', [
            'interactions' => $query->latest('interaction_date')->paginate(20)->withQueryString(),
            'entityTypes' => array_keys(config('optima.modules')) ?: [],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'entity_type' => ['required', 'string'],
            'entity_id' => ['required', 'string'],
            'interaction_date' => ['required', 'date'],
            'pic_employee_id' => ['nullable', 'exists:employees,employee_id'],
            'activity' => ['required', 'string', 'max:2000'],
            'result' => ['nullable', 'string', 'max:2000'],
            'next_action' => ['nullable', 'string', 'max:2000'],
            'follow_up_date' => ['nullable', 'date'],
            'status' => ['required', 'string'],
        ]);

        $data['pic_employee_id'] ??= AccessService::employeeId();
        $data['created_by'] = AccessService::employeeId();

        $interaction = Interaction::create($data);

        // A follow-up that needs further action must have next action + date.
        if ($data['next_action'] && $data['follow_up_date']) {
            FollowUp::create([
                'entity_type' => $data['entity_type'],
                'entity_id' => $data['entity_id'],
                'assignee_employee_id' => $data['pic_employee_id'],
                'title' => $data['next_action'],
                'due_at' => $data['follow_up_date'],
                'status' => 'open',
                'created_by' => AccessService::employeeId(),
            ]);
        }

        $back = $request->input('back') ?: back()->getTargetUrl();
        if (str_starts_with($request->input('back', ''), '/')) {
            return redirect($request->input('back'))->with('status', 'Interaksi berhasil dicatat.');
        }

        return redirect()->to($back)->with('status', 'Interaksi berhasil dicatat. (#'.$interaction->interaction_id.')');
    }
}