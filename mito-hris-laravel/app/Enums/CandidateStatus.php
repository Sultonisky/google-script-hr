<?php

namespace App\Enums;

enum CandidateStatus: string
{
    case NEW            = 'New';
    case SCREENING      = 'Screening';
    case INTERVIEW_HR   = 'Interview HR';
    case INTERVIEW_USER = 'Interview User';
    case OFFERING       = 'Offering';
    case ACCEPTED       = 'Accepted';
    case HOLD           = 'Hold';
    case BLACKLIST      = 'Blacklist';
    case REJECTED       = 'Rejected';

    public function badgeClass(): string
    {
        return match($this) {
            self::NEW            => 'bg-info text-white',
            self::SCREENING      => 'bg-primary text-white',
            self::INTERVIEW_HR,
            self::INTERVIEW_USER => 'bg-warning text-dark',
            self::OFFERING       => 'bg-info text-white',
            self::ACCEPTED       => 'bg-success text-white',
            self::HOLD           => 'bg-secondary text-white',
            self::BLACKLIST      => 'bg-dark text-white',
            self::REJECTED       => 'bg-danger text-white',
        };
    }
}
