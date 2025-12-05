<?php

namespace Tito10047\PersistentPreferenceBundle\Tests\Unit\Selection\Normalizer;

use PHPUnit\Framework\TestCase;
use Tito10047\PersistentPreferenceBundle\Selection\Normalizer\ObjectNormalizer;
use Tito10047\PersistentPreferenceBundle\Tests\App\AssetMapper\Src\Entity\RecordInteger;

class ObjectNormalizerTest extends TestCase
{
    public function testSupports(): void
    {
        $normalizer = new ObjectNormalizer();

        $this->assertTrue($normalizer->supports(new \stdClass()));
        $this->assertTrue($normalizer->supports(new RecordInteger()));

        $this->assertFalse($normalizer->supports(1));
        $this->assertFalse($normalizer->supports('a'));
        $this->assertFalse($normalizer->supports(1.23));
        $this->assertFalse($normalizer->supports(true));
        $this->assertFalse($normalizer->supports(null));
        $this->assertFalse($normalizer->supports([]));
    }

    public function testNormalizeReadsScalarViaGetter(): void
    {
        $normalizer = new ObjectNormalizer();

        $entity = (new RecordInteger())->setId(123)->setName('Foo');

        $this->assertSame(123, $normalizer->normalize($entity, 'id'));
        $this->assertSame('Foo', $normalizer->normalize($entity, 'name'));
    }

    public function testNormalizeReadsStringFromStringableProperty(): void
    {
        $normalizer = new ObjectNormalizer();

        // object with public property that is stringable
        $obj = new class {
            public $value;
            public function __construct()
            {
                $this->value = new class {
                    public function __toString(): string { return 'STRINGABLE'; }
                };
            }
        };

        $this->assertSame('STRINGABLE', $normalizer->normalize($obj, 'value'));
    }

    public function testNormalizeThrowsWhenPathNotReadable(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot read identifier');

        $normalizer = new ObjectNormalizer();
        $obj = new \stdClass();
        // property does not exist
        $normalizer->normalize($obj, 'unknown');
    }

    public function testNormalizeThrowsWhenExtractedValueIsNotScalarOrStringable(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Extracted value is not a scalar');

        $normalizer = new ObjectNormalizer();

        $obj = new class {
            public $value;
            public function __construct() { $this->value = new \stdClass(); }
        };

        $normalizer->normalize($obj, 'value');
    }
}
