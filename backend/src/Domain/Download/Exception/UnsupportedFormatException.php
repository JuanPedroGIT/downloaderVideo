<?php

declare(strict_types=1);

namespace App\Domain\Download\Exception;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final class UnsupportedFormatException extends BadRequestHttpException {}
