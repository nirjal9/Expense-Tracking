@extends('layouts.app')

@section('header')
    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        {{ __('Admin Dashboard') }}
    </h2>
@endsection

@section('content')
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-semibold mb-4">Total Users</h3>
                    <p class="text-3xl font-bold">{{ $totalUsers }}</p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <h3 class="text-lg font-semibold mb-4">Top 5 Spending Categories</h3>
                        <div class="space-y-4">
                            @foreach($topSpendingCategories as $category)
                                <div class="flex justify-between items-center">
                                    <span>{{ $category->name }}</span>
                                    <span class="font-semibold">₹{{ number_format($category->total_spent, 2) }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <h3 class="text-lg font-semibold mb-4">Top 5 Categories by Users</h3>
                        <div class="space-y-4">
                            @foreach($topUserCategories as $category)
                                <div class="flex justify-between items-center">
                                    <span>{{ $category->name }}</span>
                                    <span class="font-semibold">{{ $category->users_count }} users</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-semibold mb-4">Admin Navigation</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <a href="{{ route('admin.users') }}" class="block p-4 bg-indigo-50 rounded-lg hover:bg-indigo-100 transition">
                            <h4 class="font-medium text-indigo-700">Manage Users</h4>
                            <p class="text-sm text-gray-600">View and manage user accounts</p>
                        </a>
                        <a href="{{ route('admin.categories') }}" class="block p-4 bg-indigo-50 rounded-lg hover:bg-indigo-100 transition">
                            <h4 class="font-medium text-indigo-700">Manage Categories</h4>
                            <p class="text-sm text-gray-600">View and manage expense categories</p>
                        </a>
                        <a href="{{ route('permissions.index') }}" class="block p-4 bg-indigo-50 rounded-lg hover:bg-indigo-100 transition">
                            <h4 class="font-medium text-indigo-700">Manage Permissions</h4>
                            <p class="text-sm text-gray-600">Configure user permissions</p>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

