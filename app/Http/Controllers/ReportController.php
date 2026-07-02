<?php

namespace App\Http\Controllers;

use App\Models\DataDictionary;
use App\Models\ReportTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    /**
     * Render the Query Builder view.
     */
    public function index(): View
    {

        // Fetch all fields in the data dictionary grouped by table name
        $dictionary = DataDictionary::query()
            ->orderBy('table_name')
            ->orderBy('column_name')
            ->get()
            ->groupBy('table_name');

        $templates = ReportTemplate::query()
            ->with('creator')
            ->orderBy('name')
            ->get();

        return view('reports.builder', compact('dictionary', 'templates'));
    }

    /**
     * Preview report data (up to 50 rows).
     */
    public function preview(Request $request): JsonResponse
    {
        $config = $request->validate([
            'query_config' => 'required|array',
        ])['query_config'];

        try {
            $queryResult = $this->buildQuery($config, 50);
            return response()->json([
                'success' => true,
                'headers' => $queryResult['headers'],
                'rows' => $queryResult['rows'],
                'sql' => $queryResult['sql'],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Export report data as a CSV stream.
     */
    public function export(Request $request): StreamedResponse
    {
        $config = json_decode($request->input('query_config'), true);
        if (!$config) {
            abort(422, 'Invalid query configuration.');
        }

        $queryResult = $this->buildQuery($config, 10000);
        $headers = $queryResult['headers'];
        $rows = $queryResult['rows'];

        $response = new StreamedResponse(function () use ($headers, $rows) {
            $handle = fopen('php://output', 'w');
            
            // Add UTF-8 BOM for proper Excel encoding
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            // Column Header labels
            fputcsv($handle, array_values($headers));

            foreach ($rows as $row) {
                $rowData = [];
                foreach (array_keys($headers) as $key) {
                    $rowData[] = $row->{$key} ?? '';
                }
                fputcsv($handle, $rowData);
            }

            fclose($handle);
        });

        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="scomm_custom_report_' . date('Ymd_His') . '.csv"');

        return $response;
    }

    /**
     * Save a query template.
     */
    public function saveTemplate(Request $request): JsonResponse
    {
        $data = $request->validate([
            'id' => 'nullable|integer|exists:report_templates,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'query_config' => 'required|array',
        ]);

        $template = ReportTemplate::updateOrCreate(
            ['id' => $data['id'] ?? null],
            [
                'name' => $data['name'],
                'description' => $data['description'],
                'query_config' => $data['query_config'],
                'created_by' => auth()->id() ?? 1,
            ]
        );

        return response()->json([
            'success' => true,
            'template' => $template,
        ]);
    }

    /**
     * Delete a template.
     */
    public function deleteTemplate(ReportTemplate $template): JsonResponse
    {
        $template->delete();
        return response()->json(['success' => true]);
    }

    /**
     * AST Query Compiler Engine (Strict Security Enforced).
     */
    private function buildQuery(array $config, int $limit): array
    {
        $selectedFields = $config['selected_fields'] ?? [];
        if (empty($selectedFields)) {
            throw new \Exception('No fields selected. Please select at least one column.');
        }

        // Get whitelist of all valid tables & columns from the Data Dictionary
        $dictionary = DataDictionary::all(['table_name', 'column_name', 'business_name']);
        $whitelistedFields = $dictionary->map(fn($d) => "{$d->table_name}.{$d->column_name}")->toArray();
        $labelMap = $dictionary->pluck('business_name', 'table_name.column_name')->toArray();

        // 1. Determine involved tables
        $tables = [];
        foreach ($selectedFields as $f) {
            $fieldKey = "{$f['table']}.{$f['column']}";
            if (!in_array($fieldKey, $whitelistedFields, true)) {
                throw new \Exception("Unauthorized field access: {$fieldKey}");
            }
            $tables[] = $f['table'];
        }

        // Extract tables from filters
        $filterTables = $this->extractFilterTables($config['filters'] ?? []);
        $tables = array_unique(array_merge($tables, $filterTables));

        if (empty($tables)) {
            throw new \Exception('No tables detected in the query request.');
        }

        // 2. Select a base table
        $baseTable = 'client';
        if (in_array('collection', $tables, true)) {
            $baseTable = 'collection';
        } elseif (in_array('risk', $tables, true)) {
            $baseTable = 'risk';
        } elseif (in_array('client_log', $tables, true)) {
            $baseTable = 'client_log';
        } elseif (in_array('monthly_summary_discontinued', $tables, true)) {
            $baseTable = 'monthly_summary_discontinued';
        } elseif (in_array('monthly_summary', $tables, true)) {
            $baseTable = 'monthly_summary';
        }

        $query = DB::table($baseTable);

        // 3. Perform Left Joins safely
        foreach ($tables as $table) {
            if ($table === $baseTable) {
                continue;
            }

            // Always join on client_id for simplicity and security
            $query->leftJoin($table, "{$baseTable}.client_id", '=', "{$table}.client_id");
        }

        // 4. Select Columns (with aggregation support)
        $selects = [];
        $headers = [];

        foreach ($selectedFields as $f) {
            $tbl = $f['table'];
            $col = $f['column'];
            $alias = $f['alias'] ?? $labelMap["{$tbl}.{$col}"] ?? "{$tbl}_{$col}";
            
            // Safe alias naming
            $cleanAlias = preg_replace('/[^a-zA-Z0-9_]/', '_', strtolower($alias));
            $headers[$cleanAlias] = $alias;

            $aggregate = strtolower($f['aggregate'] ?? '');
            if (in_array($aggregate, ['sum', 'avg', 'count', 'max', 'min'], true)) {
                $selects[] = DB::raw("{$aggregate}({$tbl}.{$col}) as {$cleanAlias}");
            } else {
                $selects[] = "{$tbl}.{$col} as {$cleanAlias}";
            }
        }

        $query->select($selects);

        // 5. Apply Group By if aggregations exist
        $hasAggregation = collect($selectedFields)->contains(fn($f) => !empty($f['aggregate']));
        if ($hasAggregation) {
            $groupBys = [];
            foreach ($selectedFields as $f) {
                if (empty($f['aggregate'])) {
                    $groupBys[] = "{$f['table']}.{$f['column']}";
                }
            }
            if (!empty($groupBys)) {
                $query->groupBy($groupBys);
            }
        }

        // 6. Apply Filters (Logical Condition AST Compiler)
        if (!empty($config['filters']) && $this->hasConditions($config['filters'])) {
            $query->where(function ($q) use ($config, $whitelistedFields) {
                $this->compileFilterGroup($q, $config['filters'], $whitelistedFields);
            });
        }

        // 7. Limit results
        $query->limit($limit);

        // Fetch SQL string for preview
        $sql = $query->toSql();
        $bindings = $query->getBindings();
        foreach ($bindings as $binding) {
            $value = is_numeric($binding) ? $binding : "'" . addslashes($binding) . "'";
            $sql = preg_replace('/\?/', $value, $sql, 1);
        }

        $rows = $query->get();

        return [
            'headers' => $headers,
            'rows' => $rows,
            'sql' => $sql,
        ];
    }

    /**
     * Recursively compile filters JSON tree into Laravel Query Builder conditions.
     */
    private function compileFilterGroup($query, array $group, array $whitelistedFields): void
    {
        $logicalOperator = strtoupper($group['logical_operator'] ?? 'AND');
        if (!in_array($logicalOperator, ['AND', 'OR'], true)) {
            $logicalOperator = 'AND';
        }

        $conditions = $group['conditions'] ?? [];

        foreach ($conditions as $c) {
            // If it is a nested group, recurse
            if (isset($c['logical_operator'])) {
                if (!$this->hasConditions($c)) {
                    continue;
                }
                if ($logicalOperator === 'OR') {
                    $query->orWhere(function ($q) use ($c, $whitelistedFields) {
                        $this->compileFilterGroup($q, $c, $whitelistedFields);
                    });
                } else {
                    $query->where(function ($q) use ($c, $whitelistedFields) {
                        $this->compileFilterGroup($q, $c, $whitelistedFields);
                    });
                }
                continue;
            }

            // Standard constraint compilation
            $tbl = $c['table'] ?? '';
            $col = $c['column'] ?? '';
            $op = strtolower($c['operator'] ?? '=');
            $val = $c['value'] ?? '';

            $fieldKey = "{$tbl}.{$col}";
            if (!in_array($fieldKey, $whitelistedFields, true)) {
                continue; // Skip unauthorized fields
            }

            $opMap = [
                '=' => '=',
                '!=' => '!=',
                '>' => '>',
                '<' => '<',
                '>=' => '>=',
                '<=' => '<=',
                'contains' => 'like',
                'starts_with' => 'like',
                'ends_with' => 'like',
                'in' => 'in',
                'not_in' => 'not in',
                'is_null' => 'is_null',
                'is_not_null' => 'is_not_null',
            ];

            $mappedOp = $opMap[$op] ?? '=';

            // Format values for string wildcard matching
            if ($op === 'contains') {
                $val = "%{$val}%";
            } elseif ($op === 'starts_with') {
                $val = "{$val}%";
            } elseif ($op === 'ends_with') {
                $val = "%{$val}";
            }

            if ($mappedOp === 'in') {
                $valArray = is_array($val) ? $val : array_map('trim', explode(',', $val));
                if ($logicalOperator === 'OR') {
                    $query->orWhereIn("{$tbl}.{$col}", $valArray);
                } else {
                    $query->whereIn("{$tbl}.{$col}", $valArray);
                }
            } elseif ($mappedOp === 'not in') {
                $valArray = is_array($val) ? $val : array_map('trim', explode(',', $val));
                if ($logicalOperator === 'OR') {
                    $query->orWhereNotIn("{$tbl}.{$col}", $valArray);
                } else {
                    $query->whereNotIn("{$tbl}.{$col}", $valArray);
                }
            } elseif ($mappedOp === 'is_null') {
                if ($logicalOperator === 'OR') {
                    $query->orWhereNull("{$tbl}.{$col}");
                } else {
                    $query->whereNull("{$tbl}.{$col}");
                }
            } elseif ($mappedOp === 'is_not_null') {
                if ($logicalOperator === 'OR') {
                    $query->orWhereNotNull("{$tbl}.{$col}");
                } else {
                    $query->whereNotNull("{$tbl}.{$col}");
                }
            } else {
                if ($logicalOperator === 'OR') {
                    $query->orWhere("{$tbl}.{$col}", $mappedOp, $val);
                } else {
                    $query->where("{$tbl}.{$col}", $mappedOp, $val);
                }
            }
        }
    }

    /**
     * Recursively find all tables involved in filter criteria.
     */
    private function extractFilterTables(array $group): array
    {
        $tables = [];
        $conditions = $group['conditions'] ?? [];

        foreach ($conditions as $c) {
            if (isset($c['logical_operator'])) {
                $tables = array_merge($tables, $this->extractFilterTables($c));
            } elseif (isset($c['table'])) {
                $tables[] = $c['table'];
            }
        }

        return array_unique($tables);
    }

    /**
     * Recursively determine if the filter structure contains any actual query criteria.
     */
    private function hasConditions(array $group): bool
    {
        $conditions = $group['conditions'] ?? [];
        foreach ($conditions as $c) {
            if (isset($c['logical_operator'])) {
                if ($this->hasConditions($c)) {
                    return true;
                }
            } else {
                if (!empty($c['table']) && !empty($c['column'])) {
                    return true;
                }
            }
        }
        return false;
    }
}
