<?php

declare(strict_types=1);

use Nette\Schema\Expect;
use Nette\Schema\Processor;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/fixtures/CastingTestClass.php';



test('', function () {
	$schema = Expect::int()->castTo('string');

	Assert::same('10', (new Processor)->process($schema, 10));
});


test('', function () {
	$schema = Expect::string()->castTo('array');

	Assert::same(['foo'], (new Processor)->process($schema, 'foo'));
});


test('', function () {
	$schema = Expect::array()->castTo('stdClass');

	Assert::equal((object) ['a' => 1, 'b' => 2], (new Processor)->process($schema, ['a' => 1, 'b' => 2]));
});

test('cast to class with constructor property promotion', function () {
    $schema = Expect::structure([
        'hello' =>  Expect::string(),
        'world' => Expect::string(),
    ])->castTo(\Schema\fixtures\CastingTestClass::class, true);

    $output = (new Processor)->process($schema, ['hello' => 'ahoj', 'world' => 'svete']);
    assert($output instanceof \Schema\fixtures\CastingTestClass);

    Assert::same('ahoj', $output->hello());
    Assert::same('svete', $output->world());
});

test('failed cast to class with constructor property promotion', function () {
    Assert::exception(function () {
        $schema = Expect::structure([
            'goodbye' =>  Expect::string(),
        ])->castTo(\Schema\fixtures\CastingTestClass::class, true);

        (new Processor)->process($schema, ['goodbye' => 'world']);
    }, \Nette\Schema\CastingException::class);
});