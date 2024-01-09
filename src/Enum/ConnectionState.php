<?php

namespace la\ConnectionManager\Enum;

enum ConnectionState: int
{
    case NOT_IN_USE = 0;
    case IN_USE = 1;
}
