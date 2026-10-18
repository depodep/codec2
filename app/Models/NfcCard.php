<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NfcCard extends Model
{
    use HasFactory;

    protected $table = 'nfc_cards';

    protected $fillable = [
        'user_id',
        'tag_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}