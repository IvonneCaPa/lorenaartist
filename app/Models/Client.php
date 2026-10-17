<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'phone',
        'email',
        'tax_id',
        'tax_id_type',
        'address',
        'postal_code',
        'city',
        'country_code',
        'aesthetic_notes',
    ];
}
