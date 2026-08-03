<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\DataDictionary;
use App\Models\MonthlySummaryDiscontinued;
use App\Models\SummaryAuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class MonthlySummaryDiscontinuedController extends Controller
{
    public function index(Request $request): View
    {
        $selectedMonth = $request->input('month');
        if (! $selectedMonth) {
            $latestMonth = MonthlySummaryDiscontinued::query()->max('summary_month');
            $selectedMonth = $latestMonth ? \Carbon\Carbon::parse($latestMonth)->format('Y-m') : now()->format('Y-m');
        }

        $selectedMonthDate = \Carbon\Carbon::createFromFormat('Y-m', $selectedMonth)->endOfMonth();

        $summaries = MonthlySummaryDiscontinued::query()
            ->with('client')
            ->whereDate('summary_month', $selectedMonthDate)
            ->get();

        return view('monthly-summary-discontinued.index', [
            'summaries' => $summaries,
            'selectedMonth' => $selectedMonth,
            'clients' => Client::query()
                ->orderBy('client_name')
                ->get(['client_id', 'client_name', 'opus_id']),
            'columns' => $this->columns(),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->monthlySummaryRows($request->input('month')),
            'columns' => $this->columns(),
        ]);
    }

    public function bulkUpdate(Request $request): JsonResponse
    {
        $rows = $request->input('rows', []);

        if (! is_array($rows)) {
            return response()->json([
                'message' => 'Rows payload must be an array.',
            ], 422);
        }

        $rows = collect($rows)
            ->filter(fn ($row) => is_array($row) && ! $this->isBlankRow($row))
            ->map(fn ($row) => $this->normalizeRow($row))
            ->values()
            ->all();

        $validator = Validator::make(['rows' => $rows], $this->rules());

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Please fix the highlighted fields.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $result = DB::transaction(function () use ($rows, $request) {
            $savedCount = 0;
            $savedClients = [];
            $skippedClients = [];
            $savedClientIds = [];

            $user = auth()->user();
            $updatedBy = $user?->email ?? $user?->name ?? 'User';
            $userId = $user?->id;
            $ipAddress = $request->ip();

            foreach ($rows as $row) {
                if ($this->isBlankRow($row)) {
                    continue;
                }

                $attributes = collect($row)
                    ->except(['client_name', '__hotRow'])
                    ->only($this->fillableColumns())
                    ->map(fn ($value) => $value === '' ? null : $value)
                    ->all();

                $clientId = $attributes['client_id'] ?? null;
                $summaryId = $attributes['monthly_summary_discontinued_id'] ?? null;

                if (!$clientId && $summaryId) {
                    $clientId = MonthlySummaryDiscontinued::where('monthly_summary_discontinued_id', $summaryId)->value('client_id');
                }

                $clientName = $row['client_name'] ?? \App\Models\Client::where('client_id', $clientId)->value('client_name') ?? 'Client ID: ' . $clientId;

                $hotRowIndex = isset($row['__hotRow']) ? (int) $row['__hotRow'] : null;
                $rowLabel = $hotRowIndex !== null ? " (Row " . ($hotRowIndex + 1) . ")" : '';

                if ($clientId) {
                    $clientExists = \App\Models\Client::where('client_id', $clientId)->exists();
                    if (!$clientExists) {
                        $skippedClients[] = $clientName . $rowLabel;
                        continue;
                    }
                }

                $savedClients[] = $clientName;
                if ($clientId) {
                    $savedClientIds[] = (int) $clientId;
                }
                unset($attributes['monthly_summary_discontinued_id']);

                if ($summaryId) {
                    $existingModel = MonthlySummaryDiscontinued::find($summaryId);
                    if ($existingModel) {
                        foreach ($attributes as $key => $newValue) {
                            $oldValue = $existingModel->$key;

                            $oldStr = $oldValue === null ? null : (string) $oldValue;
                            $newStr = $newValue === null ? null : (string) $newValue;

                            if ($oldStr !== $newStr) {
                                SummaryAuditLog::create([
                                    'summary_type' => 'discontinued',
                                    'summary_id' => $summaryId,
                                    'client_id' => $existingModel->client_id,
                                    'field_name' => $key,
                                    'old_value' => $oldStr,
                                    'new_value' => $newStr,
                                    'summary_month' => $existingModel->summary_month,
                                    'user_id' => $userId,
                                    'updated_by' => $updatedBy,
                                    'ip_address' => $ipAddress,
                                ]);
                            }
                        }
                        $existingModel->update($attributes);
                    }
                } else {
                    $newModel = MonthlySummaryDiscontinued::updateOrCreate(
                        [
                            'client_id' => $attributes['client_id'],
                            'summary_month' => $attributes['summary_month'],
                        ],
                        $attributes
                    );

                    SummaryAuditLog::create([
                        'summary_type' => 'discontinued',
                        'summary_id' => $newModel->monthly_summary_discontinued_id,
                        'client_id' => $newModel->client_id,
                        'field_name' => 'row_created',
                        'old_value' => null,
                        'new_value' => 'New Discontinued Monthly Summary Row Created',
                        'summary_month' => $newModel->summary_month,
                        'user_id' => $userId,
                        'updated_by' => $updatedBy,
                        'ip_address' => $ipAddress,
                    ]);
                }

                $savedCount++;
            }

            return [
                'saved_count' => $savedCount,
                'saved_clients' => $savedClients,
                'skipped_clients' => $skippedClients,
                'saved_client_ids' => $savedClientIds,
            ];
        });

        $message = "{$result['saved_count']} discontinued summary rows saved.";
        if (!empty($result['skipped_clients'])) {
            $message .= " Edits for the following clients were not saved due to permission restrictions: " . implode(', ', $result['skipped_clients']);
        }

        return response()->json([
            'message' => $message,
            'data' => $this->monthlySummaryRows($request->input('month')),
            'saved_client_ids' => $result['saved_client_ids'] ?? [],
            'saved_count' => $result['saved_count'] ?? 0,
            'skipped_count' => count($result['skipped_clients'] ?? []),
        ]);
    }

    private function monthlySummaryRows($month = null)
    {
        $query = MonthlySummaryDiscontinued::query()
            ->with('client')
            ->orderByDesc('summary_month');

        if ($month) {
            $date = \Carbon\Carbon::createFromFormat('Y-m', $month)->endOfMonth();
            $query->whereDate('summary_month', $date);
        } else {
            $latestMonth = MonthlySummaryDiscontinued::query()->max('summary_month');
            if ($latestMonth) {
                $query->whereDate('summary_month', $latestMonth);
            }
        }

        return $query->get()
            ->map(function (MonthlySummaryDiscontinued $summary) {
                $row = $summary->toArray();
                $row['opus_id'] = $summary->client?->opus_id;
                $row['client_name'] = $summary->client?->client_name;

                foreach (['summary_month', 'nttn_discontinuation_date', 'iig_itc_discontinuation_date'] as $dateField) {
                    $row[$dateField] = optional($summary->{$dateField})->format('Y-m-d');
                }

                return $row;
            })
            ->values();
    }

    private function columns(): array
    {
        $columns = [
            ['key' => 'opus_id', 'label' => 'OPUS ID', 'type' => 'text', 'readOnly' => true],
            ['key' => 'client_name', 'label' => 'Client Name', 'type' => 'text', 'readOnly' => true],
            ['key' => 'summary_month', 'label' => 'Summary Month', 'type' => 'date', 'required' => true],
            
            ['key' => 'opening_os', 'label' => 'Opening OS', 'type' => 'money'],
            ['key' => 'opening_os_nttn', 'label' => 'Opening OS NTTN', 'type' => 'money'],
            ['key' => 'opening_os_iig', 'label' => 'Opening OS IIG', 'type' => 'money'],
            ['key' => 'opening_os_itc', 'label' => 'Opening OS ITC', 'type' => 'money'],
            ['key' => 'opening_os_nix', 'label' => 'Opening OS NIX', 'type' => 'money'],
            
            ['key' => 'target', 'label' => 'Target', 'type' => 'money'],
            ['key' => 'collection_amount', 'label' => 'Collection Amount', 'type' => 'money'],
            ['key' => 'shortfall_target', 'label' => 'Shortfall Target', 'type' => 'money'],
            
            ['key' => 'latest_os', 'label' => 'Latest OS', 'type' => 'money'],
            ['key' => 'latest_os_nttn', 'label' => 'Latest OS NTTN', 'type' => 'money'],
            ['key' => 'latest_os_iig', 'label' => 'Latest OS IIG', 'type' => 'money'],
            ['key' => 'latest_os_itc', 'label' => 'Latest OS ITC', 'type' => 'money'],
            ['key' => 'latest_os_nix', 'label' => 'Latest OS NIX', 'type' => 'money'],
            
            ['key' => 'payment_plan_description', 'label' => 'Payment Plan Description', 'type' => 'text'],
            ['key' => 'visit_remarks', 'label' => 'Visit Remarks', 'type' => 'text'],
            ['key' => 'sales_review_status', 'label' => 'Sales Review Status', 'type' => 'dropdown', 'source' => ['Pending', 'Approved', 'Rejected']],
            ['key' => 'sales_review_remarks', 'label' => 'Sales Review Remarks', 'type' => 'text'],
            ['key' => 'barring_percentage', 'label' => 'Barring %', 'type' => 'numeric'],
            ['key' => 'collection_mrc', 'label' => 'Collection MRC (LIFO)', 'type' => 'money', 'readOnly' => true],
            ['key' => 'collection_backlog', 'label' => 'Collection Backlog (LIFO)', 'type' => 'money', 'readOnly' => true],
            ['key' => 'mrc_shortfall', 'label' => 'MRC Shortfall (LIFO)', 'type' => 'money', 'readOnly' => true],
            ['key' => 'backlog_shortfall', 'label' => 'Backlog Shortfall (LIFO)', 'type' => 'money', 'readOnly' => true],
            ['key' => 'pdc', 'label' => 'PDC', 'type' => 'money'],
            ['key' => 'udc', 'label' => 'UDC', 'type' => 'money'],
            ['key' => 'total_security', 'label' => 'Total Security', 'type' => 'money'],
            ['key' => 'security_coverage', 'label' => 'Security Coverage', 'type' => 'money'],
            ['key' => 'pdc_chq', 'label' => 'PDC Cheque No', 'type' => 'text'],
            ['key' => 'udc_chq', 'label' => 'UDC Cheque No', 'type' => 'text'],
            ['key' => 'expired_chq', 'label' => 'Expired Cheque Amount', 'type' => 'money'],
            
            ['key' => 'collection_postpaid_nttn', 'label' => 'Collection Postpaid NTTN', 'type' => 'money'],
            ['key' => 'collection_postpaid_iig', 'label' => 'Collection Postpaid IIG', 'type' => 'money'],
            ['key' => 'collection_postpaid_itc', 'label' => 'Collection Postpaid ITC', 'type' => 'money'],
            ['key' => 'collection_postpaid_nix', 'label' => 'Collection Postpaid NIX', 'type' => 'money'],
            ['key' => 'total_collection', 'label' => 'Total Collection', 'type' => 'money'],
            
            ['key' => 'nttn_discontinuation_date', 'label' => 'NTTN Discontinuation Date', 'type' => 'date'],
            ['key' => 'iig_itc_discontinuation_date', 'label' => 'IIG/ITC Discontinuation Date', 'type' => 'date'],
            
            ['key' => 'unbilled_total', 'label' => 'Unbilled Total', 'type' => 'money'],
            ['key' => 'unbilled_nttn_os', 'label' => 'Unbilled NTTN OS', 'type' => 'money'],
            ['key' => 'unbilled_iig_os', 'label' => 'Unbilled IIG OS', 'type' => 'money'],
            ['key' => 'unbilled_itc_os', 'label' => 'Unbilled ITC OS', 'type' => 'money'],
        ];

        // Apply role based readOnly overrides
        $user = auth()->user();
        $canUpdate = $user && ($user->can('update monthly summaries') || $user->hasRole('admin'));
        $isCollection = $user && ($user->hasRole('collection_kam') || $user->hasRole('collection_hod') || $canUpdate);
        $isSales = $user && ($user->hasRole('sm_kam') || $user->hasRole('collection_hod') || $canUpdate);
        $isBilling = $user && ($user->hasRole('billing') || $canUpdate);

        $allowedEditableKeys = [
            'mrc_postpaid_nttn',
            'mrc_postpaid_nttn_iig',
            'mrc_postpaid_iig',
            'mrc_postpaid_itc',
            'mrc_postpaid_nix',
            'mrc_prepaid_nttn',
            'mrc_prepaid_nttn_iig',
            'mrc_prepaid_iig',
            'mrc_prepaid_itc',
            'mrc_prepaid_nix',
            'total_mrc',
            'current_month_remarks',
            'sales_review_remarks',
            'payment_plan_description',
            'visit_remarks',
        ];

        foreach ($columns as &$column) {
            if (!in_array($column['key'], $allowedEditableKeys)) {
                $column['readOnly'] = true;
            } else {
                if (in_array($column['key'], ['visit_remarks', 'payment_plan_description'])) {
                    if (!$isCollection) {
                        $column['readOnly'] = true;
                    }
                } elseif (in_array($column['key'], ['sales_review_remarks'])) {
                    if (!$isSales) {
                        $column['readOnly'] = true;
                    }
                } else {
                    if (!$isBilling) {
                        $column['readOnly'] = true;
                    }
                }
            }
        }

        return $this->applyDataDictionaryLabels($columns);
    }

    private function applyDataDictionaryLabels(array $columns): array
    {
        $columnKeys = array_column($columns, 'key');

        $labels = DataDictionary::query()
            ->whereIn('table_name', ['monthly_summary', 'monthly_summary_discontinued'])
            ->whereIn('column_name', $columnKeys)
            ->get(['column_name', 'business_name'])
            ->mapWithKeys(fn (DataDictionary $field) => [
                trim($field->column_name) => trim($field->business_name),
            ]);

        return collect($columns)
            ->map(function (array $column) use ($labels) {
                $column['label'] = $labels->get($column['key'], $column['key']);
                return $column;
            })
            ->all();
    }

    private function rules(): array
    {
        $rules = [
            'rows' => ['array'],
            'rows.*.monthly_summary_discontinued_id' => ['nullable', 'integer', 'exists:monthly_summary_discontinued,monthly_summary_discontinued_id'],
            'rows.*.client_id' => ['required', 'integer', 'exists:client,client_id'],
            'rows.*.summary_month' => ['required', 'date'],
            'rows.*.payment_plan_description' => ['nullable', 'string'],
            'rows.*.visit_remarks' => ['nullable', 'string'],
            'rows.*.sales_review_status' => ['nullable', 'string', 'max:50'],
            'rows.*.sales_review_remarks' => ['nullable', 'string'],
            'rows.*.barring_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'rows.*.pdc_chq' => ['nullable', 'string', 'max:255'],
            'rows.*.udc_chq' => ['nullable', 'string', 'max:255'],
            'rows.*.nttn_discontinuation_date' => ['nullable', 'date'],
            'rows.*.iig_itc_discontinuation_date' => ['nullable', 'date'],
        ];

        foreach ($this->moneyColumns() as $column) {
            $rules["rows.*.{$column}"] = ['nullable', 'numeric'];
        }

        return $rules;
    }

    private function fillableColumns(): array
    {
        return array_merge(
            [
                'monthly_summary_discontinued_id', 'client_id', 'summary_month', 
                'payment_plan_description', 'visit_remarks', 'sales_review_status', 'sales_review_remarks', 'barring_percentage',
                'collection_mrc', 'collection_backlog', 'mrc_shortfall', 'backlog_shortfall',
                'pdc_chq', 'udc_chq', 'nttn_discontinuation_date', 'iig_itc_discontinuation_date'
            ],
            $this->moneyColumns()
        );
    }

    private function moneyColumns(): array
    {
        return [
            'opening_os',
            'opening_os_nttn',
            'opening_os_iig',
            'opening_os_itc',
            'opening_os_nix',
            'target',
            'collection_amount',
            'shortfall_target',
            'latest_os',
            'latest_os_nttn',
            'latest_os_iig',
            'latest_os_itc',
            'latest_os_nix',
            'pdc',
            'udc',
            'total_security',
            'security_coverage',
            'expired_chq',
            'collection_postpaid_nttn',
            'collection_postpaid_iig',
            'collection_postpaid_itc',
            'collection_postpaid_nix',
            'total_collection',
            'unbilled_total',
            'unbilled_nttn_os',
            'unbilled_iig_os',
            'unbilled_itc_os',
            'collection_mrc',
            'collection_backlog',
            'mrc_shortfall',
            'backlog_shortfall',
        ];
    }

    private function isBlankRow(array $row): bool
    {
        return collect($row)
            ->except(['monthly_summary_discontinued_id', 'client_name', '__hotRow'])
            ->filter(fn ($value) => $value !== null && $value !== '')
            ->isEmpty();
    }

    private function normalizeRow(array $row): array
    {
        foreach ($this->moneyColumns() as $column) {
            if (isset($row[$column]) && is_string($row[$column])) {
                $row[$column] = str_replace(',', '', $row[$column]);
            }
        }

        return $row;
    }
}
