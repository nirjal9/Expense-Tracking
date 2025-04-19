@extends('layouts.app')

@section('header')
    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        {{ __('Manage Permissions') }}
    </h2>
@endsection

@section('content')
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-lg font-semibold">Role Permissions</h3>
                        <a href="{{ route('admin.dashboard') }}" class="text-indigo-600 hover:text-indigo-900">
                            Back to Dashboard
                        </a>
                    </div>

                    @if (session('success'))
                        <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
                            {{ session('error') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('permissions.store') }}">
                        @csrf
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Permission</th>
                                    @foreach($roles as $role)
                                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            {{ ucfirst($role->name) }}
                                        </th>
                                    @endforeach
                                </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($permissions as $group => $groupPermissions)
                                    <tr class="bg-gray-50">
                                        <td colspan="{{ count($roles) + 1 }}" class="px-6 py-3">
                                            <span class="text-sm font-semibold text-gray-700">{{ ucfirst($group) }}</span>
                                        </td>
                                    </tr>
                                    @foreach($groupPermissions as $permission)
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {{ $permission->name }}
                                            </td>
                                            @foreach($roles as $role)
                                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                                    <label class="inline-flex items-center">
                                                        <input type="checkbox"
                                                               class="form-checkbox h-5 w-5 text-indigo-600 rounded border-gray-300 focus:ring-indigo-500"
                                                               name="permissions[{{ $role->id }}][]"
                                                               value="{{ $permission->id }}"
                                                            {{ $role->permissions->contains($permission->id) ? 'checked' : '' }}>
                                                    </label>
                                                </td>
                                            @endforeach
                                        </tr>
                                    @endforeach
                                @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-6 flex justify-end">
                            <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                                Update Permissions
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
