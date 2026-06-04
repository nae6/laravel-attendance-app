<?php

namespace App\Enums;

enum AttendanceCorrectRequestStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
}
