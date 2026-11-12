<?php

namespace App\Exceptions;

use RuntimeException;

/** Aturan bisnis penjualan/stok yang dilanggar; pesannya (bahasa Indonesia) langsung ditampilkan ke staf. */
class SaleException extends RuntimeException
{
}
