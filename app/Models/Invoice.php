<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    public $timestamps = false;
    protected $guarded = ['id'];

    public function lines()
    {
        return $this->hasMany(InvoiceLine::class);
    }

    public function taxBreakdowns()
    {
        return $this->hasMany(InvoiceTaxBreakdown::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Relación con la factura original que rectifica (si aplica)
     */
    public function rectifiedInvoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'rectified_invoice_id');
    }
}