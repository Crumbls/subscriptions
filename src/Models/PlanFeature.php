<?php

declare(strict_types=1);

namespace Crumbls\Subscriptions\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * @property int $id
 * @property int $plan_id
 * @property int $feature_id
 * @property string $value
 * @property int $sort_order
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 */
class PlanFeature extends Pivot
{
    public $incrementing = true;

    protected $fillable = [
        'plan_id',
        'feature_id',
        'value',
        'sort_order',
    ];

    public function __construct(array $attributes = [])
    {
        $this->setTable(config('subscriptions.tables.plan_features', 'plan_features'));
        parent::__construct($attributes);
    }

    protected function casts(): array
    {
        return [
            'plan_id' => 'integer',
            'feature_id' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(config('subscriptions.models.plan', Plan::class), 'plan_id');
    }

    public function feature(): BelongsTo
    {
        return $this->belongsTo(config('subscriptions.models.feature', Feature::class), 'feature_id');
    }
}
