<?php

namespace App\Models;

use App\Models\Concerns\HasHumanId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class Asset extends Model
{
    use HasHumanId;

    protected $primaryKey = 'asset_id';
    protected $idPrefix = 'AST';
    protected $guarded = [];
    protected $casts = [
        'quantity' => 'integer',
        'rental_start' => 'date',
        'rental_end' => 'date',
        'procurement_date' => 'date',
        'archived_at' => 'datetime',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id', 'client_id');
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(MarketingCampaign::class, 'campaign_id', 'campaign_id');
    }

    public function holder(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'holder_employee_id', 'employee_id');
    }

    public function pic(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'pic_employee_id', 'employee_id');
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(AssetBooking::class, 'asset_id', 'asset_id');
    }

    public function conditionLogs(): HasMany
    {
        return $this->hasMany(AssetConditionLog::class, 'asset_id', 'asset_id');
    }

    public function scopeAvailableFor($query, Carbon $from, Carbon $to)
    {
        return $query
            ->where('status', '!=', 'Maintenance')
            ->where('condition', 'Baik')
            ->where(function ($q) use ($from, $to) {
                $q->whereDoesntHave('bookings', function ($b) use ($from, $to) {
                    $b->whereIn('status', ['Approved', 'Booked'])
                        ->where(function ($x) use ($from, $to) {
                            $x->whereDate('start_date', '<=', $to->toDateString())
                                ->whereDate('end_date', '>=', $from->toDateString());
                        });
                });
            });
    }

    public function isAvailableBetween(Carbon $from, Carbon $to, ?string $exceptBookingId = null): bool
    {
        if ($this->status === 'Maintenance' || $this->condition !== 'Baik') {
            return false;
        }
        if ($this->asset_group === 'ooh' && $this->status === 'Booked') {
            return false;
        }

        return ! $this->bookings()
            ->whereIn('status', ['Approved', 'Booked'])
            ->when($exceptBookingId, fn ($q) => $q->where('booking_id', '!=', $exceptBookingId))
            ->where(function ($q) use ($from, $to) {
                $q->whereDate('start_date', '<=', $to->toDateString())
                    ->whereDate('end_date', '>=', $from->toDateString());
            })
            ->exists();
    }
}