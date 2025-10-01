<?php

namespace Curacel\LangFuse\DTO;

use Curacel\LangFuse\Exceptions\MissingPromptVariablesException;
use Illuminate\Support\Collection;

class PromptRenderer implements \Stringable
{
    public function __construct(public string $promptName = '', public string|array $promptContent = '') {}

    public static function make(string $promptName, string|array $promptContent): static
    {
        return new static($promptName, $promptContent);
    }

    public function raw(): string|array
    {
        return is_array($this->promptContent) ? json_encode($this->promptContent) : $this->promptContent;
    }

    public function getPrompt(): string
    {
        return $this->promptName;
    }

    /**
     * Render the prompt with provided variables
     *
     * @throws MissingPromptVariablesException
     */
    public function render(array|\Closure|Collection $variables): string|array
    {
        return $this->processPromptVariables(
            prompt: $this->promptContent,
            promptName: $this->promptName,
            variables: $variables instanceof Collection ? $variables->toArray() : value($variables)
        );
    }

    /**
     * Process prompt variables and replace them in the template
     *
     * @throws MissingPromptVariablesException
     */
    protected function processPromptVariables(string|array $prompt, string $promptName = '', array $variables = []): string|array
    {
        $this->validatePromptVariables($prompt, $promptName, $variables);

        $patterns = [];
        $replacements = [];

        foreach ($variables as $key => $value) {
            $patterns[] = '/\{\{\s*'.preg_quote($key, '/').'\s*\}\}/';

            if (is_array($value) || is_object($value)) {
                $replacements[] = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            } else {
                $replacements[] = (string) $value;
            }
        }

        $replaceInText = static function (string $text) use ($patterns, $replacements): string {
            if (! str_contains($text, '{{')) {
                return $text;
            }

            $result = preg_replace($patterns, $replacements, $text);
            if ($result === null) {
                throw new \RuntimeException('Regex replacement failed: '.preg_last_error());
            }

            return $result;
        };

        if (is_string($prompt)) {
            return $replaceInText($prompt);
        }

        foreach ($prompt as &$message) {
            if (isset($message['content']) && is_string($message['content'])) {
                $message['content'] = $replaceInText($message['content']);
            }
        }

        unset($message);

        return $prompt;
    }

    /**
     * Validate that all required variables in the prompt are provided.
     *
     * This scans the prompt for placeholders in the format {{ variable }},
     * trims and deduplicates them, and checks if all are present in the
     * provided $variables array. If any are missing, a MissingPromptVariablesException is thrown.
     *
     * @throws MissingPromptVariablesException
     */
    protected function validatePromptVariables(
        string|array $prompt,
        string $promptName = '',
        array $variables = [],
    ): void {
        $contents = is_string($prompt) ? [$prompt] : array_map(fn ($msg) => $msg['content'] ?? '', $prompt);

        $allRequiredVariables = [];
        foreach ($contents as $content) {
            if (preg_match_all('/\{\{([^}]+)\}\}/', $content, $matches)) {
                foreach ($matches[1] as $var) {
                    $allRequiredVariables[] = trim($var);
                }
            }
        }

        $allRequiredVariables = array_unique($allRequiredVariables);

        if (empty($allRequiredVariables)) {
            return;
        }

        $missingVariables = array_diff($allRequiredVariables, array_keys($variables));

        if (! empty($missingVariables)) {
            throw new MissingPromptVariablesException(
                $missingVariables,
                $variables,
                $promptName,
                $prompt
            );
        }
    }

    public function __toString(): string
    {
        return $this->raw();
    }
}
