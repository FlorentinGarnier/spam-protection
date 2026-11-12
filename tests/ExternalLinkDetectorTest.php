<?php

/*
 * This file is part of the florentingarnier/spam-protection package.
 *
 * (c) Florentin Garnier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FlorentinGarnier\SpamProtection\Tests;

use FlorentinGarnier\SpamProtection\ExternalLinkDetector;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ExternalLinkDetectorTest extends TestCase
{
    private const OWN_HOSTS = ['shop.example', 'www.boutique.example'];

    #[DataProvider('provideExternalLinks')]
    public function testItDetectsALinkToAnotherSite(string $text): void
    {
        self::assertTrue((new ExternalLinkDetector())->containsExternalLink($text, self::OWN_HOSTS));
    }

    public static function provideExternalLinks(): iterable
    {
        yield 'https link' => ['All the details are on this page: https://offers.example/fr/ind/shop'];
        yield 'http link' => ['See http://offers.example'];
        yield 'uppercase scheme' => ['See HTTPS://OFFERS.EXAMPLE/page'];
        yield 'link without scheme' => ['See www.offers.example/page'];
        yield 'link at the start of a line' => ["Hello\nwww.offers.example"];
        yield 'link in parentheses' => ['Our page (https://offers.example) has the details'];
        yield 'own host as a path' => ['https://offers.example/shop.example'];
        yield 'own host as a query' => ['https://offers.example?u=https://shop.example'];
        yield 'own host as a subdomain of another site' => ['https://shop.example.offers.example/page'];
        yield 'own host as user information' => ['https://shop.example@offers.example/page'];
        yield 'host ending like the own host' => ['https://myshop.example/page'];
        yield 'external link after an own link' => ['https://shop.example/a and https://offers.example/b'];
    }

    #[DataProvider('provideTextsWithoutExternalLink')]
    public function testItAcceptsATextWithoutLinkToAnotherSite(string $text): void
    {
        self::assertFalse((new ExternalLinkDetector())->containsExternalLink($text, self::OWN_HOSTS));
    }

    public static function provideTextsWithoutExternalLink(): iterable
    {
        yield 'empty' => [''];
        yield 'plain sentence' => ['Bonjour, je souhaiterais un devis pour une étagère en aluminium.'];
        yield 'e-mail address' => ['Write to me at john@offers.example or john@www.offers.example'];
        yield 'file name' => ['The drawing is in plan-v2.pdf, 1.5 m wide.'];
        yield 'scheme without host' => ['Does the page start with https:// ?'];
        yield 'own link' => ['Is https://shop.example/products/shelf-12 available?'];
        yield 'own link with www' => ['Is https://www.shop.example/products/shelf-12 available?'];
        yield 'own link without scheme' => ['Is www.shop.example/products/shelf-12 available?'];
        yield 'own link with a port' => ['https://shop.example:8443/products/shelf-12'];
        yield 'own link ending the sentence' => ['I mean the one on https://shop.example.'];
        yield 'own link in uppercase' => ['HTTPS://SHOP.EXAMPLE/PRODUCTS'];
        yield 'subdomain of the own host' => ['https://media.shop.example/photo.jpg'];
        yield 'own host declared with www' => ['https://boutique.example/produits'];
    }

    public function testEveryLinkIsExternalWithoutOwnHost(): void
    {
        self::assertTrue((new ExternalLinkDetector())->containsExternalLink('https://shop.example/products'));
    }
}
