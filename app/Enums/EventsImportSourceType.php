<?php

namespace App\Enums;

enum EventsImportSourceType: string
{
    case Json = 'json';
    case Caldav = 'caldav';
}
