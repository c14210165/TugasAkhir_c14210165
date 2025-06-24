<?php
namespace App\Enums;

enum LoanStatus: string
{
    case PENDING_UNIT = 'PENDING_UNIT';
    case PENDING_PTIK = 'PENDING_PTIK';
    case APPROVED = 'APPROVED';
    case REJECTED = 'REJECTED';
    case CANCELLED = 'CANCELLED';
    case ACTIVE = 'ACTIVE';
    case COMPLETED = 'COMPLETED';
}