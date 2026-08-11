<?php

namespace App\Policies;

use App\Models\AttendanceLog;
use App\Models\User;

class AttendanceLogPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('attendance-logs.view');
    }

    public function view(User $user, AttendanceLog $attendanceLog): bool
    {
        return $user->can('attendance-logs.view');
    }
}
