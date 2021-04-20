<?php

/*
 * This file is part of the Sylius package.
 *
 * (c) Paweł Jędrzejewski
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace App\Command;

use Sylius\Bundle\ApiBundle\Command\ChannelCodeAwareInterface;
use Symfony\Component\Serializer\Annotation\Groups;

final class OneClickCheckout implements ChannelCodeAwareInterface
{
    /** @var string
     * @Groups("shop:order:one-click")
     */
    public $productVariantCode;

    /** @var string|null
     * @Groups("shop:order:one-click")
     */
    public $localeCode;

    /** @var string|null */
    private $channelCode;

    public function __construct(string $productVariantCode, ?string $localeCode)
    {
        $this->productVariantCode = $productVariantCode;
        $this->localeCode = $localeCode;
    }

    public function getChannelCode(): ?string
    {
        return $this->channelCode;
    }

    public function setChannelCode(?string $channelCode): void
    {
        $this->channelCode = $channelCode;
    }
}
