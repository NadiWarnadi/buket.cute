@extends('layouts.admin')
@section('content')
<div class="container">
    <h2>Edit State</h2>
    @include('admin.master-states._form', ['submit' => 'Update'])
</div>
@endsection