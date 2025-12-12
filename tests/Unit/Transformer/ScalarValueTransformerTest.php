<?php

namespace Tito10047\PersistentStateBundle\Tests\Unit\Transformer;

use PHPUnit\Framework\TestCase;
use Tito10047\PersistentStateBundle\Storage\StorableEnvelope;
use Tito10047\PersistentStateBundle\Transformer\ScalarValueTransformer;

class ScalarValueTransformerTest extends TestCase
{
    private ScalarValueTransformer $transformer;

    protected function setUp(): void
    {
        $this->transformer = new ScalarValueTransformer();
    }

    public function testSupportsForScalarsAndNull(): void
    {
        $this->assertTrue($this->transformer->supports(0));
        $this->assertTrue($this->transformer->supports(123));
        $this->assertTrue($this->transformer->supports(1.5));
        $this->assertTrue($this->transformer->supports(''));
        $this->assertTrue($this->transformer->supports('hello'));
        $this->assertTrue($this->transformer->supports(true));
        $this->assertTrue($this->transformer->supports(false));
        $this->assertTrue($this->transformer->supports(null));
    }

    public function testSupportsForNonScalars(): void
    {
        $this->assertFalse($this->transformer->supports([]));
        $this->assertFalse($this->transformer->supports(['a' => 1]));
        $this->assertFalse($this->transformer->supports(new \stdClass()));
        $this->assertFalse($this->transformer->supports(function () { return 1; }));
    }

    public function testTransformIsIdentityForSupportedValues(): void
    {
        $inputs = [0, 123, 1.5, '', 'hello', true, false, null];
        foreach ($inputs as $in) {
            $this->assertEquals($this->getEnvelope($in), $this->transformer->transform($in));
        }
    }

    public function testSupportsReverseMatchesSupports(): void
    {
        $supported = [0, 123, 1.5, '', 'hello', true, false, null];
        foreach ($supported as $val) {
            $this->assertTrue($this->transformer->supportsReverse($this->getEnvelope($val)));
        }

        $unsupported = [[], ['a' => 1]];
        foreach ($unsupported as $val) {
            $this->assertFalse($this->transformer->supportsReverse($this->getEnvelope($val, 'array')));
        }
    }

    public function testReverseTransformIsIdentityForSupportedValues(): void
    {
        $inputs = [0, 123, 1.5, '', 'hello', true, false, null];
        foreach ($inputs as $in) {
            $this->assertEquals($in,
                $this->transformer->reverseTransform(
                    $this->transformer->transform($in)
                )
            );
        }
    }

    private function getEnvelope(mixed $data, string $className = 'scalar'): StorableEnvelope
    {
        return new StorableEnvelope($className, $data);
    }
}
