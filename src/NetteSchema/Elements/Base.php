<?php

/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Davtk\NetteSchema\Elements;

use Nette;
use \Davtk\NetteSchema\Context;
use ReflectionClass;
use ReflectionException;


/**
 * @internal
 */
trait Base
{
	/** @var bool */
	private $required = false;

	/** @var mixed */
	private $default;

	/** @var callable|null */
	private $before;

	/** @var array[] */
	private $asserts = [];

	/** @var string|null */
	public $castTo;

    private bool $castUsingReflection = false;

	/** @var string|null */
	private $deprecated;


	public function default($value): self
	{
		$this->default = $value;
		return $this;
	}


	public function required(bool $state = true): self
	{
		$this->required = $state;
		return $this;
	}


	public function before(callable $handler): self
	{
		$this->before = $handler;
		return $this;
	}


	public function castTo(string $type, bool $castUsingReflection = false): self
	{
		$this->castTo = $type;
        $this->castUsingReflection = $castUsingReflection;
		return $this;
	}


	public function assert(callable $handler, ?string $description = null): self
	{
		$this->asserts[] = [$handler, $description];
		return $this;
	}


	/** Marks as deprecated */
	public function deprecated(string $message = 'The item %path% is deprecated.'): self
	{
		$this->deprecated = $message;
		return $this;
	}


	public function completeDefault(Context $context)
	{
		if ($this->required) {
			$context->addError(
				'The mandatory item %path% is missing.',
				\Davtk\NetteSchema\Message::MISSING_ITEM
			);
			return null;
		}

		return $this->default;
	}


	public function doNormalize($value, Context $context)
	{
		if ($this->before) {
			$value = ($this->before)($value);
		}

		return $value;
	}


	private function doDeprecation(Context $context): void
	{
		if ($this->deprecated !== null) {
			$context->addWarning(
				$this->deprecated,
				\Davtk\NetteSchema\Message::DEPRECATED
			);
		}
	}


	private function doValidate($value, string $expected, Context $context): bool
	{
		if (!Nette\Utils\Validators::is($value, $expected)) {
			$expected = str_replace(['|', ':'], [' or ', ' in range '], $expected);
			$context->addError(
				'The %label% %path% expects to be %expected%, %value% given.',
				\Davtk\NetteSchema\Message::TYPE_MISMATCH,
				['value' => $value, 'expected' => $expected]
			);
			return false;
		}

		return true;
	}


	private function doValidateRange($value, array $range, Context $context, string $types = ''): bool
	{
		if (is_array($value) || is_string($value)) {
			[$length, $label] = is_array($value)
				? [count($value), 'items']
				: (in_array('unicode', explode('|', $types), true)
					? [Nette\Utils\Strings::length($value), 'characters']
					: [strlen($value), 'bytes']);

			if (!self::isInRange($length, $range)) {
				$context->addError(
					"The length of %label% %path% expects to be in range %expected%, %length% $label given.",
					\Davtk\NetteSchema\Message::LENGTH_OUT_OF_RANGE,
					['value' => $value, 'length' => $length, 'expected' => implode('..', $range)]
				);
				return false;
			}
		} elseif ((is_int($value) || is_float($value)) && !self::isInRange($value, $range)) {
			$context->addError(
				'The %label% %path% expects to be in range %expected%, %value% given.',
				\Davtk\NetteSchema\Message::VALUE_OUT_OF_RANGE,
				['value' => $value, 'expected' => implode('..', $range)]
			);
			return false;
		}

		return true;
	}


	private function isInRange($value, array $range): bool
	{
		return ($range[0] === null || $value >= $range[0])
			&& ($range[1] === null || $value <= $range[1]);
	}


	private function doFinalize($value, Context $context)
	{
		if ($this->castTo) {
			if (Nette\Utils\Reflection::isBuiltinType($this->castTo)) {
				settype($value, $this->castTo);
			} elseif ($this->castUsingReflection) {
                try {
                    $reflection = new ReflectionClass($this->castTo);
                    $instance = $reflection->newInstanceWithoutConstructor();

                    foreach ($value as $k => $v) {
                        $reflection->getProperty($k)->setValue($instance, $v);
                    }

                    $value = $instance;
                } catch (ReflectionException $e) {
                    throw new \Davtk\NetteSchema\CastingException("Failed casting to type {$this->castTo}", previous: $e);
                }
            } else {
				$object = new $this->castTo;
				foreach ($value as $k => $v) {
					$object->$k = $v;
				}

				$value = $object;
			}
		}

		foreach ($this->asserts as $i => [$handler, $description]) {
			if (!$handler($value)) {
				$expected = $description ?: (is_string($handler) ? "$handler()" : "#$i");
				$context->addError(
					'Failed assertion ' . ($description ? "'%assertion%'" : '%assertion%') . ' for %label% %path% with value %value%.',
					\Davtk\NetteSchema\Message::FAILED_ASSERTION,
					['value' => $value, 'assertion' => $expected]
				);
				return;
			}
		}

		return $value;
	}
}
