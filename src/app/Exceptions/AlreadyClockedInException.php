<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * 当日すでに出勤打刻済みの状態で出勤しようとした場合の例外
 */
class AlreadyClockedInException extends RuntimeException
{
}
