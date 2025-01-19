<?php

namespace App\enums;

enum StatusCode: int {
    case OK = 200;
    case Created = 201;
    case NoContent = 204;
    case Accepted = 202;
    case NotFound = 404;
    case Unauthorized = 401;
    case BadRequest = 400;
    case Forbidden = 403;
    case MethodNotAllowed = 405;
    case InternalServerError = 500;
}