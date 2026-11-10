@extends('layouts.dashboard')
@section('title', __('Roles'))
@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    @can('Add Role')
                        <a href="{{ url('auth/roles/create') }}" class="btn btn-info">{{__('Create Role')}}</a>
                    @endcan
                </div><!-- /.box-header -->
                <div class="card-body table-responsive p-0">
                    <table class="table table-hover text-nowrap table-striped">
                        <thead>
                            <tr>
                                <th>{{__('Name')}}</th>
                                <th>{{__('Actions')}}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($roles as $role)
                                <tr>
                                    <td>{{ $role->name }}</td>
                                    <td>
                                        <form action="{{ url('auth/roles/' . $role->id) }}" method="POST">
                                            {{ csrf_field() }}
                                            {{ method_field('DELETE') }}
                                            @can('Edit Role')
                                                <a href="{{ url('auth/roles/' . $role->id . '/edit') }}"
                                                    class="btn btn-info btn-xs" title={{__("Edit")}}>
                                                    <i class="fa fa-edit"></i>
                                                </a>
                                            @endcan
                                             <button type="submit" class="btn btn-danger btn-xs delete" title=" {{__("Delete")}} "
                                                @if (!auth()->user()->hasRole('Super Admin') && !auth()->user()->hasRole('Municipality - Super Admin')) disabled @endif>
                                                &nbsp;<i class="fa fa-trash"></i>&nbsp;
                                            </button>
                                        </form>
                                    </td>

                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div><!-- /.box-body -->
            </div><!-- /.box -->
        </div><!-- /.col -->
    </div><!-- /.row -->

@stop

@push('scripts')
    <script>
        $('.delete').on('click', function(e) {
            var form = $(this).closest("form");
            event.preventDefault();
            deleteAction(form || this);

        });
    </script>
@endpush
