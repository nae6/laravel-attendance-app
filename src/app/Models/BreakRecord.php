<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BreakRecord extends Model
{
    protected $fillable = [
        'attendance_id',
        'break_start',
        'break_end',
    ];

    /**
     * 勤怠情報を取得
     */
    public function attendance(): BelongsTo {
        return $this->belongsTo(Attendance::class);
    }
}
