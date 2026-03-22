<?php

declare(strict_types=1);

namespace App\Domain\Document\Exception;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final class InvalidDocumentException extends BadRequestHttpException {}
