@extends('layouts.app')
@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">Manage Permissions</div>

                    <div class="card-body">
                        @if (session('success'))
                            <div class="alert alert-success" role="alert">
                                {{ session('success') }}
                            </div>
                        @endif

                        @if (session('error'))
                            <div class="alert alert-danger" role="alert">
                                {{ session('error') }}
                            </div>
                        @endif

                        <form method="POST" action="{{ route('permissions.store') }}">
                            @csrf
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                    <tr>
                                        <th>Permission</th>
                                        @foreach($roles as $role)
                                            <th>{{ $role->name }}</th>
                                        @endforeach
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @foreach($permissions as $group => $groupPermissions)
                                        <tr>
                                            <td colspan="{{ count($roles) + 1 }}" class="bg-light">
                                                <strong>{{ ucfirst($group) }}</strong>
                                            </td>
                                        </tr>
                                        @foreach($groupPermissions as $permission)
                                            <tr>
                                                <td>{{ $permission->name }}</td>
                                                @foreach($roles as $role)
                                                    <td>
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="checkbox"
                                                                   name="permissions[{{ $role->id }}][]"
                                                                   value="{{ $permission->id }}"
                                                                {{ $role->permissions->contains($permission->id) ? 'checked' : '' }}>
                                                        </div>
                                                    </td>
                                                @endforeach
                                            </tr>
                                        @endforeach
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <div class="form-group row mb-0">
                                <div class="col-md-6 offset-md-4">
                                    <button type="submit" class="btn btn-primary">
                                        Update Permissions
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
