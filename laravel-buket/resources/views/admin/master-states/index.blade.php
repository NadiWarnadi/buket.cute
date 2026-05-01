@extends('layouts.admin')

@section('content')
<div class="container-fluid">
    <h2>Master States</h2>
    <a href="{{ route('admin.master-states.create') }}" class="btn btn-primary mb-3">Add New</a>
    @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>ID</th><th>Name</th><th>Type</th><th>Prompt</th><th>Next</th><th>Action</th>
            </tr>
        </thead>
        <tbody>
            @foreach($states as $s)
            <tr>
                <td>{{ $s->id }}</td>
                <td>{{ $s->name }}</td>
                <td>{{ $s->type }}</td>
                <td>{{ Str::limit($s->prompt_text, 50) }}</td>
                <td>{{ $s->next_state_id }}</td>
                <td>
                    <a href="{{ route('admin.master-states.edit', $s) }}" class="btn btn-sm btn-info">Edit</a>
                    <form action="{{ route('admin.master-states.destroy', $s) }}" method="POST" style="display:inline;">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Delete?')">Delete</button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection