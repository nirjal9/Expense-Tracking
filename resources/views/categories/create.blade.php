@extends('layouts.app')

@section('content')
    <div class="container">
        <h1 class="mb-4">Create Category</h1>

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('categories.store') }}" method="POST">
            @csrf

            <div class="form-group mb-4">
                <h4>Predefined Categories</h4>
                <div class="row">
                    @foreach($predefinedCategories as $category)
                        <div class="col-md-4 mb-2">
                            <div class="card">
                                <div class="card-body">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="predefined_category"
                                               id="predefined-{{ $category->id }}" value="{{ $category->id }}">
                                        <label class="form-check-label" for="predefined-{{ $category->id }}">
                                            {{ $category->name }}
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="form-group">
                <h4>Or Create Custom Category</h4>
                <div class="form-group">
                    <label for="name">Category Name</label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                           value="{{ old('name') }}">
                    @error('name')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                    @enderror
                </div>

                <div class="form-group mt-3">
                    <label for="budget_percentage">Budget Allocation (%)</label>
                    <input type="number" name="budget_percentage"
                           class="form-control @error('budget_percentage') is-invalid @enderror"
                           value="{{ old('budget_percentage') }}" min="0" max="100" required>
                    @error('budget_percentage')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                    @enderror
                </div>
            </div>

            <button type="submit" class="btn btn-success mt-3">Save</button>
        </form>
    </div>
@endsection

