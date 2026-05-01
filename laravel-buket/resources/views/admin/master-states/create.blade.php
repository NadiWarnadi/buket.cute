@extends('layouts.admin')
@section('content')
<div class="container">
    <h2>Create State</h2>
    @include('admin.master-states._form', ['submit' => 'Create'])
</div>
@endsection