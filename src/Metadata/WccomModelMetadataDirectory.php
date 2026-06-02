<?php

declare(strict_types=1);

namespace Automattic\WccomAiConnector\Metadata;

use WordPress\AiClient\Messages\Enums\ModalityEnum;
use WordPress\AiClient\Providers\Contracts\ModelMetadataDirectoryInterface;
use WordPress\AiClient\Providers\Models\DTO\ModelMetadata;
use WordPress\AiClient\Providers\Models\DTO\SupportedOption;
use WordPress\AiClient\Providers\Models\Enums\CapabilityEnum;
use WordPress\AiClient\Providers\Models\Enums\OptionEnum;

/**
 * Static metadata directory — returns the wccom proxy's allowlisted models
 * without a network round-trip. Mirrors the wccom-side `Models::allowlist()`
 * but is declared here rather than fetched at runtime because the directory
 * is consulted during synchronous WP AI Client setup; an HTTP call here
 * would block on every page load.
 *
 * Keep the two lists in lockstep — adding a model on the wccom side without
 * touching this file means the connector will reject it client-side before
 * the proxy ever sees the request.
 */
class WccomModelMetadataDirectory implements ModelMetadataDirectoryInterface {

	/** @return list<ModelMetadata> */
	public function listModelMetadata(): array {
		$capabilities = array(
			CapabilityEnum::textGeneration(),
			CapabilityEnum::chatHistory(),
		);

		$options = array(
			new SupportedOption( OptionEnum::systemInstruction() ),
			new SupportedOption( OptionEnum::candidateCount() ),
			new SupportedOption( OptionEnum::maxTokens() ),
			new SupportedOption( OptionEnum::temperature() ),
			new SupportedOption( OptionEnum::topP() ),
			new SupportedOption( OptionEnum::stopSequences() ),
			new SupportedOption( OptionEnum::presencePenalty() ),
			new SupportedOption( OptionEnum::frequencyPenalty() ),
			new SupportedOption( OptionEnum::logprobs() ),
			new SupportedOption( OptionEnum::topLogprobs() ),
			new SupportedOption( OptionEnum::outputMimeType(), array( 'text/plain', 'application/json' ) ),
			new SupportedOption( OptionEnum::outputSchema() ),
			new SupportedOption( OptionEnum::functionDeclarations() ),
			new SupportedOption( OptionEnum::webSearch() ),
			new SupportedOption( OptionEnum::customOptions() ),
			new SupportedOption( OptionEnum::inputModalities(), array( array( ModalityEnum::text() ) ) ),
			new SupportedOption( OptionEnum::outputModalities(), array( array( ModalityEnum::text() ) ) ),
		);

		// Keep in lockstep with wccom-side `Models::allowlist()`. Order
		// matters: UIs that default to the first entry should land on the
		// cheapest member of the newest family.
		$ids = array(
			'gpt-5.5',
		);

		$models = array();
		foreach ( $ids as $id ) {
			$models[] = new ModelMetadata( $id, $id, $capabilities, $options );
		}
		return $models;
	}

	public function getModelMetadata( string $modelId ): ModelMetadata {
		foreach ( $this->listModelMetadata() as $metadata ) {
			if ( $metadata->getId() === $modelId ) {
				return $metadata;
			}
		}
		throw new \InvalidArgumentException( 'Unknown model: ' . $modelId );
	}

	public function hasModelMetadata( string $modelId ): bool {
		foreach ( $this->listModelMetadata() as $metadata ) {
			if ( $metadata->getId() === $modelId ) {
				return true;
			}
		}
		return false;
	}
}
