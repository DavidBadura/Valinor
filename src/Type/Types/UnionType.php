<?php

declare(strict_types=1);

namespace CuyZ\Valinor\Type\Types;

use CuyZ\Valinor\Type\CombiningType;
use CuyZ\Valinor\Type\CompositeType;
use CuyZ\Valinor\Type\Type;
use CuyZ\Valinor\Type\Types\Exception\ForbiddenMixedType;

use function implode;

/** @api */
final class UnionType implements CombiningType
{
    /** @var Type[] */
    private array $types = [];

    private string $signature;

    public function __construct(Type ...$types)
    {
        $this->signature = implode('|', $types);

        foreach ($types as $type) {
            if ($type instanceof self) {
                foreach ($type->types as $subType) {
                    $this->types[] = $subType;
                }

                continue;
            }

            if ($type instanceof MixedType) {
                throw new ForbiddenMixedType();
            }

            $this->types[] = $type;
        }
    }

    public function accepts($value): bool
    {
        foreach ($this->types as $type) {
            if ($type->accepts($value)) {
                return true;
            }
        }

        return false;
    }

    public function matches(Type $other): bool
    {
        if ($other instanceof self) {
            foreach ($this->types as $type) {
                if (! $other->isMatchedBy($type)) {
                    return false;
                }
            }

            return true;
        }

        foreach ($this->types as $type) {
            if ($type->matches($other)) {
                return true;
            }
        }

        return false;
    }

    public function isMatchedBy(Type $other): bool
    {
        foreach ($this->types as $type) {
            if ($other->matches($type)) {
                return true;
            }
        }

        return false;
    }

    public function traverse(): iterable
    {
        foreach ($this->types as $type) {
            yield $type;

            if ($type instanceof CompositeType) {
                yield from $type->traverse();
            }
        }
    }

    public function types(): array
    {
        return $this->types;
    }

    public function __toString(): string
    {
        return $this->signature;
    }
}
