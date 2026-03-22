<?php

declare(strict_types=1);

namespace App\Domain\Document\Exception;

use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

final class ConversionException extends UnprocessableEntityHttpException {}
