<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * App\Models\KpiChart
 *
 * @property int    $id
 * @property string $code
 * @property string $name
 * @property string|null $description
 * @property string $chart_type
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @property-read \Illuminate\Database\Eloquent\Collection|ChartMetric[] $metrics
 */
class KpiChart extends Model
{
    protected $table = 'kpi_charts';
    public $timestamps = true;

    protected $fillable = [
        'code',
        'name',
        'description',
        'chart_type',
    ];

    /**
     * Metrics associated with this chart
     */
    public function metrics(): HasMany
    {
        return $this->hasMany(ChartMetric::class, 'chart_id');
    }
}