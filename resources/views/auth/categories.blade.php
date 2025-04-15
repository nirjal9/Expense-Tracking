<x-guest-layout>
    <form method="POST" action="{{ route('register.categories') }}">
        @csrf

        <h2 class="text-xl font-bold mb-4 text-white">Select Your Expense Categories</h2>

        @if($errors->any())
            <div class="bg-red-500 text-white p-4 rounded-lg mb-6">
                <ul>
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="mb-6">
            <h4 class="text-lg font-semibold mb-4 text-white">Predefined Categories</h4>
            <div class="space-y-3">
                @foreach($categories as $category)
                    <div class="flex items-center">
                        <input type="checkbox"
                               name="categories[]"
                               value="{{ $category->id }}"
                               id="category-{{ $category->id }}"
                               class="rounded border-gray-600 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <label for="category-{{ $category->id }}" class="ml-2 text-white">{{ $category->name }}</label>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="mb-6">
            <h4 class="text-lg font-semibold mb-4 text-white">Add a custom category (optional)</h4>
            <x-text-input type="text"
                          name="custom_category"
                          placeholder="Enter custom category"
                          class="block w-full"/>
        </div>

        <div class="mt-6">
            <x-primary-button>Next</x-primary-button>
        </div>
    </form>
</x-guest-layout>
