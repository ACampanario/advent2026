<?php
declare(strict_types=1);

namespace App\Service\Ai\Tool;

use InvalidArgumentException;

/**
 * Thrown by tools when the model sends invalid input; turned into an error result.
 */
class InvalidToolInputException extends InvalidArgumentException
{
}
