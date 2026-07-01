<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\DB;
header('Content-Type: text/plain; charset=utf-8');

try {
    $month = '2025-06-30';
    echo "=== Starting Residue Resolution for $month ===\n\n";

    // 1. Load mappings in memory
    $mappingsRaw = DB::table('residue_client_mapping')->get();
    $mappings = [];
    foreach ($mappingsRaw as $m) {
        $key = strtolower(trim($m->client_name));
        $mappings[$key] = trim($m->c_client_name);
    }
    echo "Loaded " . count($mappings) . " mapping entries.\n\n";

    // 2. Resolve Active Residues (monthly_summary_residue)
    $activeResidues = DB::table('monthly_summary_residue')->where('summary_month', $month)->get();
    echo "Found " . count($activeResidues) . " active residue rows.\n";

    $activeResolvedCount = 0;
    foreach ($activeResidues as $row) {
        $rawName = trim($row->client_name);
        $key = strtolower($rawName);
        
        if (isset($mappings[$key])) {
            $cClientName = $mappings[$key];
            // Look up client
            $client = DB::table('client')->where('client_name', $cClientName)->first();
            if ($client) {
                $insertData = (array)$row;
                // Remove primary key or auto-incrementing field if exists
                unset($insertData['id']);
                unset($insertData['monthly_summary_id']);
                unset($insertData['created_at']);
                unset($insertData['updated_at']);
                
                // Populate snapshot fields
                $insertData['client_id'] = $client->client_id;
                $insertData['client_opus_id'] = $client->opus_id;
                $insertData['client_name'] = $client->client_name;
                $insertData['client_status'] = $client->client_status;
                $insertData['client_agreement_status'] = $client->agreement_status;
                $insertData['client_barring_priority'] = $client->barring_priority;
                $insertData['client_btrc_license_discontinuation_date'] = $client->btrc_license_discontinuation_date;
                $insertData['client_legal'] = $client->legal;
                $insertData['client_service_discontinuation_date'] = $client->service_discontinuation_date;
                $insertData['client_billing_modality_kpi'] = $client->billing_modality_kpi;
                $insertData['client_service_type_billing'] = $client->service_type_billing;
                $insertData['client_license_billing'] = $client->license_billing;
                $insertData['client_btrc_letter'] = $client->btrc_letter;
                $insertData['client_security_coverage'] = $client->security_coverage;
                $insertData['client_payment_plan'] = $client->payment_plan;
                $insertData['client_other_upstream'] = $client->other_upstream;
                $insertData['client_sm_kam'] = $client->sm_kam;
                $insertData['client_team_name'] = $client->team_name;
                $insertData['client_collection_kam'] = $client->collection_kam;
                $insertData['client_collection_supervisor'] = $client->collection_supervisor;
                $insertData['client_nttn_billing_kam'] = $client->nttn_billing_kam;
                $insertData['client_iig_itc_billing_kam'] = $client->iig_itc_billing_kam;
                $insertData['client_nttn_billing_commencement_date'] = $client->nttn_billing_commencement_date;
                $insertData['client_iig_itc_billing_commencement_date'] = $client->iig_itc_billing_commencement_date;

                // Insert into monthly_summary
                DB::table('monthly_summary')->updateOrInsert(
                    [
                        'client_id' => $client->client_id,
                        'summary_month' => $month
                    ],
                    $insertData
                );

                // Sync with collection table: delete existing collections first
                DB::table('collection')
                    ->where('client_id', $client->client_id)
                    ->where('collection_month', $month)
                    ->delete();

                $collectionTypes = [
                    'postpaid_nttn' => 'collection_postpaid_nttn',
                    'postpaid_nttn_iig' => 'collection_postpaid_nttn_iig',
                    'postpaid_iig' => 'collection_postpaid_iig',
                    'postpaid_itc' => 'collection_postpaid_itc',
                    'postpaid_nix' => 'collection_postpaid_nix',
                    'prepaid_nttn' => 'collection_prepaid_nttn',
                    'prepaid_nttn_iig' => 'collection_prepaid_nttn_iig',
                    'prepaid_iig' => 'collection_prepaid_iig',
                    'prepaid_itc' => 'collection_prepaid_itc',
                    'prepaid_nix' => 'collection_prepaid_nix',
                ];

                foreach ($collectionTypes as $enumVal => $field) {
                    $amount = isset($row->$field) ? (float)$row->$field : 0.0;
                    if ($amount > 0.0) {
                        DB::table('collection')->insert([
                            'client_id' => $client->client_id,
                            'collection_datetime' => $month . ' 23:59:59',
                            'collection_month' => $month,
                            'collection_type' => $enumVal,
                            'collection_amount' => $amount,
                            'remarks' => 'Imported active collection via residue mapping',
                            'created_by' => 'System',
                            'created_at' => now()
                        ]);
                    }
                }

                // Delete from monthly_summary_residue
                DB::table('monthly_summary_residue')
                    ->where('client_name', $rawName)
                    ->where('summary_month', $month)
                    ->delete();

                echo "  [Active] Resolved: '$rawName' -> '$cClientName' (client_id: {$client->client_id})\n";
                $activeResolvedCount++;
            } else {
                echo "  [Active] Mapped name '$cClientName' (for '$rawName') not found in client table.\n";
            }
        }
    }

    // 3. Resolve Discontinued Residues (monthly_summary_discontinued_residue)
    $discResidues = DB::table('monthly_summary_discontinued_residue')->where('summary_month', $month)->get();
    echo "\nFound " . count($discResidues) . " discontinued residue rows.\n";

    $discResolvedCount = 0;
    foreach ($discResidues as $row) {
        $rawName = trim($row->client_name);
        $key = strtolower($rawName);
        
        if (isset($mappings[$key])) {
            $cClientName = $mappings[$key];
            // Look up client
            $client = DB::table('client')->where('client_name', $cClientName)->first();
            if ($client) {
                $insertData = (array)$row;
                unset($insertData['id']);
                unset($insertData['monthly_summary_discontinued_id']);
                unset($insertData['created_at']);
                unset($insertData['updated_at']);
                
                // Populate snapshot fields
                $insertData['client_id'] = $client->client_id;
                $insertData['client_opus_id'] = $client->opus_id;
                $insertData['client_name'] = $client->client_name;
                $insertData['client_status'] = $client->client_status;
                $insertData['client_agreement_status'] = $client->agreement_status;
                $insertData['client_barring_priority'] = $client->barring_priority;
                $insertData['client_btrc_license_discontinuation_date'] = $client->btrc_license_discontinuation_date;
                $insertData['client_legal'] = $client->legal;
                $insertData['client_service_discontinuation_date'] = $client->service_discontinuation_date;
                $insertData['client_billing_modality_kpi'] = $client->billing_modality_kpi;
                $insertData['client_service_type_billing'] = $client->service_type_billing;
                $insertData['client_license_billing'] = $client->license_billing;
                $insertData['client_btrc_letter'] = $client->btrc_letter;
                $insertData['client_security_coverage'] = $client->security_coverage;
                $insertData['client_payment_plan'] = $client->payment_plan;
                $insertData['client_other_upstream'] = $client->other_upstream;
                $insertData['client_sm_kam'] = $client->sm_kam;
                $insertData['client_team_name'] = $client->team_name;
                $insertData['client_collection_kam'] = $client->collection_kam;
                $insertData['client_collection_supervisor'] = $client->collection_supervisor;
                $insertData['client_nttn_billing_kam'] = $client->nttn_billing_kam;
                $insertData['client_iig_itc_billing_kam'] = $client->iig_itc_billing_kam;
                $insertData['client_nttn_billing_commencement_date'] = $client->nttn_billing_commencement_date;
                $insertData['client_iig_itc_billing_commencement_date'] = $client->iig_itc_billing_commencement_date;

                // Insert into monthly_summary_discontinued
                DB::table('monthly_summary_discontinued')->updateOrInsert(
                    [
                        'client_id' => $client->client_id,
                        'summary_month' => $month
                    ],
                    $insertData
                );

                // Sync with collection table: delete existing collections first
                DB::table('collection')
                    ->where('client_id', $client->client_id)
                    ->where('collection_month', $month)
                    ->delete();

                $collectionTypes = [
                    'postpaid_nttn' => 'collection_postpaid_nttn',
                    'postpaid_iig' => 'collection_postpaid_iig',
                    'postpaid_itc' => 'collection_postpaid_itc',
                    'postpaid_nix' => 'collection_postpaid_nix',
                ];

                foreach ($collectionTypes as $enumVal => $field) {
                    $amount = isset($row->$field) ? (float)$row->$field : 0.0;
                    if ($amount > 0.0) {
                        DB::table('collection')->insert([
                            'client_id' => $client->client_id,
                            'collection_datetime' => $month . ' 23:59:59',
                            'collection_month' => $month,
                            'collection_type' => $enumVal,
                            'collection_amount' => $amount,
                            'remarks' => 'Imported discontinued collection via residue mapping',
                            'created_by' => 'System',
                            'created_at' => now()
                        ]);
                    }
                }

                // Delete from monthly_summary_discontinued_residue
                DB::table('monthly_summary_discontinued_residue')
                    ->where('client_name', $rawName)
                    ->where('summary_month', $month)
                    ->delete();

                echo "  [Discontinued] Resolved: '$rawName' -> '$cClientName' (client_id: {$client->client_id})\n";
                $discResolvedCount++;
            } else {
                echo "  [Discontinued] Mapped name '$cClientName' (for '$rawName') not found in client table.\n";
            }
        }
    }

    // 4. Print final totals
    $activeRemaining = DB::table('monthly_summary_residue')->where('summary_month', $month)->count();
    $discRemaining = DB::table('monthly_summary_discontinued_residue')->where('summary_month', $month)->count();

    echo "\n=== Resolution Summary ===\n";
    echo "Active Residues:       Resolved: $activeResolvedCount | Remaining: $activeRemaining\n";
    echo "Discontinued Residues: Resolved: $discResolvedCount | Remaining: $discRemaining\n";
} catch (\Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n";
}
