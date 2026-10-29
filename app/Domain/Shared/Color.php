<?php

namespace App\Domain\Shared;

/**
 * The colours that tell clients and categories apart in lists and in the timer (F-03, F-04).
 */
enum Color: string
{
    case Petrol = 'petrol';
    case Green = 'green';
    case Amber = 'amber';
    case Red = 'red';
    case Violet = 'violet';
    case Blue = 'blue';
    case Pink = 'pink';
    case Slate = 'slate';
}
