<?php

namespace App\Enums;

enum PlayStatus: string
{
    case PLAYING = 'playing';
    case NOT_PLAYING = 'not_playing';
}
