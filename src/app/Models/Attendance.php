<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Attendance extends Model
{
    /** @use HasFactory<UserFactory> */
    use HasFactory;

    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'work_date',
        'check_in',
        'check_out',
        'status',
    ];

    /**
     * ユーザー情報を取得
     */
    public function user(): BelongsTo {
        return $this->belongsTo(User::class);
    }

    /**
     * 休憩情報を取得
     */
    public function break_time(): HasMany {
        return $this->HasMany(BreakRecord::class);
    }
}
