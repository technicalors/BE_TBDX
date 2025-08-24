<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * App\Models\ChartMetric
 *
 * @property int    $id
 * @property int    $chart_id
 * @property int    $metric_id
 * @property string $dataset_label
 * @property string $color
 * @property string|null $axis
 * @property int    $sort_order
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @property-read KpiChart    $chart
 * @property-read KpiMetric   $metric
 */
class ChartMetric extends Model
{
    protected $table = 'chart_metrics';
    public $timestamps = true;

    protected $fillable = [
        'chart_id',
        'metric_id',
        'dataset_label',
        'color',
        'axis',
        'sort_order',
    ];

    /**
     * The chart this metric belongs to
     */
    public function chart(): BelongsTo
    {
        return $this->belongsTo(KpiChart::class, 'chart_id');
    }

    /**
     * The KPI metric definition
     */
    public function metric(): BelongsTo
    {
        return $this->belongsTo(KpiMetric::class, 'metric_id');
    }
}