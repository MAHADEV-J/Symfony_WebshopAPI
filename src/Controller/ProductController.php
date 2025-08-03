<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Product;
use App\Service\CartService;

#[Route('/api', name: 'api_')]
final class ProductController extends AbstractController
{
    private $cartService;
    
    public function __construct(CartService $cartService)
    {
        $this->cartService = $cartService;
    }
    
    #[Route('/products', name: 'app_product', methods:['get'])]
    public function index(EntityManagerInterface $entityManager): JsonResponse
    {
        $products = $entityManager->getRepository(Product::class)->findAll();
        
        $data = [];
        
        foreach($products as $product)
        {
            $data[] = [
                'id' => $product->getId(),
                'name' => $product->getName(),
                'sku' => $product->getSku(),
                'priceExcVat' => $product->getPriceExcvat(),
                'priceIncVat' => round($product->getPriceExcvat() * 1.21, 2)
            ];
        }
        
        return $this->json($data);
    }
    
    #[Route('/cart/add', name: 'add_to_cart', methods:['post'])]
    public function addToCart(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        try
        {
            $product = $entityManager->getRepository(Product::class)
                                     ->find($request->toArray()['id']);

            if(!$product)
            {
                return $this->json([
                    'error' => 'Dit product bestaat niet.'
                ], 404);
            }
            
            $this->cartService->addProduct($product, $request->toArray()['quantity']);
        
            $cart = $this->cartService->getContents();
            $data = [];
        
            foreach($cart as $cart_itemId => $cart_item)
            {
                if($cart_itemId != 'totalExcVat')
                {
                    $data[] = [
                        'name' => $cart_item['product']->getName(),
                        'priceExcVat' => $cart_item['product']->getPriceExcvat(),
                        'priceIncVat' => round($cart_item['product']->getPriceExcVat() * 1.21, 2),
                        'quantity' => $cart_item['quantity'],
                        'subtotalExcVat' => $cart_item['product']->getPriceExcVat() * $cart_item['quantity'],
                        'subtotalIncVat' => round($cart_item['product']->getPriceExcVat() * 1.21 * $cart_item['quantity'], 2)
                    ];
                }
            }
             
            return $this->json(
            [
                'message' => "{$product->getName()} toegevoegd aan winkelwagen. De inhoud van de winkelwagen is:",
                'cart' => [
                    'products' => $data,
                    'totalExcVat' => $cart['totalExcVat'],
                    'totalIncVat' => round($cart['totalExcVat'] * 1.21, 2)
                ],
            ]);
        }
        catch (\Exception $e)
        {
            return $this->json(
            [
                'error' => 'Kon product niet toevoegen aan winkelwagen',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
