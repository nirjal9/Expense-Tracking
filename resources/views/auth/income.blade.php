<x-guest-layout>
    <form method="POST" action="{{ route('register.income') }}" class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-lg">
        @csrf

        <h2 class="text-xl font-bold mb-4 text-gray-900 dark:text-white">Enter Your Monthly Income</h2>

        <div>
            <x-input-label for="income" :value="__('Income')" class="text-gray-700 dark:text-gray-300" />
            <x-text-input id="income"
                          class="block mt-1 w-full bg-white dark:bg-gray-700 text-gray-900 dark:text-white border-gray-300 dark:border-gray-600 focus:border-indigo-500 dark:focus:border-indigo-400 focus:ring-indigo-500 dark:focus:ring-indigo-400 rounded-md shadow-sm"
                          type="number"
                          name="income"
                          step="0.01"
                          min="0"
                          max="999999999999.99"
                          placeholder="Enter your income amount"
                          required />
            <x-input-error :messages="$errors->get('income')" class="mt-2"/>
        </div>
        <div class="mt-4">
            <label for="income_type" class="text-gray-700 dark:text-gray-300 font-medium text-sm">Income Type</label>
            <select name="income_type" id="income_type" class="block mt-1 w-full bg-white dark:bg-gray-700 text-gray-900 dark:text-white border-gray-300 dark:border-gray-600 focus:border-indigo-500 dark:focus:border-indigo-400 focus:ring-indigo-500 dark:focus:ring-indigo-400 rounded-md shadow-sm">
                <option value="monthly">Monthly</option>
                <option value="yearly">Yearly</option>
            </select>
            <x-input-error :messages="$errors->get('income_type')" class="mt-2"/>
        </div>
        <div class="mt-6">
            <x-primary-button class="w-full justify-center">Next</x-primary-button>
        </div>
    </form>
</x-guest-layout>
