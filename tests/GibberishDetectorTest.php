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

use FlorentinGarnier\SpamProtection\GibberishDetector;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class GibberishDetectorTest extends TestCase
{
    #[DataProvider('provideGibberish')]
    public function testItDetectsRandomCharacters(string $text): void
    {
        self::assertTrue((new GibberishDetector())->isGibberish($text));
    }

    public static function provideGibberish(): iterable
    {
        yield 'random mixed case' => ['dTqLzVbKxWmPfRjN'];
        yield 'random lowercase' => ['kjsdhfkjsdhf'];
        yield 'keyboard mash' => ['asdfghjkl qsdfghjklm'];
        yield 'several random words' => ['XgTbRkLm aPqWnZcV hLkRtWq'];
    }

    #[DataProvider('provideReadableText')]
    public function testItAcceptsReadableText(string $text): void
    {
        self::assertFalse((new GibberishDetector())->isGibberish($text));
    }

    public static function provideReadableText(): iterable
    {
        yield 'empty' => [''];
        yield 'french sentence' => ['Bonjour, je souhaiterais un devis pour une étagère en aluminium.'];
        yield 'german sentence' => ['Guten Tag, ich möchte gerne ein Angebot für ein Regal.'];
        yield 'short word' => ['Merci'];
        yield 'product reference' => ['Commande RX-450B, référence ZK12'];
        yield 'brand names' => ['Envoyé depuis mon iPhone via LinkedIn et YouTube'];
        yield 'capital letters' => ['URGENT : LIVRAISON INCOMPLÈTE'];
        yield 'one odd word in a sentence' => ['Bonjour, la référence XgTbRkLm de votre catalogue est-elle disponible en blanc ?'];
        yield 'non latin script' => ['Здравствуйте, сколько стоит доставка'];
    }
}
