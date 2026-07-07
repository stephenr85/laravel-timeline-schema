<?php

namespace Rushing\TimelineSchema\Schema;

use ReflectionClass;
use ReflectionProperty;
use Rushing\LaravelDataSchemas\Generators\JsonSchemaGenerator;
use Rushing\TimelineSchema\Attributes\OtioField;
use Rushing\TimelineSchema\Attributes\OtioSchema;
use Rushing\TimelineSchema\Objects\OtioData;

/**
 * A laravel-data-schemas generator that understands the OTIO object model: it
 * bridges #[OtioSchema] (class description) and #[OtioField] (property
 * description / example / required) into the emitted JSON Schema, so an OTIO
 * object's authoring metadata drives LLM structured-output schemas.
 *
 * Register it ahead of the default generator in `config/data-schemas.php`; its
 * `canGenerate()` only claims OtioData subclasses, leaving plain Data to the
 * default. `forLlmStrict()` (inherited) strips every `x-*` keyword before the
 * model sees the schema.
 *
 * Polymorphic structural fields (children, media_reference) are intentionally
 * managed outside the Data payload and so are absent from the per-object schema —
 * the schema describes a node's own scalar/value fields, as block-schema's does.
 *
 * @see JsonSchemaGenerator
 */
class OtioJsonSchemaGenerator extends JsonSchemaGenerator
{
    public function canGenerate(ReflectionClass $class): bool
    {
        return $class->isSubclassOf(OtioData::class);
    }

    protected function getClassDescription(ReflectionClass $class): ?string
    {
        $attrs = $class->getAttributes(OtioSchema::class);

        if (! empty($attrs) && ($description = $attrs[0]->newInstance()->description) !== null) {
            return $description;
        }

        return parent::getClassDescription($class);
    }

    protected function generatePropertySchema(ReflectionProperty $property): array
    {
        $schema = parent::generatePropertySchema($property);

        if (! $field = $this->otioField($property)) {
            return $schema;
        }

        if (! isset($schema['description']) && $field->description !== null) {
            $schema['description'] = $field->description;
        }

        if ($field->example !== null) {
            $schema['examples'] = [$field->example];
        }

        return $schema;
    }

    protected function isRequired(ReflectionProperty $property): bool
    {
        if (! $field = $this->otioField($property)) {
            return parent::isRequired($property);
        }

        if ($property->hasDefaultValue()) {
            return false;
        }

        return $field->required;
    }

    private function otioField(ReflectionProperty $property): ?OtioField
    {
        $attrs = $property->getAttributes(OtioField::class);

        return empty($attrs) ? null : $attrs[0]->newInstance();
    }
}
