<?php

declare(strict_types=1);

namespace Rushing\TimelineSchema\Contracts;

use Rushing\TimelineSchema\Support\ValidationResult;

/**
 * The optional semantic-validation seam. The PHP-native default
 * ({@see \Rushing\TimelineSchema\Validation\NullOtioValidator}) only guarantees
 * serialization fidelity — it never claims an `.otio` is temporally coherent. A
 * host overrides this binding with a popcorn `RemoteInvocable` over the
 * `opentimelineio` Python library to get real schema/temporal validation; that
 * wiring (and its Python dependency) lives in the consumer, not this package.
 *
 * @param  array<string, mixed>  $otio
 */
interface OtioValidator
{
    /**
     * @param  array<string, mixed>  $otio
     */
    public function validate(array $otio): ValidationResult;
}
