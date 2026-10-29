<?php

namespace App\Domain\Category;

use App\Domain\Shared\Color;

/**
 * A kind of work, which decides whether its time is billed (F-04, RN-12).
 */
final readonly class Category
{
    /** Longest name: it has to fit in the entry form on the phone. */
    public const int NAME_LIMIT = 40;

    public function __construct(
        public ?int $id,
        public string $name,
        public Color $color,
        public bool $billable = true,
        public bool $active = true,
        public int $sortOrder = 0,
    ) {}

    public function with(?string $name = null, ?Color $color = null, ?bool $billable = null, ?bool $active = null, ?int $sortOrder = null): self
    {
        return new self($this->id, $name ?? $this->name, $color ?? $this->color, $billable ?? $this->billable, $active ?? $this->active, $sortOrder ?? $this->sortOrder);
    }

    /**
     * @return array{name: string, color: string, billable: bool, active: bool}
     */
    public function toArray(): array
    {
        return ['name' => $this->name, 'color' => $this->color->value, 'billable' => $this->billable, 'active' => $this->active];
    }
}
