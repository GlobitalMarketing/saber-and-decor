<?php
namespace App\Repositories\Orders;

interface OrderRepositoryInterface
{
    public function store(array $orders, int $installationId);
    public function orders();
}
