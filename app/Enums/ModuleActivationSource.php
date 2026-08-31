<?php

namespace App\Enums;

enum ModuleActivationSource: string
{
    case Manual = 'manual';
    case Payment = 'payment';
    case Trial = 'trial';
    case System = 'system';
}
