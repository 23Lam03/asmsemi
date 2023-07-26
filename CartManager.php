<?php


namespace App\Manager;

use App\Entity\Order;
use App\Factory\OrderFactory;
use App\Storage\CartSessionStorage;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Class CartManager
 * @package App\Manager
 */
class CartManager
{
    /**
     * @var CartSessionStorage
     */
    private $cartSessionStorage;

    /**
     * @var OrderFactory
     */
    private $cartFactory;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * CartManager constructor.
     *
     * @param CartSessionStorage $cartStorage
     * @param OrderFactory $orderFactory
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(
        CartSessionStorage $cartStorage,
        OrderFactory $orderFactory,
        EntityManagerInterface $entityManager
    ) {
        $this->cartSessionStorage = $cartStorage;
        $this->cartFactory = $orderFactory;
        $this->entityManager = $entityManager;
    }

    /**
     * Gets the current cart.
     *
     * @return Order
     */
    public function getCurrentCart(): Order
    {
        $cart = $this->cartSessionStorage->getCart();

        if (!$cart) {
            $cart = $this->cartFactory->create();
        }

        return $cart;
    }

    /**
     * Persists the cart in database and session.
     *
     * @param Order $cart
     */
    public function save(Order $cart): void
    {
        // Persist in database
        $this->entityManager->persist($cart);
        $this->entityManager->flush();
        // Persist in session
        $this->cartSessionStorage->setCart($cart);
    }

    /**
     * Clears all cart items.
     *
     * @param Order $cart
     */
    public function clearCartItems(Order $cart): void
    {
        $cart->getItems()->clear();
        $this->save($cart);
    }
    /**
     * Gets a cart item from the cart by its ID.
     *
     * @param Order $cart
     * @param int $itemId
     * @return CartItem|null
     */
    public function getCartItemById(Order $cart, int $itemId): ?CartItem
    {
        foreach ($cart->getItems() as $item) {
            if ($item->getId() === $itemId) {
                return $item;
            }
        }

        return null;
    }

    /**
     * Removes a cart item from the cart.
     *
     * @param Order $cart
     * @param CartItem $cartItem
     */
    public function removeCartItem(Order $cart, CartItem $cartItem): void
    {
        $cart->removeItem($cartItem);
    }

    /**
     * Gets the cart by its ID.
     *
     * @param int $orderId
     * @return Order|null
     */
    public function getCartById(int $orderId): ?Order
    {
        // Implement the logic to retrieve the cart by its ID from the database or any other source
        // For example:
        return $this->entityManager->getRepository(Order::class)->find($orderId);
    }


}


