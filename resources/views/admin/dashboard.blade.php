@extends('layouts.app')
@section('content')
    <div class="container">
        <h1 class="mb-4">Admin Dashboard</h1>

        <div class="alert alert-info">
            Welcome, {{ Auth::user()->name }}!
        </div>
        <div class="card">
            <div class="card-header">Admin Controls</div>
            <div class="card-body">
                <ul>
                    <li><a href="{{ route('admin.users') }}">Manage Users</a></li>
                </ul>
            </div>
        </div>
    </div>
    <table class="table">
        <thead>
        <tr>
            <th>Category Name</th>
            <th>Created By</th>
            <th>Users Using This Category</th>
            <th>Actions</th>
        </tr>
        </thead>
        <tbody>
        @foreach ($categories as $category)
            <tr>
                <td>{{ $category->name }}</td>
                <td>{{ $category->creator->name ?? 'Unknown' }}</td>
                <td>{{ $category->users_count }}</td>
                <td>
                    @if(Auth::user()->hasRole('admin'))
                        <div class="btn-group" role="group">
                            <!-- Soft Delete Button -->
                            <form action="{{ route('categories.destroy', $category->id) }}" method="POST" style="display:inline;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-warning btn-sm"
                                        onclick="return confirm('Are you sure? This will soft delete the category.')">
                                    Soft Delete
                                </button>
                            </form>

                            <!-- Force Delete Button -->
                            <form action="{{ route('categories.forceDelete', $category->id) }}" method="POST" style="display:inline;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm"
                                        onclick="return confirm('WARNING: This will permanently delete the category and cannot be undone. Are you sure?')">
                                    Force Delete
                                </button>
                            </form>
                        </div>
                    @endif
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
@endsection

