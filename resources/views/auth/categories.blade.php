<x-guest-layout>
    <form method="POST" action="{{ route('register.categories') }}" class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-lg">
        @csrf

        <h2 class="text-xl font-bold mb-4 text-gray-900 dark:text-white">Select Your Expense Categories</h2>

        @if($errors->any())
            <div class="bg-red-100 dark:bg-red-800 border border-red-400 dark:border-red-600 text-red-700 dark:text-red-200 p-4 rounded-lg mb-6">
                <ul>
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="mb-6">
            <h4 class="text-lg font-semibold mb-4 text-gray-900 dark:text-white">Predefined Categories</h4>
            <div class="space-y-3">
                @foreach($categories as $category)
                    <div class="flex items-center">
                        <input type="checkbox"
                               name="categories[]"
                               value="{{ $category->id }}"
                               id="category-{{ $category->id }}"
                               class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 dark:text-indigo-400 shadow-sm focus:border-indigo-500 dark:focus:border-indigo-400 focus:ring-indigo-500 dark:focus:ring-indigo-400">
                        <label for="category-{{ $category->id }}" class="ml-2 text-gray-900 dark:text-white">{{ $category->name }}</label>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="mb-6">
            <h4 class="text-lg font-semibold mb-4 text-gray-900 dark:text-white">Add a custom category (optional)</h4>
            <x-text-input type="text"
                          name="custom_category"
                          placeholder="Enter custom category"
                          class="block w-full bg-white dark:bg-gray-700 text-gray-900 dark:text-white border-gray-300 dark:border-gray-600 focus:border-indigo-500 dark:focus:border-indigo-400 focus:ring-indigo-500 dark:focus:ring-indigo-400 rounded-md shadow-sm"/>
        </div>

        <div class="mt-6">
            <x-primary-button class="w-full justify-center">Next</x-primary-button>
        </div>
    </form>
</x-guest-layout>
