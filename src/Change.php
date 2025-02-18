<?php

namespace Zerotoprod\DocgenVisitor;

/**
 * @link https://github.com/zero-to-prod/docblock-annotator
 */
class Change
{

    /**
     * @link https://github.com/zero-to-prod/docblock-annotator
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

    /**
     * @link https://github.com/zero-to-prod/docblock-annotator
     */
    public const start = 'start';
    /**
     * @link https://github.com/zero-to-prod/docblock-annotator
     */
    public int $start;
    /**
     * @link https://github.com/zero-to-prod/docblock-annotator
     */
    public const end = 'end';
    /**
     * @link https://github.com/zero-to-prod/docblock-annotator
     */
    public int $end;
    /**
     * @link https://github.com/zero-to-prod/docblock-annotator
     */
    public const text = 'text';
    /**
     * @link https://github.com/zero-to-prod/docblock-annotator
     */
    public string $text;
}