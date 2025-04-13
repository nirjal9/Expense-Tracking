<x-guest-layout>
    <form method="POST" action="{{ route('register.income') }}">
        @csrf

        <h2 class="text-xl font-bold mb-4 text-white">Enter Your Monthly Income</h2>

        <div>
            <x-input-label for="income" :value="__('Income')" class="text-white" />
            <x-text-input id="income" class="block mt-1 w-full" type="number" name="income" required />
            <x-input-error :messages="$errors->get('income')" class="mt-2"/>
        </div>
        <div class="mt-4">
            <label for="income_type" class="text-white font-medium text-sm">Income Type</label>
            <select name="income_type" id="income_type" class="block mt-1 w-full">
                <option value="monthly">Monthly</option>
                <option value="yearly">Yearly</option>
            </select>
            <x-input-error :messages="$errors->get('income_type')" class="mt-2"/>
        </div>
        <div class="mt-6">
            <x-primary-button>Next</x-primary-button>
        </div>
    </form>
</x-guest-layout>
