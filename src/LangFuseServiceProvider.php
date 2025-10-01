<?php

namespace Curacel\LangFuse;

use Curacel\LangFuse\Contracts\LangFuseClientContract;
use Curacel\LangFuse\Exceptions\ConfigurationException;
use Curacel\LangFuse\Transporters\LangFuseClient;
use Curacel\LangFuse\ValueObjects\BaseUri;
use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\ServiceProvider;

final class LangFuseServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->configure()->bindLangFuseClient();
    }

    /**
     * Bootstrap any package services.
     */
    public function boot(): void
    {
        $this->registerPublishing();
    }

    protected function bindLangFuseClient(): void
    {
        $this->app->singleton(LangFuseClientContract::class, static function (Application|Container $app) {
            /* @var Repository $config */
            $config = $app->make('config');

            empty($secretKey = $config->get('langfuse.secret_key')) && throw ConfigurationException::noSecretKey();
            empty($publicKey = $config->get('langfuse.public_key')) && throw ConfigurationException::noPublicKey();

            return new LangFuseClient(
                transporter: Http::baseUrl(
                    BaseUri::from((string) $config->get('langfuse.host'))->toString()
                )
                    ->withBasicAuth($publicKey, $secretKey)
                    ->asJson()
                    ->acceptJson()
                    ->timeout($config->get('langfuse.timeout', 20))
                    ->connectTimeout((int) $config->get('langfuse.connect_timeout', 10)),
                config: $config
            );
        });

        $this->app->alias(LangFuseClientContract::class, 'langfuse');
        $this->app->alias(LangFuseClientContract::class, LangFuseClient::class);
    }

    /**
     * Setup the configuration for LangFuse.
     */
    protected function configure(): static
    {
        $this->mergeConfigFrom(
            path: __DIR__.'/../config/langfuse.php',
            key: 'langfuse'
        );

        return $this;
    }

    /**
     * Register the package's publishable resources.
     */
    protected function registerPublishing(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/langfuse.php' => $this->app->configPath('langfuse.php'),
            ], 'langfuse-config');
        }
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [
            LangFuseClient::class,
            LangFuseClientContract::class,
            'langfuse',
        ];
    }
}
