<?php

namespace Curacel\LangFuse\Resources;

use BackedEnum;
use Curacel\LangFuse\Concerns\Transportable;
use Curacel\LangFuse\DTO\PromptRenderer;
use Curacel\LangFuse\Exceptions\MissingPromptVariablesException;
use Curacel\LangFuse\Exceptions\NetworkErrorException;

final class Prompt
{
    use Transportable;

    /**
     * @throws NetworkErrorException|MissingPromptVariablesException
     */
    public function get(string|BackedEnum $name, array|\Closure $variables = [], array|string|null $fallback = null): string|array
    {
        $promptName = $name instanceof BackedEnum ? $name->value : $name;

        if (! $this->config->get('langfuse.enabled', true)) {
            return $fallback;
        }

        try {
            $prompt = $this->send('get', "/api/public/v2/prompts/{$promptName}")->json('prompt');
        } catch (NetworkErrorException $e) {
            if (empty($fallback)) {
                throw $e;
            }
        }

        return PromptRenderer::make(promptName: $promptName, promptContent: $prompt ?? $fallback)->render($variables);
    }
}
