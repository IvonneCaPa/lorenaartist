<?php

namespace App\DTOs;

class CreateInvoiceDTO
{
    /**
     * @param array<int, array{concept: string, quantity: float, unit_price: float, vat_rate: float}> $lines
     */
    public function __construct(
        public readonly string $series,
        public readonly string $invoiceType,
        public readonly ?int $clientId,
        public readonly array $lines,
        public readonly int $createdByUserId,
        public readonly ?int $rectifiedInvoiceId = null,
        public readonly ?string $rectificationReason = null,
    ) {}
}