<?php

namespace Curacel\LangFuse\Exceptions;

final class MissingPromptVariablesException extends \Exception
{
    public function __construct(
        protected array $missingVariables,
        protected array $providedVariables,
        protected string $promptName,
        protected string|array $promptContent,
        protected $message = '',
        protected $code = 0
    ) {
        if (empty($message)) {
            $message = $this->generateMessage();
        }

        parent::__construct($message, $code);
    }

    protected function generateMessage(): string
    {
        $missingCount = count($this->missingVariables);
        $missingList = implode(', ', $this->missingVariables);
        $providedList = empty($this->providedVariables) ? 'none' : implode(', ', array_keys($this->providedVariables));

        $message = "Missing required variables for prompt '{$this->promptName}': {$missingList}";
        $message .= "\n\n";
        $message .= "Variables provided: {$providedList}";

        return $message;
    }

    public function getMissingVariables(): array
    {
        return $this->missingVariables;
    }

    public function getProvidedVariables(): array
    {
        return $this->providedVariables;
    }

    public function getPromptName(): string
    {
        return $this->promptName;
    }

    public function getPromptContent(): string|array
    {
        return $this->promptContent;
    }
}
