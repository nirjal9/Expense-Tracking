<x-guest-layout>
    <form method="POST" action="{{ route('register.budget') }}">
        @csrf

        <h2 class="text-xl font-bold mb-4 text-white">Allocate Budget to Categories</h2>

        @if($errors->any())
            <div class="bg-red-500 text-white p-4 rounded-lg mb-6">
                <ul>
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @foreach($categories as $category)
            <div class="mt-4">
                <x-input-label for="category-{{ $category->id }}" :value="$category->name . ' (%)'" class="text-white" />
                <x-text-input
                    id="category-{{ $category->id }}"
                    class="block mt-1 w-full"
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
        <div class="mt-4 p-4 bg-gray-700 rounded-lg">
            <p class="text-white">Total Allocated: <span id="totalPercentage">0</span>%</p>
        </div>
        <div class="mt-6">
            <x-primary-button>Submit Budget Allocation</x-primary-button>
        </div>
    </form>
    <script>
        function calculateTotal() {
            let total = 0;
            const inputs = document.querySelectorAll('input[type="number"]');
            inputs.forEach(input => {
                total += parseFloat(input.value) || 0;
            });

            const totalSpan = document.getElementById('totalPercentage');
            totalSpan.textContent = total.toFixed(2);
        }

        document.addEventListener('DOMContentLoaded', calculateTotal);
    </script>
</x-guest-layout>

