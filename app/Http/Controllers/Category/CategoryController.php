<?php

namespace App\Http\Controllers\Category;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Category\Category as ModelsCategory;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    /**
     * Get all categories available to the current user
     *
     * Includes:
     * - System categories
     * - User's own categories
     */
    public function index(Request $request)
    {
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'message' => 'Unauthenticated'
                ], 401);
            }

            $categories = ModelsCategory::where('status', 'active')
                ->where(function ($query) use ($user) {
                    $query->where('is_system', true)
                          ->orWhere('user_id', $user->id);
                })
                ->with('children')
                ->get();

            return response()->json([
                'message' => 'Categories retrieved successfully',
                'categories' => $categories
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Failed to retrieve categories',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal Server Error',
            ], 500);
        }
    }


    /**
     * Get one category
     */
    public function show(Request $request, $id)
    {
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'message' => 'Unauthenticated'
                ], 401);
            }

            $category = ModelsCategory::where('id', $id)
                ->where('status', 'active')
                ->where(function ($query) use ($user) {
                    $query->where('is_system', true)
                          ->orWhere('user_id', $user->id);
                })
                ->with('parent', 'children')
                ->first();

            if (!$category) {
                return response()->json([
                    'message' => 'Category not found'
                ], 404);
            }

            return response()->json([
                'message' => 'Category retrieved successfully',
                'category' => $category
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Failed to retrieve category',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal Server Error',
            ], 500);
        }
    }


    /**
     * Create category
     *
     * parent_id = null → main category
     * parent_id = ID   → subcategory
     */
    public function store(Request $request)
    {
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'message' => 'Unauthenticated'
                ], 401);
            }

            $validated = $request->validate([
                'name' => 'required|string|max:100',
                'type' => 'required|in:income,expense',
                'parent_id' => 'nullable|exists:categories,id',
                'icon' => 'nullable|string|max:100',
                'color' => 'nullable|string|max:20',
            ]);

            /*
            |--------------------------------------------------------------------------
            | Check parent category
            |--------------------------------------------------------------------------
            */

            if ($validated['parent_id'] !== null) {

                $parent = ModelsCategory::where('id', $validated['parent_id'])
                    ->where('status', 'active')
                    ->where(function ($query) use ($user) {
                        $query->where('is_system', true)
                              ->orWhere('user_id', $user->id);
                    })
                    ->first();

                if (!$parent) {
                    return response()->json([
                        'message' => 'Parent category not found'
                    ], 404);
                }

                /*
                |--------------------------------------------------------------------------
                | Parent and child must have same type
                |--------------------------------------------------------------------------
                */

                if ($parent->type !== $validated['type']) {
                    return response()->json([
                        'message' => 'Parent category type must match child category type'
                    ], 422);
                }
            }

            /*
            |--------------------------------------------------------------------------
            | Create category
            |--------------------------------------------------------------------------
            */

            $category = ModelsCategory::create([
                'name' => $validated['name'],
                'type' => $validated['type'],
                'user_id' => $user->id,
                'parent_id' => $validated['parent_id'] ?? null,
                'icon' => $validated['icon'] ?? null,
                'color' => $validated['color'] ?? null,
                'is_system' => false,
                'status' => 'active',
            ]);

            return response()->json([
                'message' => 'Category created successfully',
                'category' => $category
            ], 201);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Failed to create category',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal Server Error',
            ], 500);
        }
    }


    /**
     * Update category
     */
    public function update(Request $request, $id)
    {
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'message' => 'Unauthenticated'
                ], 401);
            }

            /*
            |--------------------------------------------------------------------------
            | Only user's own categories can be updated
            |--------------------------------------------------------------------------
            */

            $category = ModelsCategory::where('id', $id)
                ->where('user_id', $user->id)
                ->where('is_system', false)
                ->first();

            if (!$category) {
                return response()->json([
                    'message' => 'Category not found or cannot be modified'
                ], 404);
            }

            $validated = $request->validate([
                'name' => 'sometimes|string|max:100',
                'type' => 'sometimes|in:income,expense',
                'parent_id' => 'nullable|exists:categories,id',
                'icon' => 'nullable|string|max:100',
                'color' => 'nullable|string|max:20',
                'status' => 'sometimes|in:active,inactive',
            ]);

            /*
            |--------------------------------------------------------------------------
            | Prevent category from becoming its own parent
            |--------------------------------------------------------------------------
            */

            if (
                array_key_exists('parent_id', $validated) &&
                $validated['parent_id'] == $category->id
            ) {
                return response()->json([
                    'message' => 'A category cannot be its own parent'
                ], 422);
            }

            /*
            |--------------------------------------------------------------------------
            | Check new parent
            |--------------------------------------------------------------------------
            */

            if (
                array_key_exists('parent_id', $validated) &&
                $validated['parent_id'] !== null
            ) {

                $parent = ModelsCategory::where('id', $validated['parent_id'])
                    ->where('status', 'active')
                    ->where(function ($query) use ($user) {
                        $query->where('is_system', true)
                              ->orWhere('user_id', $user->id);
                    })
                    ->first();

                if (!$parent) {
                    return response()->json([
                        'message' => 'Parent category not found'
                    ], 404);
                }

                $type = $validated['type'] ?? $category->type;

                if ($parent->type !== $type) {
                    return response()->json([
                        'message' => 'Parent category type must match child category type'
                    ], 422);
                }
            }

            $category->update($validated);

            return response()->json([
                'message' => 'Category updated successfully',
                'category' => $category
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Failed to update category',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal Server Error',
            ], 500);
        }
    }


    /**
     * Delete category
     */
    public function destroy(Request $request, $id)
    {
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'message' => 'Unauthenticated'
                ], 401);
            }

            $category = ModelsCategory::where('id', $id)
                ->where('user_id', $user->id)
                ->where('is_system', false)
                ->first();

            if (!$category) {
                return response()->json([
                    'message' => 'Category not found or cannot be deleted'
                ], 404);
            }

            /*
            |--------------------------------------------------------------------------
            | Check children
            |--------------------------------------------------------------------------
            */

            if ($category->children()->exists()) {
                return response()->json([
                    'message' => 'Cannot delete category because it has subcategories'
                ], 422);
            }

            /*
            |--------------------------------------------------------------------------
            | Check transactions
            |--------------------------------------------------------------------------
            */

            if ($category->transactions()->exists()) {
                return response()->json([
                    'message' => 'Cannot delete category because it has transactions'
                ], 422);
            }

            $category->delete();

            return response()->json([
                'message' => 'Category deleted successfully'
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Failed to delete category',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal Server Error',
            ], 500);
        }
    }
}