<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Product;

#[Route('/api', name: 'api_')]
final class ProductController extends AbstractController
{
    #[Route('/products', name: 'app_product', methods:['get'])]
    public function index(EntityManagerInterface $entityManager): JsonResponse
    {
        $products = $entityManager->getRepository(Product::class)->findAll();
        
        $data = [];
        
        foreach ($products as $product)
        {
             $data[] = [
                 'id' => $product->getId(),
                 'name' => $product->getName(),
                 'sku' => $product->getSku(),
                 'priceExcVar' => $product->getPriceExcvar(),
                 'priceIncVar' => $product->getPriceExcvar() * 1.21
             ];
        }
        
        return $this->json($data);
    }
}
