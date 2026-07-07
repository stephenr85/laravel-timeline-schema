<?php

namespace Rushing\TimelineSchema\Support;

use LogicException;

/**
 * Read/write a host application's extension blob inside an OTIO `metadata` dict,
 * under a single configurable namespace key (`timeline-schema.metadata_namespace`).
 *
 * OTIO metadata is shared, free-form, and namespaced by convention — every tool
 * stashes its data under its own key (`fcp`, `resolve`, …). This package never
 * hardcodes an application name: the host configures the key, and this helper is
 * the one place that reads it. With no namespace configured the helper is inert
 * (reads return empty; writes throw) — `metadata` still round-trips verbatim.
 */
class ExtensionMetadata
{
    public static function namespace(): ?string
    {
        $ns = function_exists('config') ? config('timeline-schema.metadata_namespace') : null;

        return is_string($ns) && $ns !== '' ? $ns : null;
    }

    /**
     * @param  array<string, mixed>  $metadata
     * @return array<string, mixed>
     */
    public static function get(array $metadata): array
    {
        $ns = self::namespace();

        if ($ns === null) {
            return [];
        }

        $value = $metadata[$ns] ?? [];

        return is_array($value) ? $value : [];
    }

    /**
     * Return a copy of $metadata with the host's extension blob set under the
     * configured namespace. Throws when no namespace is configured — writing app
     * truth into an unnamespaced dict is exactly the leakage this guards against.
     *
     * @param  array<string, mixed>  $metadata
     * @param  array<string, mixed>  $extension
     * @return array<string, mixed>
     */
    public static function put(array $metadata, array $extension): array
    {
        $ns = self::namespace();

        if ($ns === null) {
            throw new LogicException(
                'No timeline-schema.metadata_namespace configured; set it to your app slug before writing extension metadata.'
            );
        }

        $metadata[$ns] = $extension;

        return $metadata;
    }
}
