<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClientAddress extends Model
{
    use HasFactory;

    protected $fillable = ["address", "client_id"];

    public $timestamps = false;

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function deliveries()
    {
        return $this->hasMany(Delivery::class);
    }
}
