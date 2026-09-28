<?php

namespace App\Models;

use App\Enums\AttendanceCorrectRequestStatus;
use App\Enums\AttendanceStatus;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory;

    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'check_in',
        'check_out',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'check_in' => 'datetime',
            'check_out' => 'datetime',
            'status' => AttendanceStatus::class,
        ];
    }

    /**
     * ユーザー情報を取得
     */
    public function user(): BelongsTo {
        return $this->belongsTo(User::class);
    }

    /**
     * 休憩情報を取得
     */
    public function breakRecords(): HasMany {
        return $this->hasMany(BreakRecord::class);
    }

    // 修正内容を取得
    public function correctRequests(): HasMany {
        return $this->hasMany(AttendanceCorrectRequest::class);
    }

    /**
     * 合計勤務時間(分)の取得
     *
     * @return Attribute<int, never>
     */
    protected function workMinutes(): Attribute {
        return Attribute::make(
            get: function (): int {
                if (!$this->check_in || !$this->check_out) {
                    return 0;
                }

                return $this->check_in->diffInMinutes($this->check_out, true)
                    - $this->break_minutes;
            },
        );
    }

    /**
     * 勤務時間（HH:MM形式）表示
     *
     * @return Attribute<string|null, never>
     */
    protected function workTime(): Attribute {
        return Attribute::make(
            get: fn (): ?string => $this->formatMinutes($this->work_minutes),
        );
    }

    // 未認証の申請内容のうち最新の１件を取得
    public function pendingCorrectRequest(): ?AttendanceCorrectRequest {
        return $this->correctRequests()
            ->with('breakCorrectRequests')
            ->where('approval_status', AttendanceCorrectRequestStatus::Pending)
            ->latest()
            ->first();
    }

    /**
     * 合計休憩時間(分)の取得
     *
     * @return Attribute<int, never>
     */
    protected function breakMinutes(): Attribute {
        return Attribute::make(
            get: fn (): int => $this->breakRecords->sum(function ($break) {
                if (!$break->break_start || !$break->break_end) {
                    return 0;
                }

                return $break->break_start->diffInMinutes($break->break_end, true);
            }),
        );
    }

    /**
     * 休憩時間（HH:MM形式）表示
     *
     * @return Attribute<string|null, never>
     */
    protected function breakTime(): Attribute {
        return Attribute::make(
            get: fn (): ?string => $this->formatMinutes($this->break_minutes),
        );
    }

    /**
     * 分数をHH:MM形式に変換(0分以下はnull)
     */
    private function formatMinutes(int $totalMinutes): ?string {
        if ($totalMinutes <= 0) {
            return null;
        }

        return sprintf('%d:%02d', intdiv($totalMinutes, 60), $totalMinutes % 60);
    }
}
