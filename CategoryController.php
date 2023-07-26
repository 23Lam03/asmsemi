<?php

namespace App\Controller;

use App\Entity\Category;
use App\Entity\Product;
use App\Form\CategoryType;
use App\Form\ProductType;
use App\Manager\CartManager;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use App\Repository\CategoryRepository;
class CategoryController extends AbstractController
{

    #[Route('/category/view/{id}', name: 'category_view')]
    public function viewCategory( $id, ManagerRegistry $doctrine, CartManager $cartManager): Response
    {
        $category_id = $doctrine->getManager();
        $categories = $category_id->getRepository(Category::class)->find($id);
        $products = $categories->getProducts();
        $cart = $cartManager->getCurrentCart();


        return $this->render('category/view.html.twig',['products' => $products,'categories'=>$categories, 'cart' => $cart]);
    }




    #[Route('/category/details', name: 'category_details')]
    public function detailsCategory(ManagerRegistry $doctrine, CartManager $cartManager): Response
    {
        $products = $doctrine->getRepository(Product::class)->findAll();
        $categories = $doctrine->getRepository(Category::class)->findAll();
        $cart = $cartManager->getCurrentCart();

        return $this->render('category/details.html.twig', [
            'products' => $products, 'categories'=>$categories, 'cart' => $cart
        ]);
    }

    #[Route('/admin/category/delete/{id}', name: 'category_delete')]
    public function deleteCategory(ManagerRegistry $doctrine,$id)
    {
        $em = $doctrine->getManager();
        $categories = $em->getRepository(Category::class)->find($id);
        $em->remove($categories);
        $em->flush();

        $this->addFlash(
            'error',
            'Category deleted'
        );
        return $this->redirectToRoute('product_list');
    }




//    #[Route('/admin/category/create', name: 'category_create')]
//    public function createCategory(ManagerRegistry $doctrine,Request $request)
//    {
//
//        $products = $doctrine->getRepository(Category::class)->findAll();
//        $categories = new Category();
//        $form = $this->createForm(CategoryType::class, $categories);
//
//        $form->handleRequest($request);
//        if ($form->isSubmitted() && $form->isValid()) {
//            $em = $doctrine->getManager();
//            $em->persist($categories);
//            $em->flush();
//
//            $this->addFlash(
//                'notice',
//                'Category Added'
//            );
//
//            return $this->redirectToRoute('category_details', [
//                'id' => $categories->getId()
//            ]);
//
//        }
//
//        return $this->renderForm('category/create.html.twig', ['form' => $form, 'categories'=>$categories, '$products'=>$products]);
//    }

    #[Route('/admin/category/create', name: 'category_create')]
    public function createCategory(ManagerRegistry $doctrine, Request $request, SluggerInterface $slugger, CartManager $cartManager)
    {
        $products = $doctrine->getRepository(Category::class)->findAll();
        $categories = new Category();
        $form = $this->createForm(CategoryType::class, $categories);
        $cart = $cartManager->getCurrentCart();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Các đoạn code khác

            // upload file
            $logoImage = $form->get('logoImage')->getData();
            if ($logoImage) {
                $originalFilename = pathinfo($logoImage->getClientOriginalName(), PATHINFO_FILENAME);
                // this is needed to safely include the file name as part of the URL
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $logoImage->guessExtension();

                // Move the file to the directory where brochures are stored
                try {
                    $logoImage->move(
                        $this->getParameter('logoImage_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    $this->addFlash(
                        'error',
                        'Cannot upload'
                    );// ... handle exception if something happens during file upload
                }
                $categories->setLogo($newFilename);
            } else {
                $this->addFlash(
                    'error',
                    'Cannot upload'
                );// ... handle exception if something happens during file upload
            }

            $em = $doctrine->getManager();
            $em->persist($categories);
            $em->flush();

            $this->addFlash(
                'notice',
                'Product Added'
            );
            return $this->redirectToRoute('category_details', [
                'id' => $categories->getId()
            ]);
        }

        return $this->renderForm('category/create.html.twig', ['form' => $form, 'categories' => $categories, 'products' => $products, 'cart' => $cart]);
    }

//    #[Route('/category/edit/{id}', name: 'category_edit')]
//    public function editAction(ManagerRegistry $doctrine, int $id,Request $request): Response{
//        $entityManager = $doctrine->getManager();
//        $categories = $entityManager->getRepository(Category::class)->find($id);
//        $form = $this->createForm(CategoryType::class, @$categories);
//        $form->handleRequest($request);
//        $products = $doctrine->getRepository(Category::class)->findAll();
//        if ($form->isSubmitted() && $form->isValid()) {
//
//            $em = $doctrine->getManager();
//            $em->persist($categories);
//            $em->flush();
//            return $this->redirectToRoute('category_details', [
//                'id' => $categories->getId()
//            ]);
//
//        }
//        return $this->renderForm('category/edit.html.twig', ['form' => $form,'categories'=>$categories, '$products'=>$products]);
//    }

    #[Route('/category/edit/{id}', name: 'category_edit')]
    public function editAction(ManagerRegistry $doctrine, int $id, Request $request, SluggerInterface $slugger, CartManager $cartManager): Response
    {
        $entityManager = $doctrine->getManager();
        $categories = $entityManager->getRepository(Category::class)->find($id);
        $form = $this->createForm(CategoryType::class, $categories);
        $form->handleRequest($request);
        $products = $doctrine->getRepository(Category::class)->findAll();
        $cart = $cartManager->getCurrentCart();

        if ($form->isSubmitted() && $form->isValid()) {

            // upload file
            $logoImage = $form->get('logoImage')->getData();
            if ($logoImage) {
                $originalFilename = pathinfo($logoImage->getClientOriginalName(), PATHINFO_FILENAME);
                // this is needed to safely include the file name as part of the URL
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $logoImage->guessExtension();

                // Move the file to the directory where brochures are stored
                try {
                    $logoImage->move(
                        $this->getParameter('logoImage_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    $this->addFlash(
                        'error',
                        'Cannot upload'
                    );// ... handle exception if something happens during file upload
                }
                $categories->setLogo($newFilename);
            } else {
                $this->addFlash(
                    'error',
                    'Cannot upload'
                );// ... handle exception if something happens during file upload
            }

            $em = $doctrine->getManager();
            $em->persist($categories);
            $em->flush();

            return $this->redirectToRoute('category_details', [
                'id' => $categories->getId()
            ]);
        }

        return $this->renderForm('category/edit.html.twig', ['form' => $form, 'categories' => $categories, 'products' => $products, 'cart' => $cart]);
    }
}
