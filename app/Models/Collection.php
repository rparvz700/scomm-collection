<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Collection extends Model
{
    protected $table = 'collection';

    protected $primaryKey = 'collection_id';

    const UPDATED_AT = null;

    protected $fillable = [
        'client_id',
        'collection_datetime',
        'collection_month',
        'collection_type',
        'collection_amount',
        'remarks',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'collection_datetime' => 'datetime',
            'collection_month' => 'date',
            'collection_amount' => 'decimal:2',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id', 'client_id');
    }

    public static function boot()
    {
        parent::boot();

        static::saved(function ($collection) {
            self::recalculateMonthlySummary($collection->client_id, $collection->collection_month);
        });

        static::deleted(function ($collection) {
            self::recalculateMonthlySummary($collection->client_id, $collection->collection_month);
        });
    }

    public static function getRatingCategory($cr)
    {
        $ranges = config('risk.ranges', [
            ['min' => 0.00, 'max' => 1.50, 'category' => 'Best'],
            ['min' => 1.51, 'max' => 2.00, 'category' => 'Good'],
            ['min' => 2.01, 'max' => 2.50, 'category' => 'Moderate'],
            ['min' => 2.51, 'max' => 2.99, 'category' => 'Risky'],
            ['min' => 3.00, 'max' => 3.49, 'category' => 'High Risky'],
            ['min' => 3.50, 'max' => null, 'category' => 'Most Risky'],
        ]);
        foreach ($ranges as $r) {
            $min = $r['min'];
            $max = $r['max'];
            if (($min === null || $cr >= $min) && ($max === null || $cr <= $max)) {
                return $r['category'];
            }
        }
        return 'Unknown';
    }

    public static function recalculateMonthlySummary($clientId, $monthDate)
    {
        $summaryMonth = \Carbon\Carbon::parse($monthDate)->endOfMonth();

        // 1. Calculate sum of collections in this month
        $totalCollection = self::where('client_id', $clientId)
            ->whereDate('collection_month', $summaryMonth)
            ->sum('collection_amount');

        // 2. Update monthly_summary if exists
        $summary = \App\Models\MonthlySummary::where('client_id', $clientId)
            ->whereDate('summary_month', $summaryMonth)
            ->first();

        if ($summary) {
            $totalMrc = (float) $summary->total_mrc;
            $netBacklogTotal = (float) $summary->net_backlog_total;

            // LIFO allocation
            $collectionMrc = min($totalCollection, $totalMrc);
            $collectionBacklog = max(0.00, $totalCollection - $totalMrc);

            $mrcShortfall = max(0.00, $totalMrc - $collectionMrc);
            $backlogShortfall = max(0.00, $netBacklogTotal - $collectionBacklog);

            // Recalculate OS
            $totalLatestOs = max(0.00, (float)$summary->total_opening_os + (float)$summary->total_maturity - $totalCollection);

            // Recalculate CR & Rating Category
            $latestCr = $totalMrc > 0 ? ($totalLatestOs / $totalMrc) : 0.00;
            $latestRatingCategory = self::getRatingCategory($latestCr);

            // Fetch current client status/barring details to snapshot
            $client = \App\Models\Client::find($clientId);
            $barringPct = $client ? (float) $client->barring_percentage : 0.00;

            $summary->update([
                'total_collection' => $totalCollection,
                'collection_mrc' => $collectionMrc,
                'collection_backlog' => $collectionBacklog,
                'mrc_shortfall' => $mrcShortfall,
                'backlog_shortfall' => $backlogShortfall,
                'total_shortfall_maturity' => $mrcShortfall,
                'total_latest_os' => $totalLatestOs,
                'latest_cr' => $latestCr,
                'latest_rating_category' => $latestRatingCategory,
                'barring_percentage' => $barringPct,
            ]);
        }

        // 3. Update monthly_summary_discontinued if exists
        $discSummary = \App\Models\MonthlySummaryDiscontinued::where('client_id', $clientId)
            ->whereDate('summary_month', $summaryMonth)
            ->first();

        if ($discSummary) {
            $openingOs = (float) $discSummary->opening_os;

            $collectionMrc = 0.00;
            $collectionBacklog = $totalCollection;

            $mrcShortfall = 0.00;
            $backlogShortfall = max(0.00, $openingOs - $collectionBacklog);

            // Recalculate Latest OS
            $latestOs = max(0.00, $openingOs - $totalCollection);

            $client = \App\Models\Client::find($clientId);
            $barringPct = $client ? (float) $client->barring_percentage : 0.00;

            $discSummary->update([
                'total_collection' => $totalCollection,
                'collection_amount' => $totalCollection,
                'collection_mrc' => $collectionMrc,
                'collection_backlog' => $collectionBacklog,
                'mrc_shortfall' => $mrcShortfall,
                'backlog_shortfall' => $backlogShortfall,
                'latest_os' => $latestOs,
                'barring_percentage' => $barringPct,
            ]);
        }
    }
}
