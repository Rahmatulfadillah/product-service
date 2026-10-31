<?php
namespace App\Exceptions;

use Exception;

class ProductNotFoundException extends Exception
{
    public function __construct(int $productId)
    {
        
        parent::__construct("Product dengan id {$productId} tidak ditemukan");
    }
}