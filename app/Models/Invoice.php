<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
}