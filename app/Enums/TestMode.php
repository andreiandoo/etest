<?php

namespace App\Enums;

enum TestMode: string
{
    case Practice = 'practice';
    case Exam = 'exam';
    case Quick = 'quick';
    case Daily = 'daily';
    case Adaptive = 'adaptive';
    case Custom = 'custom';
}
