<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Appointment extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'service_id',
        'starts_at',
        'ends_at',
        'status',
        'notes',
        'invoice_id',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * Scope para detectar colisiones de horario (excluyendo canceladas).
     */
    public function scopeOverlapping(Builder $query, Carbon|string $start, Carbon|string $end, ?int $ignoreId = null): Builder
    {
        return $query
            ->whereNotIn('status', ['cancelled', 'no_show'])
            ->where('starts_at', '<', $end)
            ->where('ends_at', '>', $start)
            ->when($ignoreId, fn (Builder $q) => $q->where('id', '!=', $ignoreId));
    }
}