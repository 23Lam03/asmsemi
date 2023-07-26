<?php

namespace App\Controller;

use App\Entity\Category;
use App\Entity\Product;
use App\Form\AddToCartType;
use App\Form\ProductType;
use App\Manager\CartManager;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use function Symfony\Component\String\u;
use Doctrine\Common\Collections\ArrayCollection;

class ProductController extends AbstractController
{
    #[Route('/', name: 'product_list')]
    public function listAction(ManagerRegistry $doctrine,Request $request, CartManager $cartManager): Response
    {
        $products = $doctrine->getRepository(Product::class)->findAll();
        $categories = $doctrine->getRepository(Category::class)->findAll();
        $cart = $cartManager->getCurrentCart();

        return $this->render('product/index.html.twig', [
            'products' => $products, 'categories'=>$categories, 'cart' => $cart
        ]);
    }

    #[Route('/product', name:'product')]
    public function index(ManagerRegistry $doctrine): Response
    {
        $category = new Category();
        $category->setName('Computer Peripherals');


        $product = new Product();
        $product->setName('Keyboard');
        $product->setPrice(19.99);
        $product->setQuantity(2);
        $product->setDate(new DateTime(0-2-0));
        $product->setDescription('Ergonomic and stylish!');

        // relates this product to the category
        $product->setCategory($category);

        $entityManager = $doctrine->getManager();
        $entityManager->persist($category);
        $entityManager->persist($product);
        $entityManager->flush();

        return new Response(
            'Saved new product with id: ' . $product->getId()
            . ' and new category with id: ' . $category->getId()
        );
    }

    #[Route('/insertUser', name:'product')]
    public function insertAction(ManagerRegistry $doctrine): Response
    {
        $user= new User();

        $user->setEmail('abc@gmail.com');
        $user->setPassword("123@abc");
        // relates this product to the category

        $entityManager = $doctrine->getManager();
        $entityManager->persist($user);
        $entityManager->persist($user);
        $entityManager->flush();

        return new Response(
            'Saved new product with id: '.$user->getId()
            .' and new category with id: '.$user->getId()
        );
    }

    #[Route('/product/details/{id}', name: 'product_details')]
    public function detailsAction(ManagerRegistry $doctrine, $id, Request $request, CartManager $cartManager)
    {

        $products = $doctrine->getRepository(Product::class)->find($id);
        $categories = $doctrine->getRepository(Category::class)->findAll();
        $cart = $cartManager->getCurrentCart();

        $form = $this->createForm(AddToCartType::class);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $item = $form->getData();
            $item->setProduct($products);

            $cart = $cartManager->getCurrentCart();
            $cart
                ->addItem($item)
                ->setUpdatedAt(new \DateTime());

            $cartManager->save($cart);

            return $this->redirectToRoute('product_details', ['id' => $products->getId()]);
        }


        return $this->render('product/details.html.twig',[
            'products'=>$products,
            'categories'=>$categories,
            'cart' => $cart,
            'form' => $form->createView()
        ]);
    }

    #[Route('/admin/product/delete/{id}', name: 'product_delete')]
    public function deleteAction(ManagerRegistry $doctrine,$id)
    {
        $em = $doctrine->getManager();
        $product = $em->getRepository(Product::class)->find($id);
        $em->remove($product);
        $em->flush();

        $this->addFlash(
            'error',
            'Product deleted'
        );
        return $this->redirectToRoute('product_list');
    }



    #[Route('/admin/product/create', name: 'product_create')]
    public function createAction(ManagerRegistry $doctrine,Request $request, SluggerInterface $slugger, CartManager $cartManager)
    {
        $categories = $doctrine->getRepository(Category::class)->findAll();
        $product = new Product();
        $form = $this->createForm(ProductType::class, $product);
        $cart = $cartManager->getCurrentCart();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // upload file
            $productImage = $form->get('productImage')->getData();
            if ($productImage) {
                $originalFilename = pathinfo($productImage->getClientOriginalName(), PATHINFO_FILENAME);
                // this is needed to safely include the file name as part of the URL
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $productImage->guessExtension();

                // Move the file to the directory where brochures are stored
                try {
                    $productImage->move(
                        $this->getParameter('productImages_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    $this->addFlash(
                        'error',
                        'Cannot upload'
                    );// ... handle exception if something happens during file upload
                }
                $product->setProductImage($newFilename);
            }else{
                $this->addFlash(
                    'error',
                    'Cannot upload'
                );// ... handle exception if something happens during file upload
            }
            $em = $doctrine->getManager();
            $em->persist($product);
            $em->flush();

            $this->addFlash(
                'notice',
                'Product Added'
            );
            return $this->redirectToRoute('product_list');
        }
        return $this->renderForm('product/create.html.twig', ['form' => $form, 'categories'=>$categories, 'cart' => $cart]);
    }

    #[Route('/product/productByCat/{id}', name: 'productByCat')]
    public function productByCatAction(ManagerRegistry $doctrine, $id, CartManager $cartManager):Response
    {
        $category = $doctrine->getRepository(Category::class)->find($id);
        $products = $category->getProducts();
        $categories = $doctrine->getRepository(Category::class)->findAll();
        $cart = $cartManager->getCurrentCart();
        return $this->render('category/details.html.twig', ['products' => $products,
            'categories' => $categories, 'cart' => $cart]);
    }

    #[Route('/admin/product/edit/{id}', name: 'product_edit')]
    public function editAction(ManagerRegistry $doctrine, int $id,Request $request,SluggerInterface $slugger, CartManager $cartManager): Response{
        $entityManager = $doctrine->getManager();
        $product = $entityManager->getRepository(Product::class)->find($id);
        $form = $this->createForm(ProductType::class, @$product);
        $form->handleRequest($request);
        $categories = $doctrine->getRepository(Category::class)->findAll();
        $cart = $cartManager->getCurrentCart();

        if ($form->isSubmitted() && $form->isValid()) {
            //upload file
            $productImage = $form->get('productImage')->getData();
            if ($productImage) {
                $originalFilename = pathinfo($productImage->getClientOriginalName(), PATHINFO_FILENAME);
                // this is needed to safely include the file name as part of the URL
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $productImage->guessExtension();
                // Move the file to the directory where brochures are stored
                try {
                    $productImage->move(
                        $this->getParameter('productImages_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    $this->addFlash(
                        'error',
                        'Cannot upload'
                    );// ... handle exception if something happens during file upload
                }
                $product->setProductImage($newFilename);
            }else{
                $this->addFlash(
                    'error',
                    'Cannot upload'
                );// ... handle exception if something happens during file upload
            }

            $em = $doctrine->getManager();
            $em->persist($product);
            $em->flush();
            return $this->redirectToRoute('product_list', [
                'id' => $product->getId()
            ]);

        }
        return $this->renderForm('product/edit.html.twig', ['form' => $form, 'categories' => $categories, 'cart' => $cart]);
    }



}
