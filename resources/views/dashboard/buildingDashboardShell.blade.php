@extends('layouts.dashboard')

@section('title', $page_title)

@section('content')
    @include('dashboard._asyncDashboardLoader', [
        'dashboardContentEndpoint' => route('buildingdashboard.content'),
    ])
@stop
