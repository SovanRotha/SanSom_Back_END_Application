<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminCategoryController extends Controller
{
    //
    public function index()
    {
        $categories = Category::with('parent')
            ->withCount([
                'transactions as users_count' => function ($query) {
                    $query->select(
                        DB::raw('COUNT(DISTINCT user_id)')
                    );
                }
            ])
            ->latest()
            ->get();

        return response()->json([
            'message' => 'Categories retrieved successfully',
            'total' => $categories->count(),
            'categories' => $categories,
        ]);
    }

    /**
     * Create a system category
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'icon' => 'nullable|string|max:255',
            'color' => 'nullable|string|max:50',
            'parent_id' => 'nullable|exists:categories,id',
        ]);

        $category = Category::create([
            'name' => $validated['name'],
            'icon' => $validated['icon'] ?? null,
            'color' => $validated['color'] ?? null,
            'parent_id' => $validated['parent_id'] ?? null,

            // Important: this is an admin/system category
            'is_system' => true,
            'user_id' => null,
        ]);

        return response()->json([
            'message' => 'System category created successfully',
            'category' => $category,
        ], 201);
    }

    /**
     * Get one category
     */
    public function show($id)
    {
        $category = Category::with('parent')
            ->withCount([
                'transactions as users_count' => function ($query) {
                    $query->select(
                        DB::raw('COUNT(DISTINCT user_id)')
                    );
                }
            ])
            ->findOrFail($id);

        return response()->json([
            'message' => 'Category retrieved successfully',
            'category' => $category,
        ]);
    }

    /**
     * Update a system category
     */
    public function update(Request $request, $id)
    {
        $category = Category::where('is_system', true)
            ->findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'icon' => 'nullable|string|max:255',
            'color' => 'nullable|string|max:50',
            'parent_id' => 'nullable|exists:categories,id',
        ]);

        $category->update([
            'name' => $validated['name'],
            'icon' => $validated['icon'] ?? $category->icon,
            'color' => $validated['color'] ?? $category->color,
            'parent_id' => $validated['parent_id'] ?? $category->parent_id,
        ]);

        return response()->json([
            'message' => 'System category updated successfully',
            'category' => $category,
        ]);
    }

    /**
     * Delete a system category
     */
    public function destroy($id)
    {
        $category = Category::where('is_system', true)
            ->findOrFail($id);

        $category->delete();

        return response()->json([
            'message' => 'System category deleted successfully',
        ]);
    }
}
