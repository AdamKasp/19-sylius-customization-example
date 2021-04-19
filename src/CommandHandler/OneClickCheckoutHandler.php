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

namespace App\CommandHandler;

use App\Command\OneClickCheckout;
use Doctrine\Persistence\ObjectManager;
use SM\Factory\FactoryInterface as StateMachineFactoryInterface;
use Sylius\Bundle\ApiBundle\Context\UserContextInterface;
use Sylius\Component\Channel\Repository\ChannelRepositoryInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\OrderItemInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Core\OrderCheckoutTransitions;
use Sylius\Component\Core\Repository\ProductVariantRepositoryInterface;
use Sylius\Component\Order\Modifier\OrderItemQuantityModifierInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

final class OneClickCheckoutHandler implements MessageHandlerInterface
{
    /** @var UserContextInterface */
    private $userContext;

    /** @var StatemachineFactoryInterface */
    private $stateMachineFactory;

    /** @var FactoryInterface */
    private $orderFactory;

    /** @var FactoryInterface */
    private $orderItemFactory;

    /** @var OrderItemQuantityModifierInterface */
    private $orderItemQuantityModifier;

    /** @var ProductVariantRepositoryInterface */
    private $productVariantRepository;

    /** @var ObjectManager */
    private $objectManager;

    /** @var ChannelRepositoryInterface */
    private $channelRepository;

    public function __construct(
        UserContextInterface $userContext,
        StateMachineFactoryInterface $stateMachineFactory,
        FactoryInterface $orderFactory,
        FactoryInterface $orderItemFactory,
        OrderItemQuantityModifierInterface $orderItemQuantityModifier,
        ProductVariantRepositoryInterface $productVariantRepository,
        ObjectManager $objectManager,
        ChannelRepositoryInterface $channelRepository
    ) {
        $this->userContext = $userContext;
        $this->stateMachineFactory = $stateMachineFactory;
        $this->orderFactory = $orderFactory;
        $this->orderItemFactory = $orderItemFactory;
        $this->orderItemQuantityModifier = $orderItemQuantityModifier;
        $this->productVariantRepository = $productVariantRepository;
        $this->objectManager = $objectManager;
        $this->channelRepository = $channelRepository;
    }

    public function __invoke(OneClickCheckout $command): OrderInterface
    {
        /** @var ProductVariantInterface $productVariant */
        $productVariant = $this->productVariantRepository->find($command->productVariantCode);

        /** @var OrderInterface $order */
        $order = $this->orderFactory->createNew();
        /** @var OrderItemInterface $orderItem */
        $orderItem = $this->orderItemFactory->createNew();

        $orderItem->setVariant($productVariant);
        $this->orderItemQuantityModifier->modify($orderItem, 1);

        $order->addItem($orderItem);

        /** @var CustomerInterface $customer */
        $customer = $this->userContext->getUser()->getCustomer();

        $order->setCustomer($customer);
        $order->setShippingAddress($customer->getDefaultAddress());
        $order->setBillingAddress($customer->getDefaultAddress());

        /** @var ChannelInterface $channel */
        $channel = $this->channelRepository->findOneByCode($command->getChannelCode());

        $order->setChannel($channel);
        $order->setCurrencyCode($channel->getBaseCurrency());
        $order->setLocaleCode($command->localeCode);

        $orderCheckoutStateMachine = $this->stateMachineFactory->get($order, OrderCheckoutTransitions::GRAPH);
        $orderCheckoutStateMachine->apply(OrderCheckoutTransitions::TRANSITION_ADDRESS);
        $orderCheckoutStateMachine->apply(OrderCheckoutTransitions::TRANSITION_SELECT_SHIPPING);
        $orderCheckoutStateMachine->apply(OrderCheckoutTransitions::TRANSITION_SELECT_PAYMENT);
        $orderCheckoutStateMachine->apply(OrderCheckoutTransitions::TRANSITION_COMPLETE);

        $this->objectManager->persist($order);
        $this->objectManager->flush();

        return $order;
    }
}
