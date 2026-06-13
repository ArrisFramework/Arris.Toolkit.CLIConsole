<?php

namespace Arris\Toolkit;

enum CLIOptionType: string
{
    case Flag = 'flag';               // --verbose (true если присутствует)
    case RequiredValue = 'required';  // --config=file.json
    case OptionalValue = 'optional';  // --log[=level]
}
