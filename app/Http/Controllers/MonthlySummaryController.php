<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\DataDictionary;
use App\Models\MonthlySummary;
use App\Models\SummaryAuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class MonthlySummaryController extends Controller
{
    public function index(Request $request): View
    {
        $selectedMonth = $request->input('month');
        if (! $selectedMonth) {
            $latestMonth = MonthlySummary::query()->max('summary_month');
            $selectedMonth = $latestMonth ? \Carbon\Carbon::parse($latestMonth)->format('Y-m') : now()->format('Y-m');
        }

        $selectedMonthDate = \Carbon\Carbon::createFromFormat('Y-m', $selectedMonth)->endOfMonth();

        $summaries = MonthlySummary::query()
            ->with('client')
            ->whereDate('summary_month', $selectedMonthDate)
            ->get();

        $existingClientIds = $summaries->pluck('client_id')->filter()->all();
        $pendingActiveClients = Client::query()
            ->where('client_status', 'Active')
            ->whereNotIn('client_id', $existingClientIds)
            ->orderBy('client_name')
            ->get(['client_id', 'client_name', 'opus_id']);

        return view('monthly-summary.index', [
            'summaries' => $summaries,
            'selectedMonth' => $selectedMonth,
            'clients' => Client::query()
                ->orderBy('client_name')
                ->get(['client_id', 'client_name', 'opus_id']),
            'columns' => $this->columns(),
            'pendingActiveClients' => $pendingActiveClients,
            'pendingActiveCount' => $pendingActiveClients->count(),
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
                'message' => 'Please fix the highlighted monthly summary fields.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $saved = DB::transaction(function () use ($rows, $request) {
            $count = 0;
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

                if (empty($attributes['client_id'])) {
                    if (!empty($row['opus_id'])) {
                        $attributes['client_id'] = Client::where('opus_id', $row['opus_id'])->value('client_id');
                    } elseif (!empty($row['client_name'])) {
                        $attributes['client_id'] = Client::where('client_name', $row['client_name'])->value('client_id');
                    }
                }

                $summaryId = $attributes['monthly_summary_id'] ?? null;
                unset($attributes['monthly_summary_id']);

                if ($summaryId) {
                    $existingModel = MonthlySummary::find($summaryId);
                    if ($existingModel) {
                        foreach ($attributes as $key => $newValue) {
                            $oldValue = $existingModel->$key;

                            $oldStr = $oldValue === null ? null : (string) $oldValue;
                            $newStr = $newValue === null ? null : (string) $newValue;

                            if ($oldStr !== $newStr) {
                                SummaryAuditLog::create([
                                    'summary_type' => 'active',
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
                    $newModel = MonthlySummary::updateOrCreate(
                        [
                            'client_id' => $attributes['client_id'],
                            'summary_month' => $attributes['summary_month'],
                        ],
                        $attributes
                    );

                    SummaryAuditLog::create([
                        'summary_type' => 'active',
                        'summary_id' => $newModel->monthly_summary_id,
                        'client_id' => $newModel->client_id,
                        'field_name' => 'row_created',
                        'old_value' => null,
                        'new_value' => 'New Active Monthly Summary Row Created',
                        'summary_month' => $newModel->summary_month,
                        'user_id' => $userId,
                        'updated_by' => $updatedBy,
                        'ip_address' => $ipAddress,
                    ]);
                }

                $count++;
            }

            return $count;
        });

        return response()->json([
            'message' => "{$saved} monthly summary rows saved.",
            'data' => $this->monthlySummaryRows($request->input('month')),
        ]);
    }

    private function monthlySummaryRows($month = null)
    {
        $query = MonthlySummary::query()
            ->with('client')
            ->orderByDesc('summary_month');

        if ($month) {
            $date = \Carbon\Carbon::createFromFormat('Y-m', $month)->endOfMonth();
            $query->whereDate('summary_month', $date);
        } else {
            // Default to latest month if none specified
            $latestMonth = MonthlySummary::query()->max('summary_month');
            if ($latestMonth) {
                $query->whereDate('summary_month', $latestMonth);
            }
        }

        return $query->get()
            ->map(function (MonthlySummary $summary) {
                $row = $summary->toArray();
                $row['opus_id'] = $summary->client?->opus_id;
                $row['client_name'] = $summary->client?->client_name;

                foreach (['summary_month', 'client_payment_commitment_date'] as $dateField) {
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
            ['key' => 'opening_os_postpaid_nttn', 'label' => 'Opening OS Postpaid NTTN', 'type' => 'money'],
            ['key' => 'opening_os_postpaid_iig_nttn', 'label' => 'Opening OS Postpaid IIG NTTN', 'type' => 'money'],
            ['key' => 'opening_os_postpaid_iig', 'label' => 'Opening OS Postpaid IIG', 'type' => 'money'],
            ['key' => 'opening_os_postpaid_itc', 'label' => 'Opening OS Postpaid ITC', 'type' => 'money'],
            ['key' => 'opening_os_postpaid_nix', 'label' => 'Opening OS Postpaid NIX', 'type' => 'money'],
            ['key' => 'opening_os_prepaid_nttn', 'label' => 'Opening OS Prepaid NTTN', 'type' => 'money'],
            ['key' => 'opening_os_prepaid_iig_nttn', 'label' => 'Opening OS Prepaid IIG NTTN', 'type' => 'money'],
            ['key' => 'opening_os_prepaid_iig', 'label' => 'Opening OS Prepaid IIG', 'type' => 'money'],
            ['key' => 'opening_os_prepaid_itc', 'label' => 'Opening OS Prepaid ITC', 'type' => 'money'],
            ['key' => 'opening_os_prepaid_nix', 'label' => 'Opening OS Prepaid NIX', 'type' => 'money'],
            ['key' => 'total_opening_os', 'label' => 'Total Opening OS', 'type' => 'money'],
            ['key' => 'mrc_postpaid_nttn', 'label' => 'MRC Postpaid NTTN', 'type' => 'money'],
            ['key' => 'mrc_postpaid_nttn_iig', 'label' => 'MRC Postpaid NTTN IIG', 'type' => 'money'],
            ['key' => 'mrc_postpaid_iig', 'label' => 'MRC Postpaid IIG', 'type' => 'money'],
            ['key' => 'mrc_postpaid_itc', 'label' => 'MRC Postpaid ITC', 'type' => 'money'],
            ['key' => 'mrc_postpaid_nix', 'label' => 'MRC Postpaid NIX', 'type' => 'money'],
            ['key' => 'mrc_prepaid_nttn', 'label' => 'MRC Prepaid NTTN', 'type' => 'money'],
            ['key' => 'mrc_prepaid_nttn_iig', 'label' => 'MRC Prepaid NTTN IIG', 'type' => 'money'],
            ['key' => 'mrc_prepaid_iig', 'label' => 'MRC Prepaid IIG', 'type' => 'money'],
            ['key' => 'mrc_prepaid_itc', 'label' => 'MRC Prepaid ITC', 'type' => 'money'],
            ['key' => 'mrc_prepaid_nix', 'label' => 'MRC Prepaid NIX', 'type' => 'money'],
            ['key' => 'total_mrc', 'label' => 'Total MRC', 'type' => 'money'],
            ['key' => 'maturity_postpaid_nttn', 'label' => 'Maturity Postpaid NTTN', 'type' => 'money'],
            ['key' => 'maturity_postpaid_nttn_iig', 'label' => 'Maturity Postpaid NTTN IIG', 'type' => 'money'],
            ['key' => 'maturity_postpaid_iig', 'label' => 'Maturity Postpaid IIG', 'type' => 'money'],
            ['key' => 'maturity_postpaid_itc', 'label' => 'Maturity Postpaid ITC', 'type' => 'money'],
            ['key' => 'maturity_postpaid_nix', 'label' => 'Maturity Postpaid NIX', 'type' => 'money'],
            ['key' => 'maturity_prepaid_nttn', 'label' => 'Maturity Prepaid NTTN', 'type' => 'money'],
            ['key' => 'maturity_prepaid_nttn_iig', 'label' => 'Maturity Prepaid NTTN IIG', 'type' => 'money'],
            ['key' => 'maturity_prepaid_iig', 'label' => 'Maturity Prepaid IIG', 'type' => 'money'],
            ['key' => 'maturity_prepaid_itc', 'label' => 'Maturity Prepaid ITC', 'type' => 'money'],
            ['key' => 'maturity_prepaid_nix', 'label' => 'Maturity Prepaid NIX', 'type' => 'money'],
            ['key' => 'total_maturity', 'label' => 'Total Maturity', 'type' => 'money'],
            ['key' => 'net_backlog_postpaid', 'label' => 'Net Backlog Postpaid', 'type' => 'money'],
            ['key' => 'net_backlog_prepaid', 'label' => 'Net Backlog Prepaid', 'type' => 'money'],
            ['key' => 'net_backlog_total', 'label' => 'Net Backlog Total', 'type' => 'money'],
            ['key' => 'target_maturity_commitment_postpaid', 'label' => 'Target Maturity Commitment Postpaid', 'type' => 'money'],
            ['key' => 'target_maturity_commitment_prepaid', 'label' => 'Target Maturity Commitment Prepaid', 'type' => 'money'],
            ['key' => 'total_target_maturity_commitment', 'label' => 'Total Target Maturity Commitment', 'type' => 'money'],
            ['key' => 'target_additional_shortfall_from_maturity', 'label' => 'Additional Shortfall From Maturity', 'type' => 'money'],
            ['key' => 'maturity_commitment_total', 'label' => 'Maturity Commitment Total', 'type' => 'money'],
            ['key' => 'payment_plan_postpaid', 'label' => 'Payment Plan Postpaid', 'type' => 'money'],
            ['key' => 'payment_plan_prepaid', 'label' => 'Payment Plan Prepaid', 'type' => 'money'],
            ['key' => 'total_payment_plan', 'label' => 'Total Payment Plan', 'type' => 'money'],
            ['key' => 'current_month_remarks', 'label' => 'Current Month Remarks', 'type' => 'text'],
            ['key' => 'sales_review_status', 'label' => 'Sales Review Status', 'type' => 'dropdown', 'source' => ['Pending', 'Approved', 'Rejected']],
            ['key' => 'sales_review_remarks', 'label' => 'Sales Review Remarks', 'type' => 'text'],
            ['key' => 'barring_percentage', 'label' => 'Barring %', 'type' => 'numeric'],
            ['key' => 'collection_mrc', 'label' => 'Collection MRC (LIFO)', 'type' => 'money', 'readOnly' => true],
            ['key' => 'collection_backlog', 'label' => 'Collection Backlog (LIFO)', 'type' => 'money', 'readOnly' => true],
            ['key' => 'mrc_shortfall', 'label' => 'MRC Shortfall (LIFO)', 'type' => 'money', 'readOnly' => true],
            ['key' => 'backlog_shortfall', 'label' => 'Backlog Shortfall (LIFO)', 'type' => 'money', 'readOnly' => true],
            ['key' => 'shortfall_target_postpaid', 'label' => 'Shortfall Target Postpaid', 'type' => 'money'],
            ['key' => 'shortfall_target_prepaid', 'label' => 'Shortfall Target Prepaid', 'type' => 'money'],
            ['key' => 'total_shortfall_target', 'label' => 'Total Shortfall Target', 'type' => 'money'],
            ['key' => 'shortfall_mrc_postpaid', 'label' => 'Shortfall MRC Postpaid', 'type' => 'money'],
            ['key' => 'shortfall_mrc_prepaid', 'label' => 'Shortfall MRC Prepaid', 'type' => 'money'],
            ['key' => 'total_shortfall_mrc', 'label' => 'Total Shortfall MRC', 'type' => 'money'],
            ['key' => 'shortfall_maturity_postpaid', 'label' => 'Shortfall Maturity Postpaid', 'type' => 'money'],
            ['key' => 'shortfall_maturity_prepaid', 'label' => 'Shortfall Maturity Prepaid', 'type' => 'money'],
            ['key' => 'total_shortfall_maturity', 'label' => 'Total Shortfall Maturity', 'type' => 'money'],
            ['key' => 'shortfall_payment_plan_postpaid', 'label' => 'Shortfall Payment Plan Postpaid', 'type' => 'money'],
            ['key' => 'shortfall_payment_plan_prepaid', 'label' => 'Shortfall Payment Plan Prepaid', 'type' => 'money'],
            ['key' => 'total_shortfall_payment_plan', 'label' => 'Total Shortfall Payment Plan', 'type' => 'money'],
            ['key' => 'latest_os_balance_postpaid', 'label' => 'Latest OS Postpaid', 'type' => 'money'],
            ['key' => 'latest_os_balance_prepaid', 'label' => 'Latest OS Prepaid', 'type' => 'money'],
            ['key' => 'total_latest_os', 'label' => 'Total Latest OS', 'type' => 'money'],
            ['key' => 'pdc', 'label' => 'PDC', 'type' => 'money'],
            ['key' => 'udc', 'label' => 'UDC', 'type' => 'money'],
            ['key' => 'expired_chq', 'label' => 'Expired CHQ', 'type' => 'money'],
            ['key' => 'payment_plan_description', 'label' => 'Payment Plan Description', 'type' => 'text'],
            ['key' => 'visit_remarks', 'label' => 'Visit Remarks', 'type' => 'text'],
            ['key' => 'client_payment_commitment_date', 'label' => 'Payment Commitment Date', 'type' => 'date'],
            ['key' => 'nttn_tds_amount', 'label' => 'NTTN TDS Amount', 'type' => 'money'],
            ['key' => 'balance_after_recovery', 'label' => 'Balance After Recovery', 'type' => 'money'],
            ['key' => 'collection_postpaid_nttn', 'label' => 'Collection Postpaid NTTN', 'type' => 'money'],
            ['key' => 'collection_postpaid_nttn_iig', 'label' => 'Collection Postpaid NTTN IIG', 'type' => 'money'],
            ['key' => 'collection_postpaid_iig', 'label' => 'Collection Postpaid IIG', 'type' => 'money'],
            ['key' => 'collection_postpaid_itc', 'label' => 'Collection Postpaid ITC', 'type' => 'money'],
            ['key' => 'collection_postpaid_nix', 'label' => 'Collection Postpaid NIX', 'type' => 'money'],
            ['key' => 'collection_prepaid_nttn', 'label' => 'Collection Prepaid NTTN', 'type' => 'money'],
            ['key' => 'collection_prepaid_nttn_iig', 'label' => 'Collection Prepaid NTTN IIG', 'type' => 'money'],
            ['key' => 'collection_prepaid_iig', 'label' => 'Collection Prepaid IIG', 'type' => 'money'],
            ['key' => 'collection_prepaid_itc', 'label' => 'Collection Prepaid ITC', 'type' => 'money'],
            ['key' => 'collection_prepaid_nix', 'label' => 'Collection Prepaid NIX', 'type' => 'money'],
            ['key' => 'total_collection', 'label' => 'Total Collection', 'type' => 'money'],
            ['key' => 'opening_cr', 'label' => 'Opening CR', 'type' => 'rating'],
            ['key' => 'opening_rating_category', 'label' => 'Opening Rating Category', 'type' => 'text'],
            ['key' => 'latest_cr', 'label' => 'Latest CR', 'type' => 'rating'],
            ['key' => 'latest_rating_category', 'label' => 'Latest Rating Category', 'type' => 'text'],
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
                if (in_array($column['key'], ['current_month_remarks', 'payment_plan_description', 'visit_remarks'])) {
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
            ->where('table_name', 'monthly_summary')
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
            'rows.*.monthly_summary_id' => ['nullable', 'integer', 'exists:monthly_summary,monthly_summary_id'],
            'rows.*.client_id' => ['required', 'integer', 'exists:client,client_id'],
            'rows.*.summary_month' => ['required', 'date'],
            'rows.*.current_month_remarks' => ['nullable', 'string'],
            'rows.*.payment_plan_description' => ['nullable', 'string'],
            'rows.*.visit_remarks' => ['nullable', 'string'],
            'rows.*.client_payment_commitment_date' => ['nullable', 'date'],
            'rows.*.opening_rating_category' => ['nullable', 'string', 'max:100'],
            'rows.*.latest_rating_category' => ['nullable', 'string', 'max:100'],
            'rows.*.sales_review_status' => ['nullable', 'string', 'max:50'],
            'rows.*.sales_review_remarks' => ['nullable', 'string'],
            'rows.*.barring_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ];

        foreach ($this->moneyColumns() as $column) {
            $rules["rows.*.{$column}"] = ['nullable', 'numeric', 'min:0'];
        }

        foreach (['opening_cr', 'latest_cr'] as $column) {
            $rules["rows.*.{$column}"] = ['nullable', 'numeric', 'min:0'];
        }

        return $rules;
    }

    private function fillableColumns(): array
    {
        return array_merge(
            [
                'monthly_summary_id', 'client_id', 'summary_month', 'current_month_remarks', 
                'payment_plan_description', 'visit_remarks', 'client_payment_commitment_date', 'opening_rating_category', 
                'latest_rating_category', 'sales_review_status', 'sales_review_remarks', 'barring_percentage',
                'collection_mrc', 'collection_backlog', 'mrc_shortfall', 'backlog_shortfall'
            ],
            $this->moneyColumns(),
            ['opening_cr', 'latest_cr']
        );
    }

    private function moneyColumns(): array
    {
        return [
            'opening_os_postpaid_nttn',
            'opening_os_postpaid_iig_nttn',
            'opening_os_postpaid_iig',
            'opening_os_postpaid_itc',
            'opening_os_postpaid_nix',
            'opening_os_prepaid_nttn',
            'opening_os_prepaid_iig_nttn',
            'opening_os_prepaid_iig',
            'opening_os_prepaid_itc',
            'opening_os_prepaid_nix',
            'total_opening_os',
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
            'maturity_postpaid_nttn',
            'maturity_postpaid_nttn_iig',
            'maturity_postpaid_iig',
            'maturity_postpaid_itc',
            'maturity_postpaid_nix',
            'maturity_prepaid_nttn',
            'maturity_prepaid_nttn_iig',
            'maturity_prepaid_iig',
            'maturity_prepaid_itc',
            'maturity_prepaid_nix',
            'total_maturity',
            'net_backlog_postpaid',
            'net_backlog_prepaid',
            'net_backlog_total',
            'target_maturity_commitment_postpaid',
            'target_maturity_commitment_prepaid',
            'total_target_maturity_commitment',
            'target_additional_shortfall_from_maturity',
            'maturity_commitment_total',
            'payment_plan_postpaid',
            'payment_plan_prepaid',
            'total_payment_plan',
            'shortfall_target_postpaid',
            'shortfall_target_prepaid',
            'total_shortfall_target',
            'shortfall_mrc_postpaid',
            'shortfall_mrc_prepaid',
            'total_shortfall_mrc',
            'shortfall_maturity_postpaid',
            'shortfall_maturity_prepaid',
            'total_shortfall_maturity',
            'shortfall_payment_plan_postpaid',
            'shortfall_payment_plan_prepaid',
            'total_shortfall_payment_plan',
            'latest_os_balance_postpaid',
            'latest_os_balance_prepaid',
            'total_latest_os',
            'pdc',
            'udc',
            'expired_chq',
            'nttn_tds_amount',
            'balance_after_recovery',
            'collection_postpaid_nttn',
            'collection_postpaid_nttn_iig',
            'collection_postpaid_iig',
            'collection_postpaid_itc',
            'collection_postpaid_nix',
            'collection_prepaid_nttn',
            'collection_prepaid_nttn_iig',
            'collection_prepaid_iig',
            'collection_prepaid_itc',
            'collection_prepaid_nix',
            'total_collection',
            'collection_mrc',
            'collection_backlog',
            'mrc_shortfall',
            'backlog_shortfall',
        ];
    }

    private function isBlankRow(array $row): bool
    {
        return collect($row)
            ->except(['monthly_summary_id', 'client_name', '__hotRow'])
            ->filter(fn ($value) => $value !== null && $value !== '')
            ->isEmpty();
    }

    private function normalizeRow(array $row): array
    {
        foreach (array_merge($this->moneyColumns(), ['opening_cr', 'latest_cr']) as $column) {
            if (isset($row[$column]) && is_string($row[$column])) {
                $row[$column] = str_replace(',', '', $row[$column]);
            }
        }

        return $row;
    }
}
