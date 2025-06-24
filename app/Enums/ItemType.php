<?php
namespace App\Enums;

enum ItemType: string
{
    case LAPTOP = 'Laptop';
    case PROJECTOR = 'Projector';
    case CAMERA = 'Camera';
    case HARDWARE = 'Hardware';
}