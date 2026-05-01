<form action="{{ isset($state) ? route('admin.master-states.update', $state) : route('admin.master-states.store') }}" method="POST">
    @csrf
    @if(isset($state)) @method('PUT') @endif

    <div class="mb-3">
        <label>Name</label>
        <input type="text" name="name" class="form-control" value="{{ old('name', $state->name ?? '') }}" required>
    </div>
    <div class="mb-3">
        <label>Type</label>
        <select name="type" class="form-control">
            @foreach(['greeting','input','fuzzy_inquiry','decision','output'] as $type)
                <option value="{{ $type }}" {{ (old('type', $state->type ?? '') == $type) ? 'selected' : '' }}>{{ $type }}</option>
            @endforeach
        </select>
    </div>
    <div class="mb-3">
        <label>Prompt Text</label>
        <textarea name="prompt_text" class="form-control">{{ old('prompt_text', $state->prompt_text ?? '') }}</textarea>
    </div>
    <div class="mb-3">
        <label>Input Key</label>
        <input type="text" name="input_key" class="form-control" value="{{ old('input_key', $state->input_key ?? '') }}">
    </div>
    <div class="mb-3">
        <label>Fuzzy Context</label>
        <input type="text" name="fuzzy_context" class="form-control" value="{{ old('fuzzy_context', $state->fuzzy_context ?? '') }}">
    </div>
    <div class="mb-3">
        <label>Next State ID</label>
        <input type="number" name="next_state_id" class="form-control" value="{{ old('next_state_id', $state->next_state_id ?? '') }}">
    </div>
    <div class="mb-3">
        <label>Fallback State ID</label>
        <input type="number" name="fallback_state_id" class="form-control" value="{{ old('fallback_state_id', $state->fallback_state_id ?? '') }}">
    </div>
    <div class="mb-3">
        <label>Prerequisite Keys (JSON array)</label>
        <input type="text" name="prerequisite_keys" class="form-control" value="{{ old('prerequisite_keys', isset($state) ? json_encode($state->prerequisite_keys) : '') }}" placeholder='["name","address"]'>
    </div>
    <div class="mb-3">
        <label>Resume Message</label>
        <input type="text" name="resume_message" class="form-control" value="{{ old('resume_message', $state->resume_message ?? '') }}">
    </div>
    <div class="mb-3">
        <label>Validation Rules (JSON)</label>
        <input type="text" name="validation_rules" class="form-control" value="{{ old('validation_rules', isset($state) ? json_encode($state->validation_rules) : '') }}" placeholder='{"min_length":3}'>
    </div>
    <button type="submit" class="btn btn-primary">{{ $submit }}</button>
</form>