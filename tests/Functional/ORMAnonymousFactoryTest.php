<?php

namespace Zenstruck\Foundry\Tests\Functional;

use Zenstruck\Foundry\Tests\Fixtures\Entity\Category;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ORMAnonymousFactoryTest extends AnonymousFactoryTest
{
    protected function setUp(): void
    {
        if (!\getenv('USE_ORM')) {
            self::markTestSkipped('doctrine/orm not enabled.');
        }
    }

    protected function categoryClass(): string
    {
        return Category::class;
    }
}
