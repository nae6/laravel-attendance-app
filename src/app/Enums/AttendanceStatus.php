<?php

namespace App\Enums;

enum AttendanceStatus: string
{
    // 当日の勤怠レコードがない状態(DBには保存しない)
    case OffDuty = '勤務外';
    case Working = '出勤中';
    case OnBreak = '休憩中';
    case Finished = '退勤済';
}
