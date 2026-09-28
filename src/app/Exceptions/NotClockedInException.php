<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * 当日の出勤記録がない状態で打刻しようとした場合の例外
 */
class NotClockedInException extends RuntimeException
{
}
