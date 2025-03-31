@extends('layouts.app')

@section('content')
    <div class="container">
        <h1 class="mb-4">Edit Category</h1>

        <form action="{{ route('categories.update', $categoryWithPivot->id) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="form-group">
                <label for="name">Category Name</label>
                <input type="text" name="name" class="form-control" value="{{ $categoryWithPivot->name }}" required>
            </div>

            <div class="form-group mt-3">
                <label for="budget_percentage">Budget Allocation (%)</label>
                <input type="number" name="budget_percentage" class="form-control"
                       value="{{ $categoryWithPivot->pivot->budget_percentage ?? 0 }}" min="0" max="100" required>
            </div>

            <button type="submit" class="btn btn-primary mt-3">Update</button>
        </form>
    </div>
@endsection
