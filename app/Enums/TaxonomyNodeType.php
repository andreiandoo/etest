<?php

namespace App\Enums;

enum TaxonomyNodeType: string
{
    case Domain = 'domain';
    case Exam = 'exam';
    case Certification = 'certification';
    case Subject = 'subject';
    case Chapter = 'chapter';
    case Topic = 'topic';
}
