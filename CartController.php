<?php

namespace App\Controller;

use App\Entity\Category;
use App\Entity\Order;
use App\Form\CartType;
use App\Manager\CartManager;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Session\SessionInterface;


class CartController extends AbstractController
{
    #[Route('/cart', name: 'cart')]
    public function index(ManagerRegistry $doctrine, CartManager $cartManager, Request $request): Response
    {
        $categories = $doctrine->getRepository(Category::class)->findAll();
        $cart = $cartManager->getCurrentCart();

        $form = $this->createForm(CartType::class, $cart);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $cart->setUpdatedAt(new \DateTime());
            $cartManager->save($cart);

            return $this->redirectToRoute('cart');
        }

        return $this->render('cart/index.html.twig', [
            'cart' => $cart,
            'categories' => $categories,
            'form' => $form->createView()
        ]);
    }

    #[Route('/cart/{orderId}/remove/{itemId}', name: 'remove_item', methods: ['GET'])]
    public function removeItemAction(
        ManagerRegistry $doctrine,
        CartManager $cartManager,
        int $orderId,
        int $itemId,
        SessionInterface $session
    ): Response {
        $cart = $cartManager->getCartById($orderId);
        if ($cart) {
            $cartItem = null;
            foreach ($cart->getItems() as $item) {
                if ($item->getId() === $itemId) {
                    $cartItem = $item;
                    break;
                }
            }

            if ($cartItem) {
                $cart->removeItem($cartItem);
                $cartManager->save($cart);
            }
        }

        return $this->redirectToRoute('cart');
    }

    #[Route('/cart/{orderId}/clear', name: 'clear_items', methods: ['GET'])]
    public function clearItemsAction(ManagerRegistry $doctrine, CartManager $cartManager, int $orderId, SessionInterface $session): Response
    {
        $entityManager = $doctrine->getManager();
        $cartRepository = $entityManager->getRepository(Order::class);
        $cart = $cartRepository->find($orderId);
        if ($cart) {
            $cartManager->clearCartItems($cart);
            $cartManager->save($cart);
        }
        return $this->redirectToRoute('cart');
    }
}
