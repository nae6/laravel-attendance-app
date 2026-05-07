<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AttendanceCorrectRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'attendance_id',
        'requested_check_in',
        'requested_check_out',
        'reason',
        'approval_status',
    ];

    protected $casts = [
        'requested_check_in' => 'datetime',
        'requested_check_out' => 'datetime',
    ];

    // 修正申請の承認状態
    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';

    /**
     * 修正申請の承認状態（日本語設定）
     *
     * @return string
     */
    public function getStatusLabelAttribute(): string {
        return match ($this->approval_status) {
            self::STATUS_PENDING => '承認待ち',
            self::STATUS_APPROVED => '承認済み',
            self::STATUS_REJECTED => '否認',
        };
    }

    /**
     * 休憩時間の修正申請内容を取得
     */
    public function breakCorrectRequests(): HasMany {
        return $this->hasMany(BreakCorrectRequest::class);
    }
}