<?php

namespace App\Http\Controllers;

use App\Models\Interaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class InteractionController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'lead_id' => ['required', 'uuid', 'exists:leads,id'],
            'channel' => ['required', 'in:whatsapp,email,call,meeting,social,other'],
            'summary' => ['required', 'string', 'max:2000'],
            'outcome' => ['nullable', 'string', 'max:500'],
            'occurred_at' => ['required', 'date'],
        ]);
        $data['created_by'] = data_get($request->session()->get('optima_user'), 'id');
        Interaction::create($data);
        return back()->with('status', 'Interaksi berhasil dicatat.');
    }
}
