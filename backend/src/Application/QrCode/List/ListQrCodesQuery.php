<?php

declare(strict_types=1);

namespace App\Application\QrCode\List;

final readonly class ListQrCodesQuery
{
    public function __construct(public int $authUserId) {}
}
