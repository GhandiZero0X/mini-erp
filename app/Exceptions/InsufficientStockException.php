<?php

namespace App\Exceptions;

/** Pengurangan stok akan membuat saldo negatif (BR-07). Dipakai StockService::decrease(). */
class InsufficientStockException extends \RuntimeException
{
    public function __construct(
        public readonly int $productId,
        public readonly int $available,
        public readonly int $requested
    ) {
        parent::__construct("Stok produk #{$productId} tidak mencukupi. Tersedia: {$available}, diminta: {$requested}.");
    }
}
