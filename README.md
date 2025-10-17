# Langfuse Laravel

A Laravel integration for Langfuse, providing request/response tracing, metrics, and observability for AI features and application workflows.

This package helps you:

- Capture traces and spans across your Laravel app (HTTP, jobs, commands).
- Log inputs/outputs of AI calls with metadata for evaluation.
- Send telemetry to Langfuse for monitoring, analysis, and debugging.
- Prompt management

## 🧩 Requirements

- **PHP**: 8.1+
- **Laravel**: 10.x, 11.x, or 12.x
- **Composer**

## ⚙️ Configuration

### 1. Publish the Config File

```bash
php artisan vendor:publish --provider="Curacel\Langfuse\LangfuseServiceProvider" --tag="langfuse-config"
```

This will create `config/langfuse.php`.

### 2. Set Environment Variables in `.env`

```env
LANGFUSE_PUBLIC_KEY=your_public_key
LANGFUSE_SECRET_KEY=your_secret_key
LANGFUSE_HOST=https://cloud.langfuse.com   # or your self-hosted URL
LANGFUSE_ENABLED=true
```

### 3. Key Options (in `config/langfuse.php`)

| Option | Description |
|--------|-------------|
| `enabled` | Master switch to enable/disable instrumentation |
| `host` | Langfuse API host |
| `public_key` / `secret_key` | Your Langfuse credentials |

## 🚀 Usage

### Quick Start: Create a Trace and Observation

#### Observability Data Model

Tracing in Langfuse is a method for logging and analyzing the execution of your LLM applications. It follows an OpenTelemetry-inspired model.

#### Concepts

**Traces**

- Represent a single request or operation.
- Contain overall input, output, and metadata (user, session, tags, etc.).

**Observations**

Each trace can contain multiple observations to log individual execution steps. Usually, a trace corresponds to a single API call of your application.

Observations can be of different types:

- **Events** → Track discrete events in a trace.
- **Spans** → Represent durations of work units.
- **Generations** → Specialized spans for AI model generations, containing prompt/completion details.

Observations can be nested.

### 🧠 Example

```php
use Curacel\Langfuse\Facades\LangFuse;
use Curacel\Langfuse\DTO\TraceConfig;
use Illuminate\Support\Facades\App;

// Initialize tracing
$langfuse = LangFuse::tracing();

// Create a trace
$trace = $langfuse->trace(
    TraceConfig::create("validate-vehicle-image")
);

// Create spans and generations
$trace
    ->span(
        name: "detect-vehicle-part-interaction",
        metadata: [
            'route' => App::bound('request') ? request()?->fullUrl() : null
        ]
    )
    ->generation(
        name: "detect-vehicle-part-llm-call",
        model: "gpt-4o",
        modelParameters: ['temperature' => 0.5],
        input: $messages
    );

// Perform your LLM call
// e.g., $result = OpenAI::call($messages);

// End the trace
$trace->end([
    'input' => $messages,
    'output' => $result
]);

// Sync traces to Langfuse
$langfuse->syncTraces();
```

# ✨ Langfuse Prompt Features

## 🧩 Features

- Fetch prompts from Langfuse by name
- Compile prompts with variables (e.g. `{{ name }}`)
- Detailed error messages for missing variables

## 🚀 Usage

```php
use Curacel\Langfuse\Facades\Langfuse;

$langfuse = Langfuse::prompt();

// Get a raw prompt
echo $langfuse->getPrompt('welcome')->raw();

// Compile a prompt with variables
echo $langfuse->getPrompt('greeting')->compile(['name' => 'Alice']);
```

## 📝 Get a Prompt

```php
$prompt = $langfuse->getPrompt('welcome')->raw();
// "Hello {{name}}"
```

## 🧠 Compile a Prompt

```php
$compiled = $langfuse->getPrompt('greeting')->compile(['name' => 'Alice']);
// "Hello Alice!"
```

## ⚠️ Handle Missing Variables

If you compile a prompt without providing all required variables, a `MissingPromptVariablesException` will be thrown.

```php
try {
    $langfuse->getPrompt('profile')->compile(['name' => 'John']);
} catch (Curacel\Langfuse\Exceptions\MissingPromptVariablesException $e) {
    echo $e->getMessage();
}
```

## 🧭 Versioning and Stability

⚠️ **This is an early release.**

- APIs may change before 1.0.
- Pin a minor version in your `composer.json`.
- Review changelogs before upgrading.

## 🤝 Contributing

1. Fork the repository and create a feature branch.
2. Run tests and static analysis:
   ```bash
   composer ci
   ```
3. Submit a pull request with:
   - A clear description
   - Example usage