<?php

namespace App\Http\Controllers;

use App\Models\DailyIncome;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class DailyIncomeController extends Controller
{
    public function index(): JsonResponse
    {
        try {
            $incomes = DailyIncome::orderBy('date', 'desc')->get();
            
            return response()->json([
                'success' => true,
                'data' => $incomes,
                'total' => $incomes->sum('amount')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch daily incomes',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            \Log::info('Daily Income Store Request:', $request->all());
            
            $validated = $request->validate([
                'customerName' => 'required|string|max:255',
                'date' => 'required|date',
                'amount' => 'required|numeric|min:0',
                'purpose' => 'nullable|string'
            ]);

            $income = DailyIncome::create([
                'customer_name' => $validated['customerName'],
                'date' => \Carbon\Carbon::parse($validated['date'])->format('Y-m-d'),
                'amount' => $validated['amount'],
                'purpose' => $validated['purpose']
            ]);

            return response()->json([
                'success' => true,
                'data' => $income,
                'message' => 'Daily income created successfully'
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create daily income',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show(DailyIncome $dailyIncome): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $dailyIncome
        ]);
    }

    public function destroy($id): JsonResponse
    {
        try {
            \Log::info('Deleting daily income with ID: ' . $id);
            
            $dailyIncome = DailyIncome::find($id);
            
            if (!$dailyIncome) {
                return response()->json([
                    'success' => false,
                    'message' => 'Daily income not found'
                ], 404);
            }
            
            $deleted = $dailyIncome->delete();
            
            \Log::info('Delete result: ' . ($deleted ? 'success' : 'failed'));
            
            if ($deleted) {
                return response()->json([
                    'success' => true,
                    'message' => 'Daily income deleted successfully'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to delete daily income'
                ], 400);
            }
        } catch (\Exception $e) {
            \Log::error('Delete daily income error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete daily income',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function total(): JsonResponse
    {
        try {
            $total = DailyIncome::sum('amount');
            
            return response()->json([
                'success' => true,
                'total' => $total
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to calculate total income',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function balance(): JsonResponse
    {
        try {
            $totalIncome = DailyIncome::sum('amount');
            $totalExpenses = \DB::table('expenses')->sum('amount');
            $balance = $totalIncome - $totalExpenses;
            
            return response()->json([
                'success' => true,
                'total_income' => $totalIncome,
                'total_expenses' => $totalExpenses,
                'balance' => $balance
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to calculate balance',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'id' => 'required|integer|exists:daily_incomes,id',
                'customerName' => 'required|string|max:255',
                'date' => 'required|date',
                'amount' => 'required|numeric|min:0',
                'purpose' => 'nullable|string'
            ]);

            $dailyIncome = DailyIncome::find($validated['id']);
            
            if (!$dailyIncome) {
                return response()->json([
                    'success' => false,
                    'message' => 'Daily income not found'
                ], 404);
            }

            $dailyIncome->update([
                'customer_name' => $validated['customerName'],
                'date' => \Carbon\Carbon::parse($validated['date'])->format('Y-m-d'),
                'amount' => $validated['amount'],
                'purpose' => $validated['purpose']
            ]);

            return response()->json([
                'success' => true,
                'data' => $dailyIncome,
                'message' => 'Daily income updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update daily income',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}