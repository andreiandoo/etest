<?php

namespace App\Enums;

enum QuestionType: string
{
    case SingleChoice = 'single_choice';
    case MultipleChoice = 'multiple_choice';
    case TrueFalse = 'true_false';
    case Numeric = 'numeric';
    case ShortText = 'short_text';
    case Matching = 'matching';
    case Ordering = 'ordering';
}
