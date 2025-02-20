<?php

namespace Zerotoprod\DocgenVisitor;

/**
 * Used for representing changes
 *
 * @internal
 * @link https://github.com/zero-to-prod/docgen-visitor
 */
class Change
{
    /**
     * @link https://github.com/zero-to-prod/docgen-visitor
     */
    public const start = 'start';
    /**
     * @link https://github.com/zero-to-prod/docgen-visitor
     */
    public int $start;
    /**
     * @link https://github.com/zero-to-prod/docgen-visitor
     */
    public const end = 'end';
    /**
     * @link https://github.com/zero-to-prod/docgen-visitor
     */
    public int $end;
    /**
     * @link https://github.com/zero-to-prod/docgen-visitor
     */
    public const text = 'text';
    /**
     * @link https://github.com/zero-to-prod/docgen-visitor
     */
    public string $text;

    /**
     * @link https://github.com/zero-to-prod/docgen-visitor
     */
    public static function from(array $data): self
    {
        $self = new self;
        foreach ($data as $key => $value) {
            if (property_exists(self::class, $key)) {
                $self->$key = $value;
            }
        }

        return $self;
    }
}