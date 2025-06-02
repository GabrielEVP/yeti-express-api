<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyBill extends Model
{
    use HasFactory;

    protected $fillable = [
        "date",
        "name",
        "description",
        "method",
        "amount",
        "user_id",
    ];

    protected $casts = [
        "date" => "date",
        "amount" => "decimal:2",
        "method" => "string",
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
