<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

use PHPUnit\Framework\TestCase;
use Ray\Di\Injector;

class MediaQueryModuleTest extends TestCase
{
    public function testEnhancedParamConverterIsDefault(): void
    {
        $module = new MediaQueryBaseModule(
            Queries::fromClasses([]),
        );
        $injector = new Injector($module);

        $paramConverter = $injector->getInstance(ParamConverterInterface::class);

        $this->assertInstanceOf(ParamConverter::class, $paramConverter);
    }

    public function testOriginalParamConverterWithNamedBinding(): void
    {
        $module = new MediaQueryBaseModule(
            Queries::fromClasses([]),
        );
        $injector = new Injector($module);

        $paramConverter = $injector->getInstance(ParamConverterInterface::class, 'original');

        $this->assertInstanceOf(ParamConverter::class, $paramConverter);
    }

    public function testEnhancedParamConverterWithInputObjects(): void
    {
        $module = new MediaQueryBaseModule(
            Queries::fromClasses([]),
        );
        $injector = new Injector($module);

        /** @var ParamConverterInterface $paramConverter */
        $paramConverter = $injector->getInstance(ParamConverterInterface::class);

        // Test with Input object
        $userInput = new UserInput(
            givenName: 'John',
            familyName: 'Doe',
            email: 'john@example.com',
        );

        $values = ['user' => $userInput];
        $paramConverter($values);

        $expected = [
            'givenName' => 'John',
            'familyName' => 'Doe',
            'email' => 'john@example.com',
        ];

        $this->assertSame($expected, $values);
    }
}
