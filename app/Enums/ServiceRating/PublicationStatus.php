<?php

namespace App\Enums\ServiceRating;

enum PublicationStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Held = 'held';
    case Superseded = 'superseded';
}
