<?php

namespace App\Enums;

enum JourneyQuestionType: string
{
    case TrueFalse = 'true_false';
    case MultipleChoice = 'multiple_choice';
    case Dropdown = 'dropdown';
    case TextArea = 'text_area';
    case YesNo = 'yes_no';
    case Time = 'time';
    case ShortText = 'short_text';
    case Number = 'number';

    public function label(): string
    {
        return match ($this) {
            self::TrueFalse => 'True / False',
            self::MultipleChoice => 'Multiple Choice',
            self::Dropdown => 'Dropdown',
            self::TextArea => 'Text Area',
            self::YesNo => 'Yes / No',
            self::Time => 'Time (hh:mm)',
            self::ShortText => 'Short Text',
            self::Number => 'Number',
        };
    }

    /**
     * Only these types let the author supply their own answer list.
     */
    public function hasCustomOptions(): bool
    {
        return $this === self::MultipleChoice || $this === self::Dropdown;
    }

    /**
     * Free-text types are the only ones where a character limit is meaningful.
     */
    public function supportsMaxCharacters(): bool
    {
        return $this === self::TextArea || $this === self::ShortText;
    }
}
