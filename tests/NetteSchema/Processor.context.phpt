<?php

declare(strict_types=1);

use Davtk\NetteSchema\Context;
use Davtk\NetteSchema\Expect;
use Davtk\NetteSchema\Processor;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


test('', function () {
	$schema = Expect::structure([
		'r' => Expect::string()->required(),
	]);

	$processor = new Processor;
	$processor->onNewContext[] = function (Context $context) {
		$context->path = ['first'];
	};

	$e = checkValidationErrors(function () use ($schema, $processor) {
		$processor->process($schema, []);
	}, ["The mandatory item 'first\u{a0}›\u{a0}r' is missing."]);

	Assert::equal(
		[
			new Davtk\NetteSchema\Message(
				'The mandatory item %path% is missing.',
				Davtk\NetteSchema\Message::MISSING_ITEM,
				['first', 'r'],
				['isKey' => false]
			),
		],
		$e->getMessageObjects()
	);
});
