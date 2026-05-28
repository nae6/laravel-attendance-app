<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;
use App\Models\Attendance;


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
        'created_at' => 'datetime',
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
     * ログインユーザーの勤怠履歴を取得
     */
    public function scopeForUser($query, $userId)
    {
        return $query->whereHas('attendance', function ($q) use ($userId) {
            $q->where('user_id', $userId);
        });
    }

    /**
     * 休憩時間の修正申請内容を取得
     *
     * @return HasMany
     */
    public function breakCorrectRequests(): HasMany {
        return $this->hasMany(BreakCorrectRequest::class);
    }

    /**
     * 勤怠情報を取得
     *
     * @return BelongsTo
     */
    public function attendance(): BelongsTo {
        return $this->belongsTo(Attendance::class);
    }

    /**
     * 承認済かどうか判定
     */
    public function isApproved(): bool
    {
        return $this->approval_status === self::STATUS_APPROVED;
    }
}