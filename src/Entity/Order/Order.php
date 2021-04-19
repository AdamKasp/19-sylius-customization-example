<?php

declare(strict_types=1);

namespace App\Entity\Order;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;
use Sylius\Component\Core\Model\Order as BaseOrder;

/**
 * @ORM\Entity
 * @ORM\Table(name="sylius_order")
 *
 * @ApiResource(
 *      routePrefix="shop",
 *      denormalizationContext={"groups"={"admin:order:read"}},
 *      itemOperations={
 *          "oneClickCheckout"={
 *              "path"="/order/one-click-checkout",
 *              "method"="PATCH",
 *              "messanger"="input",
 *              "input"="App\Command\OneClickCheckout"
 *          }
 *       }
 *     )
 */
class Order extends BaseOrder
{
}
