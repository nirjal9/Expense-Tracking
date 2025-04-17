@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h3>Income</h3>
                            <a href="{{ route('incomes.create') }}" class="btn btn-primary">Add Income</a>
                        </div>
                    </div>

                    <div class="card-body">
                        @if (session('success'))
                            <div class="alert alert-success">
                                {{ session('success') }}
                            </div>
                        @endif

                        <div class="mb-4">
                            <h4>Total Monthly Income: Rs{{ number_format(auth()->user()->total_income, 2) }}</h4>
                        </div>

                        @if($incomes->isEmpty())
                            <p>No income entries added yet.</p>
                        @else
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Description</th>
                                        <th>Amount</th>
                                        <th>Actions</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @foreach($incomes as $income)
                                        <tr>
                                            <td>{{ $income->date->format('M d, Y') }}</td>
                                            <td>{{ $income->description }}</td>
                                            <td>Rs{{ number_format($income->amount, 2) }}</td>
                                            <td>
                                                <a href="{{ route('incomes.edit', $income) }}" class="btn btn-sm btn-primary">Edit</a>
                                                <form action="{{ route('incomes.destroy', $income) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this income entry?')">Delete</button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
