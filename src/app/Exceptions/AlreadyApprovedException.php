<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * 承認済みの修正申請を再承認しようとした場合の例外
 */
class AlreadyApprovedException extends RuntimeException
{
}
