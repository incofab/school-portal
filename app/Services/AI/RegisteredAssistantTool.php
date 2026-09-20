<?php

namespace App\Services\AI;

use Closure;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Ai\Contracts\CanActAsTool;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

class RegisteredAssistantTool implements Tool, CanActAsTool
{
  public function __construct(
    private readonly string $toolName,
    private readonly string $toolDescription,
    private readonly array $inputSchema,
    private readonly Closure $executor
  ) {
  }

  public function name(): string
  {
    return $this->toolName;
  }

  public function description(): string
  {
    return $this->toolDescription;
  }

  public function schema(JsonSchema $schema): array
  {
    $properties = $this->inputSchema['properties'] ?? [];
    $required = $this->inputSchema['required'] ?? [];

    return collect($properties)
      ->mapWithKeys(function (array $definition, string $name) use (
        $schema,
        $required
      ): array {
        $type = match ($definition['type'] ?? 'string') {
          'integer' => $schema->integer(),
          'number' => $schema->number(),
          'boolean' => $schema->boolean(),
          'array' => $schema->array(),
          'object' => $schema->object(),
          default => $schema->string()
        };

        if (isset($definition['description'])) {
          $type->description($definition['description']);
        }

        if (isset($definition['enum'])) {
          $type->enum($definition['enum']);
        }

        if (in_array($name, $required, true)) {
          $type->required();
        }

        return [$name => $type];
      })
      ->all();
  }

  public function handle(Request $request): string
  {
    return ($this->executor)($request->all());
  }
}
