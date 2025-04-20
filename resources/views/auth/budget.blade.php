<x-guest-layout>
    <form method="POST" action="{{ route('register.budget') }}" class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-lg">
        @csrf

        <h2 class="text-xl font-bold mb-4 text-gray-900 dark:text-white">Allocate Budget to Categories</h2>

        @if($errors->any())
            <div class="bg-red-500 dark:bg-red-800 text-white p-4 rounded-lg mb-6">
                <ul>
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @foreach($categories as $category)
            <div class="mt-4">
                <x-input-label for="category-{{ $category->id }}" :value="$category->name . ' (%)'" class="text-gray-700 dark:text-gray-300" />
                <x-text-input
                    id="category-{{ $category->id }}"
                    class="block mt-1 w-full bg-white dark:bg-gray-700 text-gray-900 dark:text-white border-gray-300 dark:border-gray-600 focus:border-indigo-500 dark:focus:border-indigo-400 focus:ring-indigo-500 dark:focus:ring-indigo-400 rounded-md shadow-sm"
                    type="number"
                    name="percentages[{{ $category->id }}]"
                    :value="old('percentages.' . $category->id)"
                    step="0.01"
                    min="1"
                    max="100"
                    required
                    onchange="calculateTotal()"
                />
                <x-input-error :messages="$errors->get('percentages.' . $category->id)" class="mt-2" />
            </div>
        @endforeach

        <div class="mt-4 p-4 bg-gray-100 dark:bg-gray-700 rounded-lg">
            <p class="text-gray-900 dark:text-white">Total Allocated: <span id="totalPercentage" class="font-semibold">0</span>%</p>
        </div>

        <div class="mt-6">
            <x-primary-button class="w-full justify-center">Submit Budget Allocation</x-primary-button>
        </div>
    </form>

    <script>
        function calculateTotal() {
            let total = 0;
            document.querySelectorAll('input[type="number"]').forEach(input => {
                total += parseFloat(input.value || 0);
            });
            document.getElementById('totalPercentage').textContent = total.toFixed(2);
        }
    </script>
</x-guest-layout>

