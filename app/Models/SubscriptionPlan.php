<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubscriptionPlan extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'name',
        'display_name',
        'description',
        'price_monthly',
        'max_branches',
        'features',
    ];

    protected $casts = [
        'features' => 'array',
    ];
}
