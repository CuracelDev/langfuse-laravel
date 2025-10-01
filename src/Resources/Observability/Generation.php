<?php

declare(strict_types=1);

namespace Curacel\LangFuse\Resources\Observability;

use Curacel\LangFuse\Contracts\ObservationContract;
use Curacel\LangFuse\DTO\ModelParameters;

/**
 * Class Generation
 *
 * Represents an AI/LLM generation within a trace. Generations are specialized
 * observations that capture model interactions, including prompts, completions,
 * and detailed usage metrics.
 */
class Generation extends AbstractObservability
{
    protected string $type = 'generation';

    /* The name of the model used for the generation. */
    protected ?string $model = null;

    /* The parameters of the model used for the generation; */
    protected ?ModelParameters $modelParameters = null;

    /* The usage object supports the OpenAi structure with (promptTokens, completionTokens, totalTokens)
     and a more generic version (input, output, total, unit, inputCost, outputCost, totalCost)
     where unit can be of value "TOKENS", "CHARACTERS", "MILLISECONDS", "SECONDS", "IMAGES". */
    protected ?array $usageDetails = null;

    protected ?array $costDetails = null;

    /* The time at which the completion started (streaming).
      Set it to get latency analytics broken down into time
      until completion started and completion duration.
    */
    protected ?string $completionStartTime = null;

    /* The level of the generation. Can be DEBUG, DEFAULT, WARNING or ERROR.
      Used for sorting/filtering of traces with elevated error levels
      and for highlighting in the UI.
    */
    protected ?string $level = 'DEFAULT';

    /* The status message of the generation. Additional field for context of the event.
      E.g. the error message of an error event.
    */
    protected ?string $statusMessage = null;

    public function __construct(
        string $traceId,
        string $name,
        string $model,
        ?array $modelParameters = null,
        ?array $metadata = null,
        mixed $input = null
    ) {
        parent::__construct($traceId, $name, $metadata, $input);

        $this->model = $model;
        $this->modelParameters = ModelParameters::fromArray($modelParameters);
    }

    /**
     * Ends the generation and records completion details
     *
     * @param  array|null  $data  Completion data including:
     *                            - output: The model's response
     *                            - usage_details: Token usage statistics
     *                            - cost_details: Cost information for the generation
     */
    public function end(?array $data = null): static
    {
        parent::end($data);

        return $this;
    }

    /**
     * Update generation with new data, including generation-specific fields
     *
     * @param  array  $data  Data to update, supports generation-specific fields like usage_details and cost_details
     */
    public function update(array $data): ObservationContract
    {
        parent::update($data);

        if (isset($data['usage_details'])) {
            $this->usageDetails = $data['usage_details'];
        }
        if (isset($data['cost_details'])) {
            $this->costDetails = $data['cost_details'];
        }

        return $this;
    }

    /**
     * Converts the generation to an array format for API submission
     */
    public function toArray(): array
    {
        $modelParametersValue = null;

        if ($this->modelParameters !== null && ! $this->modelParameters->isEmpty()) {
            $modelParametersValue = $this->modelParameters->toObject();
        }

        return array_merge(parent::toArray(), [
            'model' => $this->model,
            'modelParameters' => $modelParametersValue,
            'usageDetails' => $this->usageDetails,
            'costDetails' => $this->costDetails,
            'completionStartTime' => $this->completionStartTime,
            'level' => $this->level,
            'statusMessage' => $this->statusMessage,
        ]);
    }

    public function withUsageDetails(array $usageDetails): static
    {
        $this->usageDetails = $usageDetails;

        return $this;
    }

    /**
     * Sets cost details for this generation observation
     *
     * @throws \InvalidArgumentException
     */
    public function withCostDetails(array $costDetails): static
    {
        foreach ($costDetails as $key => $value) {
            if (! is_string($key) || ! is_numeric($value)) {
                throw new \InvalidArgumentException('Invalid cost details format');
            }
        }

        $this->costDetails = $costDetails;

        return $this;
    }

    public function startCompletion(): self
    {
        $this->completionStartTime = (new \DateTime)->format('Y-m-d\TH:i:s.vP');

        return $this;
    }
}
