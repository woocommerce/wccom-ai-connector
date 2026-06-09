<?php

declare(strict_types=1);

namespace Automattic\WccomAiConnector\Haydi;

/**
 * WCCOM-specific Haydi customizations for the /start/ Playground sandbox.
 *
 * This plugin is only installed by the WooCommerce.com blueprint. Keeping the
 * prompt, suggestion chips, and launch hint here avoids baking WCCOM behaviour
 * into Haydi itself.
 */
final class WccomHaydi {

	/**
	 * Register Haydi customization hooks.
	 */
	public static function init(): void {
		add_filter( 'haydi_system_prompt', array( self::class, 'filter_system_prompt' ), 10, 2 );
		add_filter( 'haydi_suggestion_pool', array( self::class, 'filter_suggestion_pool' ) );
		add_filter( 'haydi_suggestion_hint', array( self::class, 'filter_suggestion_hint' ) );
		add_filter( 'haydi_greeting_capabilities', array( self::class, 'filter_greeting_capabilities' ) );
		add_filter( 'haydi_greeting_footer', array( self::class, 'filter_greeting_footer' ) );
		add_filter( 'haydi_known_extensions', array( self::class, 'filter_known_extensions' ) );
		add_action( 'admin_enqueue_scripts', array( self::class, 'enqueue_haydi_styles' ), 20 );
	}

	/**
	 * Tailor the Haydi system prompt for Automattician-assisted Woo stores.
	 *
	 * @param string $prompt  Haydi's generated system prompt.
	 * @param array  $context Prompt context from Haydi.
	 */
	public static function filter_system_prompt( string $prompt, array $context = array() ): string {
		unset( $context );

		$wccom_context = implode(
			"\n",
			array(
				'',
				'## WooCommerce.com Start Sandbox',
				'You are helping an Automattician and a merchant prototype a WooCommerce store inside a WordPress Playground sandbox launched from WooCommerce.com.',
				'Favor concrete WooCommerce store-building help: products, categories, homepage structure, navigation, checkout readiness, shipping/tax/payment setup guidance, and concise merchandising copy.',
				'When the user gives a broad idea, turn it into a specific Woo store concept with sample products and next actions. Good starter concepts include pancake mix shops, pizza shops, coffee roasters, bakeries, pet supply stores, local boutiques, classes, and event-ticket stores.',
				'Keep responses practical and merchant-facing. Do not discuss internal WCCOM implementation details, proxy keys, or blueprint mechanics unless the user asks.',
				'This sandbox is not the live destination site. Do not claim changes are published to WordPress.com or WooCommerce.com.',
				'If the user asks to launch, publish, go live, or keep this site, tell them to click the "Launch on WordPress.com" button in the top bar of the page.',
			)
		);

		return $prompt . "\n" . $wccom_context;
	}

	/**
	 * Replace Haydi's generic chips with Woo store starter prompts.
	 *
	 * @param array $pool Existing suggestion pool.
	 */
	public static function filter_suggestion_pool( array $pool ): array {
		unset( $pool );

		return array(
			'pancakes' => array(
				__( 'Build a pancake mix store with three products, a homepage, and starter copy.', 'wccom-ai-connector' ),
			),
			'pizza'    => array(
				__( 'Create a pizza shop that sells pickup and delivery items with categories.', 'wccom-ai-connector' ),
			),
			'coffee'   => array(
				__( 'Set up a coffee roaster store with beans, bundles, and subscription ideas.', 'wccom-ai-connector' ),
			),
			'boutique' => array(
				__( 'Make a small boutique store with sample products and a simple launch checklist.', 'wccom-ai-connector' ),
			),
		);
	}

	/**
	 * Customize the suggestion-chip hint.
	 */
	public static function filter_suggestion_hint( string $hint = '' ): string {
		unset( $hint );
		return __( 'Try a Woo store starter prompt:', 'wccom-ai-connector' );
	}

	/**
	 * Focus Haydi's opening capability list on Woo store work.
	 *
	 * @param array $capabilities Existing greeting capabilities.
	 */
	public static function filter_greeting_capabilities( array $capabilities ): array {
		unset( $capabilities );

		return array(
			__( 'Creating WooCommerce store concepts and sample products', 'wccom-ai-connector' ),
			__( 'Drafting homepage, category, and merchandising copy', 'wccom-ai-connector' ),
			__( 'Checking store setup tasks like payments, shipping, tax, and checkout', 'wccom-ai-connector' ),
			__( 'Installing and configuring useful WooCommerce plugins after approval', 'wccom-ai-connector' ),
			__( 'Launching this sandbox to a live WordPress.com site — use the button in the top bar when ready', 'wccom-ai-connector' ),
		);
	}

	/**
	 * Add a launch hint to the Haydi greeting footer.
	 *
	 * @param string $footer Existing greeting footer HTML.
	 */
	public static function filter_greeting_footer( string $footer ): string {
		unset( $footer );

		return sprintf(
			'<p class="wccom-a12s-launch-hint">%s</p>',
			esc_html__( 'When your store is ready, click Launch on WordPress.com in the top bar to publish it.', 'wccom-ai-connector' )
		);
	}

	/**
	 * Surface this customization in Haydi's extension inventory.
	 *
	 * @param array $extensions Existing extension metadata.
	 */
	public static function filter_known_extensions( array $extensions ): array {
		$extensions['wccom_ai_connector'] = array(
			'label'        => 'WooCommerce.com AI Connector',
			'description'  => 'Adds WCCOM proxy access and Woo store prompts for the /start/ Playground sandbox.',
			'capabilities' => array(
				'wccom_ai_provider',
				'woo_store_suggestions',
			),
		);

		return $extensions;
	}

	/**
	 * Add launch-hint styles on Haydi admin screens.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public static function enqueue_haydi_styles( string $hook ): void {
		if ( false === strpos( $hook, 'haydi' ) ) {
			return;
		}

		wp_register_style( 'wccom-ai-connector-haydi', false, array(), '0.1.0' );
		wp_enqueue_style( 'wccom-ai-connector-haydi' );
		wp_add_inline_style( 'wccom-ai-connector-haydi', self::haydi_css() );
	}

	/**
	 * CSS for WCCOM additions.
	 */
	private static function haydi_css(): string {
		return <<<CSS
.wccom-a12s-launch-hint {
	margin: 12px 0 2px;
	padding: 12px 16px;
	border: 1px solid #bcd7ff;
	border-radius: 8px;
	background: #f4f9ff;
	color: #1e3a5f;
	font-size: 14px;
}
CSS;
	}
}
