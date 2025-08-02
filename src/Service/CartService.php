<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\RequestStack;
use App\Entity\Product;

class CartService
{   
    private RequestStack $requestStack;
    
    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }
    
    public function addProduct(Product $product, int $quantity): void
    {
         $session = $this->requestStack->getSession();
         
         $cart = $session->get('cart', []);
         
         if(isset($cart[$product->getId()]))
         {
             $cart[$product->getId()]['quantity'] += $quantity;
         }
         else
         {
             $cart[$product->getId()] = [
                 'product' => $product,
                 'quantity' => $quantity
             ];
         }
         
         $session->set('cart', $cart);
         
         $this->requestStack->session = $session;
    }
    
    public function getContents(): array
    {
         $session = $this->requestStack->getSession();
         $cart = $session->get('cart', []);
         if (!isset($cart['totalExcVat']))
         {
              $cart['totalExcVat'] = $this->calculateTotal($cart);
         }
         return $cart;
    }
    
    private function calculateTotal(array $cart): float
    {
         $total = 0;
         foreach ($cart as $itemId => $item)
         {
             if($itemId !== 'totalExcVat' && isset($item['product']) && isset($item['quantity']))
             {
                 $total += $item['product']->getPriceExcVat() * $item['quantity'];
             }
         }
         return $total;
    }
}