<x-guest-layout>
    <form method="POST" action="{{ route('expenses.store') }}">
        @csrf
        <h2 class="text-xl font-bold mb-4">Create a New Expense</h2>
        <div>
            <label for="category_id">Category</label>
            <select name="category_id" id="category_id" required>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                @endforeach
            </select>
            @error('category_id')
            <p class="text-red-500">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label for="amount">Amount</label>
            <input type="number" name="amount" id="amount" step="0.01" required>
            @error('amount')
            <p class="text-red-500">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="description">Description (optional)</label>
            <input type="text" name="description" id="description">
        </div>
        <div>
            <label for="date">Date</label>
            <input type="date" name="date" id="date" required>
            @error('date')
            <p class="text-red-500">{{ $message }}</p>
            @enderror
        </div>

        <button type="submit">Save Expense</button>
    </form>
</x-guest-layout>
