<?php

namespace Schema\fixtures;

class CastingTestClass {
    public function __construct(
        private string $hello,
        private string $world,
    ) {
    }

    public function hello(): string
    {
        return $this->hello;
    }

    public function world(): string
    {
        return $this->world;
    }
};