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
     * Boot the trait.
     */
    protected static function bootHasClientSnapshot()
    {
        static::creating(function ($model) {
            if ($model->client_id && !$model->client_name) {
                $client = Client::find($model->client_id);
                if ($client) {
                    $model->client_opus_id = $client->opus_id;
                    $model->client_name = $client->client_name;
                    $model->client_status = $client->client_status;
                    $model->client_agreement_status = $client->agreement_status;
                    $model->client_barring_priority = $client->barring_priority;
                    $model->client_btrc_license_discontinuation_date = $client->btrc_license_discontinuation_date;
                    $model->client_legal = $client->legal;
                    $model->client_service_discontinuation_date = $client->service_discontinuation_date;
                    $model->client_billing_modality_kpi = $client->billing_modality_kpi;
                    $model->client_service_type_billing = $client->service_type_billing;
                    $model->client_license_billing = $client->license_billing;
                    $model->client_btrc_letter = $client->btrc_letter;
                    $model->client_security_coverage = $client->security_coverage;
                    $model->client_payment_plan = $client->payment_plan;
                    $model->client_other_upstream = $client->other_upstream;
                    $model->client_sm_kam = $client->sm_kam;
                    $model->client_team_name = $client->team_name;
                    $model->client_collection_kam = $client->collection_kam;
                    $model->client_collection_supervisor = $client->collection_supervisor;
                    $model->client_nttn_billing_kam = $client->nttn_billing_kam;
                    $model->client_iig_itc_billing_kam = $client->iig_itc_billing_kam;
                    $model->client_nttn_billing_commencement_date = $client->nttn_billing_commencement_date;
                    $model->client_iig_itc_billing_commencement_date = $client->iig_itc_billing_commencement_date;
                    $model->client_barred_at = $client->barred_at;
                    $model->client_barring_percentage = $client->barring_percentage;
                    $model->client_barring_workflow_status = $client->barring_workflow_status;
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
        $client->other_upstream = $this->client_other_upstream;
        $client->sm_kam = $this->client_sm_kam;
        $client->team_name = $this->client_team_name;
        $client->collection_kam = $this->client_collection_kam;
        $client->collection_supervisor = $this->client_collection_supervisor;
        $client->nttn_billing_kam = $this->client_nttn_billing_kam;
        $client->iig_itc_billing_kam = $this->client_iig_itc_billing_kam;
        $client->nttn_billing_commencement_date = $this->client_nttn_billing_commencement_date;
        $client->iig_itc_billing_commencement_date = $this->client_iig_itc_billing_commencement_date;
        $client->barred_at = $this->client_barred_at;
        $client->barring_percentage = $this->client_barring_percentage;
        $client->barring_workflow_status = $this->client_barring_workflow_status;

        $this->clientMockInstance = $client;
        return $client;
    }
}
