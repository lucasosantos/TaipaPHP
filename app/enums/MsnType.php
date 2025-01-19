<?php

namespace App\enums;

enum MsnType: int {
    case SUCCESS = 1;
    case DANGER = 2;
    case ERROR = 3;
}