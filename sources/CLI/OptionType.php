<?php

namespace Arris\Toolkit\CLI;

enum OptionType: string
{
    case Flag = 'flag';
    case WithValue = 'with_value';
    case WithOptionalValue = 'with_optional';
}