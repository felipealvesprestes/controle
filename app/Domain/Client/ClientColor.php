<?php

namespace App\Domain\Client;

/**
 * The colour that tells clients apart in lists and in the timer (F-03).
 */
enum ClientColor: string
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
