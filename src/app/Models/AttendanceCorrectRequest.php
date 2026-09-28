<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;
use App\Enums\AttendanceCorrectRequestStatus;
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

    protected function casts(): array
    {
        return [
            'requested_check_in' => 'datetime',
            'requested_check_out' => 'datetime',
            'created_at' => 'datetime',
            'approval_status' => AttendanceCorrectRequestStatus::class,
        ];
    }

    /**
     * 修正申請の承認状態（日本語設定）
     *
     * @return Attribute<string, never>
     */
    protected function statusLabel(): Attribute {
        return Attribute::make(
            get: fn (): string => match ($this->approval_status) {
                AttendanceCorrectRequestStatus::Pending => '承認待ち',
                AttendanceCorrectRequestStatus::Approved => '承認済み',
                AttendanceCorrectRequestStatus::Rejected => '否認',
            },
        );
    }

    /**
     * ログインユーザーの勤怠履歴を取得
     */
    #[Scope]
    protected function forUser(Builder $query, int $userId): void
    {
        $query->whereHas('attendance', function (Builder $q) use ($userId) {
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
        return $this->approval_status === AttendanceCorrectRequestStatus::Approved;
    }
}