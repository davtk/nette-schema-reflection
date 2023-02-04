Original Nette schema: https://github.com/nette/schema

## Additional functionality:

### Instantiate object via Reflection

Let's imagine following class:

```php
class Foo
{
    public function __construct(
        public int $a,
        public int $b,
    ) {
    }
}
```

Casting to this class will result in ArgumentCountError because Schema is instantiating the class using new keyword.

I solved this with adding second argument to `castTo: castTo(string $type, bool $usingReflection = false): self` - default behavior is not changed, so it's not causing BC break.
