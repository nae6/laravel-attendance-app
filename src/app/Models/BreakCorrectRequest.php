<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BreakCorrectRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'attendance_correct_request_id',
        'requested_break_start',
        'requested_break_end',
    ];

    protected $casts = [
        'requested_break_start' => 'datetime',
        'requested_break_end' => 'datetime',
    ];

    /**
     * 休憩時間の修正申請内容を取得
     */
    public function attendanceCorrectRequest(): BelongsTo {
        return $this->belongsTo(AttendanceCorrectRequest::class);
    }
}
