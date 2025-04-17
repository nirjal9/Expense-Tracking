<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Income;
use Illuminate\Support\Facades\Auth;

class IncomeController extends Controller
{
    public function index()
    {
        $incomes = Auth::user()->incomes()->orderBy('date', 'desc')->get();
        return view('incomes.index', compact('incomes'));
    }
    public function create()
    {
        return view('incomes.create');
    }
    public function store(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0|max:999999999999.99',
            'description' => 'required|string|max:255',
            'date' => ['required', 'date', 'before:tomorrow'],
        ]);

        Auth::user()->incomes()->create($request->all());

        return redirect()->route('incomes.index')
            ->with('success', 'Income added successfully.');
    }
    public function edit(Income $income)
    {
        if ($income->user_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }

        return view('incomes.edit', compact('income'));
    }
    public function update(Request $request, Income $income)
    {
        if ($income->user_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'amount' => 'required|numeric|min:0|max:999999999999.99',
            'description' => 'required|string|max:255',
            'date' => ['required', 'date', 'before:tomorrow'],
        ]);

        $income->update($request->all());

        return redirect()->route('incomes.index')
            ->with('success', 'Income updated successfully.');
    }
    public function destroy(Income $income)
    {
        if ($income->user_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }

        $income->delete();

        return redirect()->route('incomes.index')
            ->with('success', 'Income deleted successfully.');
    }
}
