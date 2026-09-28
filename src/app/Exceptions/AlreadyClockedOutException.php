<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * 当日すでに退勤済みの状態で打刻しようとした場合の例外
 */
class AlreadyClockedOutException extends RuntimeException
{
}
