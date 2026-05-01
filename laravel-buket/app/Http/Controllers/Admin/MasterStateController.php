<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MasterState;
use Illuminate\Http\Request;

class MasterStateController extends Controller
{
    public function index()
    {
        $states = MasterState::orderBy('id')->get();
        return view('admin.master-states.index', compact('states'));
    }

    public function create()
    {
        return view('admin.master-states.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'type' => 'required|in:greeting,input,fuzzy_inquiry,decision,output',
            'prompt_text' => 'nullable|string',
            'input_key' => 'nullable|string|max:100',
            'fuzzy_context' => 'nullable|string|max:100',
            'next_state_id' => 'nullable|exists:master_states,id',
            'fallback_state_id' => 'nullable|exists:master_states,id',
            'prerequisite_keys' => 'nullable|json',
            'resume_message' => 'nullable|string',
            'validation_rules' => 'nullable|json',
        ]);

        $data['validation_rules'] = json_decode($data['validation_rules'] ?? '[]', true);
        $data['prerequisite_keys'] = json_decode($data['prerequisite_keys'] ?? '[]', true);
        MasterState::create($data);

        return redirect()->route('admin.master-states.index')->with('success', 'State created.');
    }

    public function edit(MasterState $masterState)
    {
        return view('admin.master-states.edit', ['state' => $masterState]);
    }

    public function update(Request $request, MasterState $masterState)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'type' => 'required|in:greeting,input,fuzzy_inquiry,decision,output',
            'prompt_text' => 'nullable|string',
            'input_key' => 'nullable|string|max:100',
            'fuzzy_context' => 'nullable|string|max:100',
            'next_state_id' => 'nullable|exists:master_states,id',
            'fallback_state_id' => 'nullable|exists:master_states,id',
            'prerequisite_keys' => 'nullable|json',
            'resume_message' => 'nullable|string',
            'validation_rules' => 'nullable|json',
        ]);

        $data['validation_rules'] = json_decode($data['validation_rules'] ?? '[]', true);
        $data['prerequisite_keys'] = json_decode($data['prerequisite_keys'] ?? '[]', true);
        $masterState->update($data);

        return redirect()->route('admin.master-states.index')->with('success', 'State updated.');
    }

    public function destroy(MasterState $masterState)
    {
        $masterState->delete();
        return redirect()->route('admin.master-states.index')->with('success', 'State deleted.');
    }
}