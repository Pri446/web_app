<!-- Last Modified Date: 18-04-2024
Developed By: Innovative Solution Pvt. Ltd. (ISPL) -->
@extends('layouts.dashboard')
@section('title', __('Users'))
@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    @can('Add User')
                        <a href="{{ action('Auth\UserController@create') }}" class="btn btn-info">{{__('Create User')}}</a>
                    @endcan
                    @can('Export Users to CSV')
                        <a href="{{ action('Auth\UserController@export') }}" class="btn btn-info">{{__('Export to CSV')}}</a>
                    @endcan
                </div>

                <div class="card-body table-responsive p-0">
                    <table class="table table-hover text-nowrap table-striped">
                        <thead>
                            <tr>
                                <th>{{__('User ID')}}</th>
                                <th>{{__('Email')}}</th>
                                <th>{{__('Name')}}</th>
                                <th>{{__('Role')}}</th>
                                <th>{{__('User Type')}}</th>
                                <th>{{__('Status')}}</th>
                                <th>{{__('Actions')}}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($users as $user)
                                @if (!$user->hasRole('Super Admin'))
                                    <tr>
                                        <td>{{ $user->id }}</td>
                                        <td>{{ $user->email }}</td>
                                        <td>{{ $user->name }}</td>
                                        <td>
                                            <?php
                                            $user_roles = [];
                                            foreach ($user->roles as $role) {
                                                $user_roles[] = $role->name;
                                            }
                                            echo implode(', ', $user_roles);
                                            ?>
                                        </td>
                                        <td>{{ $user->user_type }}</td>
                                        <td>{{ get_user_status_description($user->status) }}</td>
                                        <td>
                                            {!! Form::open(['method' => 'DELETE', 'route' => ['users.destroy', $user->id]]) !!}
                                            @can('Edit User')
                                                <a title={{ __('Edit') }}  href="{{ action('Auth\UserController@edit', [$user->id]) }}"
                                                    class="btn btn-info btn-xs"><i class="fa fa-edit"></i></a>
                                            @endcan
                                            @can('View User')
                                                <a title="Detail"
                                                    href="{{ action('Auth\UserController@show', [$user->id]) }}"
                                                    class="btn btn-info btn-xs"><i class="fa fa-list"></i></a>
                                            @endcan
                                            @can('View User Login Activity')
                                                 <a title={{__("History")}}
                                                    href="{{ action('Auth\UserController@getLoginActivity', [$user->id]) }}"
                                                    class="btn btn-info btn-xs"><i class="fa-solid fa-right-to-bracket"></i></a>
                                            @endcan
                                            @can('Delete User')
                                                <button title={{__('Delete')}} .  type="submit"
                                                    class="btn btn-danger btn-xs delete">&nbsp;<i
                                                        class="fa fa-trash"></i>&nbsp;</button>
                                            @endcan
                                            {!! Form::close() !!}
                                        </td>
                                    </tr>
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        // Handle delete button click
        $(document).on('click', '.delete', function(e) {
            e.preventDefault(); // Prevent default button action
            var form = $(this).closest("form"); // Get the closest form element

            // Show SweetAlert confirmation
            deleteAction(form || this);;
        });

        // Handle export button click
        $("#export").on("click", function(e) {
            e.preventDefault(); // Prevent default link behavior
            window.location.href = "{!! url('users/users/export?searchData=') !!}" + searchData;
        });
    </script>
@endpush
