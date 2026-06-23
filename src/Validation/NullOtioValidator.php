<?php

declare(strict_types=1);

namespace Rushing\TimelineSchema\Validation;

use Rushing\TimelineSchema\Contracts\OtioValidator;
use Rushing\TimelineSchema\Support\ValidationResult;

/**
 * The default, PHP-native validator: it asserts nothing beyond "this parsed".
 * `semantic` stays false so callers know real OTIO validation never ran. Swap in
 * a Python-sidecar-backed validator (via popcorn) when you need the real thing.
 */
final class NullOtioValidator implements OtioValidator
{
    public function validate(array $otio): ValidationResult
    {
        return ValidationResult::ok(semantic: false);
    }
}
