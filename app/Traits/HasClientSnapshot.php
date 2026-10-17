<?php

namespace App\Traits;

use App\Models\Client;

trait HasClientSnapshot
{
    /**
     * Cached mock instance of the snapshotted client.
     */
    protected $clientMockInstance;

    /**
     * Helper to extract client snapshot array from a Client model instance.
     */
    public static function extractClientSnapshotData(Client $client): array
    {
        return [
            'client_opus_id' => $client->opus_id,
            'client_name' => $client->client_name,
            'client_status' => $client->client_status,
            'client_agreement_status' => is_array($client->agreement_status) ? json_encode($client->agreement_status) : $client->agreement_status,
            'client_barring_priority' => $client->barring_priority,
            'client_btrc_license_discontinuation_date' => $client->btrc_license_discontinuation_date,
            'client_legal' => $client->legal,
            'client_service_discontinuation_date' => $client->service_discontinuation_date,
            'client_billing_modality_kpi' => $client->billing_modality_kpi,
            'client_service_type_billing' => $client->service_type_billing,
            'client_license_billing' => $client->license_billing,
            'client_btrc_letter' => $client->btrc_letter,
            'client_security_coverage' => $client->security_coverage,
            'client_payment_plan' => $client->payment_plan,
            'client_deferred_period' => $client->deferred_period ?? 0,
            'client_other_upstream' => $client->other_upstream ? 1 : 0,
            'client_sm_kam' => $client->sm_kam,
            'client_sm_kam_id' => $client->sm_kam_id,
            'client_team_name' => $client->team_name,
            'client_sm_supervisor' => $client->sm_supervisor,
            'client_sm_supervisor_id' => $client->sm_supervisor_id,
            'client_collection_kam' => $client->collection_kam,
            'client_collection_kam_id' => $client->collection_kam_id,
            'client_collection_supervisor' => $client->collection_supervisor,
            'client_collection_supervisor_id' => $client->collection_supervisor_id,
            'client_collection_team_name' => $client->collection_team_name,
            'client_nttn_billing_kam' => $client->nttn_billing_kam,
            'client_nttn_billing_kam_id' => $client->nttn_billing_kam_id,
            'client_iig_itc_billing_kam' => $client->iig_itc_billing_kam,
            'client_iig_itc_billing_kam_id' => $client->iig_itc_billing_kam_id,
            'client_nttn_billing_commencement_date' => $client->nttn_billing_commencement_date,
            'client_iig_itc_billing_commencement_date' => $client->iig_itc_billing_commencement_date,
            'client_barred_at' => $client->barred_at,
            'client_barring_percentage' => $client->barring_percentage ?? 0.00,
            'client_barring_workflow_status' => $client->barring_workflow_status ?? 'none',
        ];
    }

    /**
     * Boot the trait.
     */
    protected static function bootHasClientSnapshot()
    {
        static::creating(function ($model) {
            if ($model->client_id && !$model->client_name) {
                $client = Client::find($model->client_id);
                if ($client) {
                    foreach (static::extractClientSnapshotData($client) as $column => $value) {
                        $model->{$column} = $value;
                    }
                }
            }
        });
    }

    /**
     * Intercept and return snapshotted client details.
     */
    public function getClientAttribute()
    {
        if ($this->clientMockInstance) {
            return $this->clientMockInstance;
        }

        // If snapshot columns are empty/null (e.g. legacy unsynced rows), fallback to standard relation
        if (!$this->client_name) {
            return $this->relationLoaded('client') 
                ? $this->getRelation('client') 
                : $this->client()->first();
        }

        // Return a dummy Client model populated with snapshotted fields
        $client = new Client();
        $client->exists = true;
        
        $client->client_id = $this->client_id;
        $client->opus_id = $this->client_opus_id;
        $client->client_name = $this->client_name;
        $client->client_status = $this->client_status;
        $client->agreement_status = $this->client_agreement_status;
        $client->barring_priority = $this->client_barring_priority;
        $client->btrc_license_discontinuation_date = $this->client_btrc_license_discontinuation_date;
        $client->legal = $this->client_legal;
        $client->service_discontinuation_date = $this->client_service_discontinuation_date;
        $client->billing_modality_kpi = $this->client_billing_modality_kpi;
        $client->service_type_billing = $this->client_service_type_billing;
        $client->license_billing = $this->client_license_billing;
        $client->btrc_letter = $this->client_btrc_letter;
        $client->security_coverage = $this->client_security_coverage;
        $client->payment_plan = $this->client_payment_plan;
        $client->deferred_period = $this->client_deferred_period ?? 0;
        $client->other_upstream = $this->client_other_upstream;
        $client->sm_kam = $this->client_sm_kam;
        $client->sm_kam_id = $this->client_sm_kam_id;
        $client->team_name = $this->client_team_name;
        $client->sm_supervisor = $this->client_sm_supervisor;
        $client->sm_supervisor_id = $this->client_sm_supervisor_id;
        $client->collection_kam = $this->client_collection_kam;
        $client->collection_kam_id = $this->client_collection_kam_id;
        $client->collection_supervisor = $this->client_collection_supervisor;
        $client->collection_supervisor_id = $this->client_collection_supervisor_id;
        $client->collection_team_name = $this->client_collection_team_name;
        $client->nttn_billing_kam = $this->client_nttn_billing_kam;
        $client->nttn_billing_kam_id = $this->client_nttn_billing_kam_id;
        $client->iig_itc_billing_kam = $this->client_iig_itc_billing_kam;
        $client->iig_itc_billing_kam_id = $this->client_iig_itc_billing_kam_id;
        $client->nttn_billing_commencement_date = $this->client_nttn_billing_commencement_date;
        $client->iig_itc_billing_commencement_date = $this->client_iig_itc_billing_commencement_date;
        $client->barred_at = $this->client_barred_at;
        $client->barring_percentage = $this->client_barring_percentage;
        $client->barring_workflow_status = $this->client_barring_workflow_status;

        $this->clientMockInstance = $client;
        return $client;
    }
}
