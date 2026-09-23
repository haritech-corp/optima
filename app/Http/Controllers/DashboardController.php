<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\FollowUp;
use App\Models\Interaction;
use App\Models\Lead;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $userId = data_get($request->session()->get('optima_user'), 'id');
        $role = data_get($request->session()->get('optima_user'), 'role');
        $scope = fn ($query) => in_array($role, ['super_admin', 'management', 'crm_manager'], true)
            ? $query
            : $query->where('owner_id', $userId);

        return view('dashboard', [
            'leadCount' => $scope(Lead::query())->whereNull('archived_at')->count(),
            'qualifiedCount' => $scope(Lead::query())->where('status', 'qualified')->count(),
            'clientCount' => Client::query()->whereNull('archived_at')->count(),
            'interactionCount' => Interaction::query()->whereDate('occurred_at', '>=', now()->subDays(30))->count(),
            'dueCount' => FollowUp::query()->where('assignee_id', $userId)->where('status', 'open')->whereDate('due_at', today())->count(),
            'overdueCount' => FollowUp::query()->where('assignee_id', $userId)->where('status', 'open')->where('due_at', '<', now())->count(),
            'recentLeads' => $scope(Lead::query())->latest()->limit(6)->get(),
            'upcoming' => FollowUp::query()->with('lead')->where('assignee_id', $userId)->where('status', 'open')->orderBy('due_at')->limit(6)->get(),
        ]);
    }
}
