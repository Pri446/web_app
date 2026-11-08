@extends('layouts.dashboard')
@section('title', $page_title)
@section('content')
@include('layouts.components.error-list')
@include('layouts.components.success-alert')
@include('layouts.components.error-alert')
    <div class="card card-info">
        {!! Form::open(['method' => 'POST', 'route' => ['places.store'], 'class' => 'form-horizontal']) !!}
        @include('places.partial-form', ['submitButtonText' => 'Save'])
        {!! Form::close() !!}
    </div><!-- /.card -->
@endsection
