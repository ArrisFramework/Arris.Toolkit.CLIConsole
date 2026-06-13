<?php

namespace Arris\Toolkit;

enum CLIOptionType: string
{
    case Flag = 'flag';
    case WithValue = 'with_value';
    case WithOptionalValue = 'with_optional';
}