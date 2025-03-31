@extends('layouts.app')
@section('content')
    <div class="container">
        <h2>Select Your Expense Categories</h2>

        <form method="POST" action="{{ route('register.categories') }}">
            @csrf

            <h4>Predefined Categories</h4>
            @foreach($categories as $category)
                <div>
                    <input type="checkbox" name="categories[]" value="{{ $category->id }}" id="category-{{ $category->id }}">
                    <label for="category-{{ $category->id }}">{{ $category->name }}</label>
                </div>
            @endforeach
            <h4>Add a custom category</h4>
            <input type="text" name="custom_category" placeholder="Enter custom category" required>
{{--            <h4>Custom Categories</h4>--}}
{{--            @foreach($customCategories as $category)--}}
{{--                <div>--}}
{{--                    <input type="checkbox" name="categories[]" value="{{ $category->id }}" id="custom-category-{{ $category->id }}" checked>--}}
{{--                    <label for="custom-category-{{ $category->id }}">{{ $category->name }}</label>--}}
{{--                </div>--}}
{{--            @endforeach--}}

{{--            <h4>Add a Custom Category</h4>--}}
{{--            <input type="text" name="custom_category" placeholder="Enter custom category">--}}

            <button type="submit">Next</button>
        </form>
    </div>
@endsection
