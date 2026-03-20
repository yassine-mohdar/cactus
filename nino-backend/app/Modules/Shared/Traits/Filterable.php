<?php

namespace App\Modules\Shared\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Reusable filter builder trait for admin controllers.
 * Provides a fluent interface for applying search, date, and field filters.
 *
 * Usage in controller:
 *   use Filterable;
 *   $query = $this->applyFilters(Model::query(), $request, [
 *       'search' => ['name', 'email', 'reference_number'],
 *       'exact'  => ['status', 'type', 'payment_method'],
 *       'dates'  => ['created_at'],
 *   ]);
 */
trait Filterable
{
    protected function applyFilters(Builder $query, Request $request, array $config): Builder
    {
        // Full-text search across specified columns
        if ($request->filled('search') && !empty($config['search'] ?? [])) {
            $search = $request->search;
            $columns = $config['search'];
            $query->where(function ($q) use ($search, $columns) {
                foreach ($columns as $col) {
                    $q->orWhere($col, 'like', "%{$search}%");
                }
            });
        }

        // Exact match filters
        foreach ($config['exact'] ?? [] as $field) {
            if ($request->filled($field)) {
                $query->where($field, $request->input($field));
            }
        }

        // Date range filters (from/to)
        foreach ($config['dates'] ?? [] as $field) {
            $fromKey = $field === 'created_at' ? 'from' : "{$field}_from";
            $toKey = $field === 'created_at' ? 'to' : "{$field}_to";
            if ($request->filled($fromKey)) {
                $query->where($field, '>=', $request->input($fromKey) . ' 00:00:00');
            }
            if ($request->filled($toKey)) {
                $query->where($field, '<=', $request->input($toKey) . ' 23:59:59');
            }
        }

        // Boolean filters
        foreach ($config['boolean'] ?? [] as $field) {
            if ($request->filled($field)) {
                $query->where($field, $request->boolean($field));
            }
        }

        // Sorting
        if ($request->filled('sort_by')) {
            $direction = $request->input('sort_dir', 'desc');
            $query->orderBy($request->sort_by, $direction);
        }

        return $query;
    }
}
