<?php

declare(strict_types=1);

namespace AEFS\Core\View\Exception;

use InvalidArgumentException;

final class InvalidViewDataException extends InvalidArgumentException
{
    public static function reservedVariable(string $name): self
    {
        return new self(
            sprintf(
                'Viewvariabele [%s] is gereserveerd en mag niet worden overschreven.',
                $name
            )
        );
    }

    public static function invalidVariableName(string $name): self
    {
        return new self(
            sprintf(
                'Ongeldige naam voor viewvariabele [%s].',
                $name
            )
        );
    }
}