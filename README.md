# Symfony_WebshopAPI
Dit is een web-API gebouwd met Symfony, die een heel eenvoudige webshop vertegenwoordigt. Ik had nog nooit eerder een Symfony-project opgezet, maar ik ken Symfony wel zijdelings via Laravel, dus sommige dingen kwamen me wel bekend voor.

## Beschrijving van de API
De API heeft twee endpoints, `/api/products` en `/api/cart/add`.

Naar `/api/products` kan alleen een GET request gedaan worden. De API geeft dan een lijst van alle producten terug in het formaat:
```
id: int,
name: string,
sku: string,
priceExcVat: number,
priceIncVat: number
```
In de database staat alleen `priceExcVat`, maar `priceIncVat` wordt in de code berekend iedere keer dat de request gedaan wordt. Dit heb ik bewust zo gedaan om te laten zien dat ik de API ook bewerkingen kan laten uitvoeren op data uit de database in plaats van alles rechtstreeks uit de database te halen.  
Het btw-percentage van 21% is gehardcoded. Nu zou je kunnen zeggen dat dit wel redelijk vastligt en dat je daarom ook de bedragen inclusief btw in de database zou kunnen zetten. Maar dit was maar een voorbeeld om bovenstaande aan te tonen.  
De API geeft 20 producten terug. Dit is een willekeurig gekozen aantal om te voldoen aan de eis dat het minstens 10 producten moesten zijn.

Naar `/api/cart/add` kan alleen een POST request gedaan worden. Daarbij dient de request body als volgt opgebouwd te zijn:
```
id: int,
quantity: int
```
De API geeft een response terug in het volgende formaat:
```
message: string,
cart: {
    products: [
    
        name: string,
        priceExcVat: number,
        priceIncVat: number,
        quantity: int,
        subtotalExcVat: number,
        subtotalIncVat: number
    ]
    totalExcVat: number,
    totalIncVat: number
}
```
`message` is een bericht dat bevestigt dat het product aan de winkelwagen is toegevoegd en dat hieronder de winkelwagen volgt. `cart` is de eigenlijke winkelwagen met een lijst van producten en een totaal exclusief en inclusief btw. Ook hier geldt dat de bedragen inclusief btw berekend zijn.

## Database-instellingen
De API maakt gebruik van een MySQL-database. Let erop dat `extension=pdo_mysql` in `php.ini` aan staat en dat in `.env` de volgende regel toegevoegd is:

```DATABASE_URL="pdo-mysql://root:@127.0.0.1:3306/app?serverVersion=8.0.32&charset=utf8mb4"```

Ik heb een Migration gemaakt om de databasetabel op te zetten, deze is te vinden in `/migrations/Version20250801183246.php`.

Symfony biedt niet zoals Laravel seeders om de databasetabel automatisch te vullen, daarom heb ik dat handmatig gedaan. Hieronder staat een SQL-script dat je kunt draaien om de tabel te vullen:
```
# hier SQL graag
```

Het id, de naam en het SKU zijn allemaal uniek. Behalve het id zijn dit geen UNIQUE constraints, ik heb er gewoon zelf voor gezorgd dat ze uniek zijn. In een productie-applicatie zou ik dit wel netjes doen door middel van UNIQUE constraints.  
Ik heb opgezocht dat een SKU meestal tussen de 8 tot 12 tekens lang is. De veldlengte 30 in de database is een min of meer willekeurig gekozen getal groter dan 12, om er zeker van te zijn dat ik van dit soort details geen last zou hebben bij het vullen van de database (en stel dat in een productie-situatie de SKU's ooit zouden veranderen waardoor ze langer werden, dan zou het wel fijn zijn als je daar nog ruimte voor had in de database.)

## Beschrijving van de code
De code die ik zelf geschreven heb bestaat eigenlijk maar uit vier bestanden, afgezien van de Migration (zie hierboven onder Database-instellingen):
`/src/Entity/Product.php`
`/src/Repository/ProductRepository.php`
`/src/Controller/ProductController.php`
`/src/Service/CartService.php`

`Product` en `ProductRepository` bestaan uit door Symfony automatisch gegenereerde code waar ik zelf niets aan veranderd heb (afgezien van het verbeteren van typfouten). Het echte werk van de API wordt in `ProductController` en `CartService` gedaan (zie hieronder).

## Opmerkingen over de architectuur
De API-endpoints en de functies om ze af te handelen worden gedefinieerd in `ProductController`.

### Functies
De functie `index()` is puur een mapping van de databaselaag (omgezet naar een `Product`-object middels de Doctrine ORM) naar de gewenste JSON-output, afgezien van `priceIncVat` wat hier berekend wordt.

De functie `addToCart()` valideert eerst de input door te controleren of het product-id daadwerkelijk bestaat. Ik heb er hier bewust voor gekozen om geen Validator te gebruiken, omdat er maar één ding gecontroleerd hoeft te worden. Wat mij betreft geldt: hoe eenvoudiger hoe beter, en ik denk dat dit teveel tijd gekost zou hebben voor zoiets eenvoudigs.

Na de validatie wordt de functie `addProduct` aangeroepen om de producten uit de POST request body in de sessie te zetten. Vervolgens wordt de inhoud van de sessie weer opgehaald middels de functie `getContents`, waarna er nog wat bewerkingen op gedaan worden door de `ProductController`. Ik heb bewust gekozen voor deze opzet met een Service, waarbij de `CartService` zich bezighoudt met het toevoegen van producten aan de sessie en de `ProductController` alleen met het afhandelen en doorgeven van de request body en het in elkaar zetten van de uiteindelijke JSON-output voor de response body. Dit heb ik gedaan omdat dit een nettere scheiding van verantwoordelijkheden (separation of concerns) is, zodat de code overzichtelijk blijft, en ook omdat ik per se wilde dat de response een nette boodschap plus een uitgebreidere winkelwagen zou bevatten, met daarin ook de subtotalen en totalen en niet alleen maar een lijst van hoeveel je van elk product in de winkelwagen hebt.  

### Redis

Ik heb ervoor gekozen Redis niet te gebruiken, maar de winkelwagen rechtstreeks in de sessie op te slaan. Dit heb ik gedaan uit tijdsoverwegingen, omdat het moeilijker dan gedacht bleek om Redis te installeren onder Windows en ik geen WSL heb. Ik denk dat ik wel geweten zou hebben hoe het zou moeten. Je zou dan `config/packages/framework.yaml` als volgt moeten bijwerken:
```
framework:
    session:
        handler_id: Symfony\Component\HttpFoundation\Session\Storage\Handler\RedisSessionHandler
```
En `config/services.yaml` als volgt:
```
services:
    Redis:
        class: Redis
        arguments:
            - '%env(REDIS_URL)%'
    
    Symfony\Component\HttpFoundation\Session\Storage\Handler\RedisSessionHandler:
        arguments:
            - '@Redis'
```
En in `.env` zou je de volgende regel moeten toevoegen:
```
REDIS_URL=redis://localhost:6379
```
(aangenomen dat Redis op poort 6379 communiceert). Als het goed is, zou er aan de code dan verder niets hoeven veranderen.

## Algemene opmerkingen
Ik heb er alles bij elkaar, inclusief het schrijven van deze readme, iets langer dan 6 uur over gedaan. Houd er rekening mee dat mijn laptop 8 GB RAM heeft en af en toe plotseling erg traag wordt.

## Wat ik anders gedaan zou hebben als ik meer tijd had of deze opdracht nog een keer mocht doen
- Niet van tevoren een Git repository opzetten en dan Symfony installeren en een project aanmaken, maar eerst Symfony installeren en een project aanmaken en dan in de projectmap een Git repository opzetten.
- Kleur en maat van kledingstukken als een aparte kolom in de database opnemen zodat je in de request body van de POST request naar `/api/cart/add` kunt aangeven welke maat en kleur je van elk kledingstuk wilt. Hierdoor kan het aantal unieke id's en dus records in de database verkleind worden.
- Van tevoren uitzoeken hoe je WSL en Redis installeert onder Windows en dan Redis gebruiken voor sessiebeheer.