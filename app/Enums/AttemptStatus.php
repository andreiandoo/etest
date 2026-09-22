<?php

namespace App\Enums;

enum AttemptStatus: string
{
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Abandoned = 'abandoned';
}
