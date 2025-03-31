@extends('layouts.app')

@section('content')
    <div class="container">
        <h2>Allocate Budget to Categories</h2>

        <form method="POST" action="{{ route('register.budget') }}">
            @csrf

            @foreach($categories as $category)
                <div class="mb-4">
                    <label for="category-{{ $category->id }}">{{ $category->name }} (%):</label>
                    <input type="number" name="percentages[{{ $category->id }}]" id="category-{{ $category->id }}" required>
                </div>
            @endforeach

            <button type="submit">Submit</button>
        </form>
    </div>
@endsection

