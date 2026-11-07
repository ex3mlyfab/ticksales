<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class ApplicationSetting extends Model
{
    use HasUlids;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'app_name',
        'currency',
        'logo_path',
    ];

    protected $attributes = [
        'app_name' => 'Sokora',
        'currency' => 'NGN',
    ];
}
