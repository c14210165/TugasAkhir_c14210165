<?php
namespace App\Enums;

enum ItemStatus: string
{
    case AVAILABLE = 'AVAILABLE';
    case BORROWED = 'BORROWED';
    case IN_MAINTENANCE = 'IN_MAINTENANCE';
}