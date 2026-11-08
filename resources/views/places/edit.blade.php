<!-- Last Modified Date: 18-04-2024
Developed By: Innovative Solution Pvt. Ltd. (ISPL)   -->
@extends('layouts.layers')
@section('title', $page_title)
@section('content')
@include('layouts.components.error-list')
@include('layouts.components.success-alert')
@include('layouts.components.error-alert')
<div class="card card-info">
{!! Form::model($place, [
    'method' => 'PATCH',
    'action' => ['PlacesController@update', $place->id],
    'class' => 'form-horizontal',
   
]) !!}
    @include('places.partial-form', ['submitButtonText' => 'Update'])
{!! Form::close() !!}
</div><!-- /.card -->
@stop
