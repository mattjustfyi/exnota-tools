<?php

namespace App\Enums;

enum PlayStatus: string
{
    case PLAYING = 'playing';
    case STOPPED = 'stopped';
}
