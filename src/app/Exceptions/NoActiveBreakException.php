<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * 終了できる休憩がない状態で休憩戻りしようとした場合の例外
 */
class NoActiveBreakException extends RuntimeException
{
}
