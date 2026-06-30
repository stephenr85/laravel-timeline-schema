<?php

declare(strict_types=1);

namespace Rushing\TimelineSchema\Support;

use Rushing\TimelineSchema\Contracts\OtioValidator;
use Spatie\LaravelData\Data;

/**
 * The outcome of an {@see OtioValidator} run.
 * `semantic` flags whether real temporal/schema validation actually ran (the
 * PHP-native default leaves it false — it only confirms the bytes parse).
 */
final class ValidationResult extends Data
{
    /**
     * @param  list<string>  $errors
     */
    public function __construct(
        public readonly bool $valid,
        public readonly bool $semantic = false,
        public readonly array $errors = [],
    ) {}

    public static function ok(bool $semantic = false): self
    {
        return new self(valid: true, semantic: $semantic);
    }

    /**
     * @param  list<string>  $errors
     */
    public static function failed(array $errors, bool $semantic = true): self
    {
        return new self(valid: false, semantic: $semantic, errors: $errors);
    }
}
