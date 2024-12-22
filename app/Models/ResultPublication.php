<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ResultPublication extends Model
{
    use HasFactory;

    protected $table = 'result_publications';
    protected $guarded = [];

    protected $casts = [
        'num_of_results' => 'integer',
    ];

    public function staff()
    {
        return $this->belongsTo(User::class, 'staff_user_id');
    }
}