<?php

namespace Tito10047\PersistentPreferenceBundle\Tests\Unit\Selection\Normalizer;

use PHPUnit\Framework\TestCase;
use Tito10047\PersistentPreferenceBundle\Selection\Normalizer\ScalarNormalizer;

class ScalarNormalizerTest extends TestCase
{
    public function testSupports(): void
    {
        $normalizer = new ScalarNormalizer();

        $this->assertTrue($normalizer->supports(1));
        $this->assertTrue($normalizer->supports('id'));
        $this->assertTrue($normalizer->supports(1.5));
        $this->assertTrue($normalizer->supports(false));

        $this->assertFalse($normalizer->supports(null));
        $this->assertFalse($normalizer->supports([]));
        $this->assertFalse($normalizer->supports(new \stdClass()));
    }

    public function testNormalizeReturnsIntOrString(): void
    {
        $normalizer = new ScalarNormalizer();

        $this->assertSame(123, $normalizer->normalize(123, 'ignored'));
        $this->assertSame('abc', $normalizer->normalize('abc', 'ignored'));
    }

    public function testNormalizeThrowsForFloatOrBool(): void
    {
        $normalizer = new ScalarNormalizer();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Item is not a valid scalar type');
        $normalizer->normalize(12.34, 'ignored');
    }

    public function testNormalizeThrowsForBool(): void
    {
        $normalizer = new ScalarNormalizer();

        $this->expectException(\RuntimeException::class);
        $normalizer->normalize(true, 'ignored');
    }
}
