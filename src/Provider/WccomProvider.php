<?php

declare(strict_types=1);

namespace Automattic\WccomAiConnector\Provider;

use Automattic\WccomAiConnector\Metadata\WccomModelMetadataDirectory;
use Automattic\WccomAiConnector\Models\WccomTextGenerationModel;
use WordPress\AiClient\Common\Exception\RuntimeException;
use WordPress\AiClient\Providers\ApiBasedImplementation\AbstractApiProvider;
use WordPress\AiClient\Providers\ApiBasedImplementation\ListModelsApiBasedProviderAvailability;
use WordPress\AiClient\Providers\Contracts\ModelMetadataDirectoryInterface;
use WordPress\AiClient\Providers\Contracts\ProviderAvailabilityInterface;
use WordPress\AiClient\Providers\DTO\ProviderMetadata;
use WordPress\AiClient\Providers\Enums\ProviderTypeEnum;
use WordPress\AiClient\Providers\Http\Enums\RequestAuthenticationMethod;
use WordPress\AiClient\Providers\Models\Contracts\ModelInterface;
use WordPress\AiClient\Providers\Models\DTO\ModelMetadata;

/**
 * WCCOM proxy provider. Connects to a per-user, Automattician-only proxy
 * fronting OpenAI on woocommerce.com. The wire format is OpenAI
 * chat/completions, not the newer Responses API — keeps the model class
 * lean and matches what the wccom proxy speaks today.
 *
 * Base URL is read from `wp_option('wccom_ai_base_url')` so the same
 * connector can target production, staging, or a sandbox without a code
 * change. The Playground blueprint writes both that option and the API key
 * before this plugin's `init` hook fires.
 */
class WccomProvider extends AbstractApiProvider {

	public const DEFAULT_BASE_URL = 'https://woocommerce.com/wp-json/wccom/ai/v1';

	protected static function baseUrl(): string {
		$configured = (string) get_option( 'wccom_ai_base_url', '' );
		return '' !== $configured ? $configured : self::DEFAULT_BASE_URL;
	}

	protected static function createModel(
		ModelMetadata $modelMetadata,
		ProviderMetadata $providerMetadata
	): ModelInterface {
		foreach ( $modelMetadata->getSupportedCapabilities() as $capability ) {
			if ( $capability->isTextGeneration() ) {
				return new WccomTextGenerationModel( $modelMetadata, $providerMetadata );
			}
		}
		throw new RuntimeException( 'Unsupported model capabilities.' );
	}

	protected static function createProviderMetadata(): ProviderMetadata {
		return new ProviderMetadata(
			'wccom',
			'WooCommerce.com',
			ProviderTypeEnum::cloud(),
			'https://woocommerce.com/start/',
			RequestAuthenticationMethod::apiKey()
		);
	}

	protected static function createProviderAvailability(): ProviderAvailabilityInterface {
		return new ListModelsApiBasedProviderAvailability( static::modelMetadataDirectory() );
	}

	protected static function createModelMetadataDirectory(): ModelMetadataDirectoryInterface {
		return new WccomModelMetadataDirectory();
	}
}
