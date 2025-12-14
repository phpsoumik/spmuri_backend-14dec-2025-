<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Expense;
use App\Models\DailyIncome;

class ExpenseController extends Controller
{
    public function index()
    {
        try {
            $expenses = DB::table('expenses')
                ->orderBy('created_at', 'desc')
                ->get();
                
            return response()->json([
                'success' => true,
                'message' => 'success',
                'data' => $expenses->toArray(),
                'count' => $expenses->count()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'error',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'category' => 'required|string',
            'amount' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'date' => 'required|date'
        ]);

        try {
            // Get total daily income from daily_incomes table
            $totalDailyIncome = DB::table('daily_incomes')->sum('amount');
            
            // Create expense
            $expense = Expense::create([
                'category' => $request->category,
                'amount' => $request->amount,
                'description' => $request->description,
                'date' => \Carbon\Carbon::parse($request->date)->format('Y-m-d'),
                'quantity_kg' => $request->quantity_kg,
                'rate_per_kg' => $request->rate_per_kg
            ]);
            
            // Calculate profit after adding this expense
            $totalExpenses = DB::table('expenses')->sum('amount');
            $profit = $totalDailyIncome - $totalExpenses;
            
            return response()->json([
                'message' => 'success',
                'data' => $expense,
                'total_income' => $totalDailyIncome,
                'total_expenses' => $totalExpenses,
                'profit' => $profit
            ], 201);
            
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'error',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    public function getAvailableBalance($date)
    {
        try {
            // Get daily income from sale invoices (paid amount)
            $dailyIncome = DB::table('sale_invoices')
                ->whereDate('created_at', $date)
                ->sum('paid_amount');
            
            // Get existing expenses for the day
            $dailyExpense = DB::table('expenses')
                ->whereDate('created_at', $date)
                ->sum('amount');
            
            $availableBalance = $dailyIncome - $dailyExpense;
            
            return response()->json([
                'date' => $date,
                'daily_income' => $dailyIncome,
                'daily_expense' => $dailyExpense,
                'available_balance' => $availableBalance
            ]);
            
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        try {
            $expense = DB::table('expenses')->where('id', $id)->first();
            
            if (!$expense) {
                return response()->json(['error' => 'Expense not found'], 404);
            }
            
            return response()->json([
                'success' => true,
                'data' => $expense
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'category' => 'required|string',
            'amount' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'date' => 'required|date'
        ]);

        try {
            $expense = Expense::find($id);
            
            if (!$expense) {
                return response()->json([
                    'message' => 'error',
                    'error' => 'Expense not found'
                ], 404);
            }
            
            $expense->update([
                'category' => $request->category,
                'amount' => $request->amount,
                'description' => $request->description,
                'date' => \Carbon\Carbon::parse($request->date)->format('Y-m-d'),
                'quantity_kg' => $request->quantity_kg,
                'rate_per_kg' => $request->rate_per_kg
            ]);
            
            // Calculate updated totals
            $totalDailyIncome = DB::table('daily_incomes')->sum('amount');
            $totalExpenses = DB::table('expenses')->sum('amount');
            $profit = $totalDailyIncome - $totalExpenses;
            
            return response()->json([
                'message' => 'success',
                'data' => $expense->fresh(),
                'total_income' => $totalDailyIncome,
                'total_expenses' => $totalExpenses,
                'profit' => $profit
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'error',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $deleted = DB::table('expenses')->where('id', $id)->delete();
            
            if (!$deleted) {
                return response()->json(['error' => 'Expense not found'], 404);
            }
            
            return response()->json(['message' => 'Expense deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function reports()
    {
        try {
            $expenses = DB::table('expenses')
                ->selectRaw('category, SUM(amount) as total_amount, COUNT(*) as count')
                ->groupBy('category')
                ->get();
                
            return response()->json([
                'success' => true,
                'data' => $expenses
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function getTotals()
    {
        try {
            $totalIncome = DB::table('daily_incomes')->sum('amount');
            $totalExpenses = DB::table('expenses')->sum('amount');
            $profit = $totalIncome - $totalExpenses;
            
            return response()->json([
                'success' => true,
                'data' => [
                    'total_income' => $totalIncome,
                    'total_expenses' => $totalExpenses,
                    'profit' => $profit
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}