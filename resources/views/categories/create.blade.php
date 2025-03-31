@extends('layouts.app')

@section('content')
    <div class="container">
        <h1 class="mb-4">Create Category</h1>

        <form action="{{ route('categories.store') }}" method="POST">
            @csrf
            <div class="form-group">
                <label for="name">Category Name</label>
                <input type="text" name="name" class="form-control" required>
            </div>

            <div class="form-group mt-3">
                <label for="budget_percentage">Budget Allocation (%)</label>
                <input type="number" name="budget_percentage" class="form-control" min="0" max="100" required>
            </div>

            <button type="submit" class="btn btn-success mt-3">Save</button>
        </form>
    </div>
@endsection

