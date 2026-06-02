<?php

declare(strict_types=1);

namespace Automattic\WccomAiConnector\Haydi;

/**
 * WCCOM-specific Haydi customizations for the /start/ Playground sandbox.
 *
 * This plugin is only installed by the WooCommerce.com blueprint. Keeping the
 * prompt, suggestion chips, and migration CTA here avoids baking WCCOM behavior
 * into Haydi itself.
 */
final class WccomHaydi {

	public const MIGRATION_URL = 'https://wordpress.com/setup/hosted-site-migration?ref=support-page-import-an-entire-wordpress-site';

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
		add_filter( 'haydi_tool_schemas', array( self::class, 'filter_tool_schemas' ) );
		add_filter( 'haydi_execute_read_tool', array( self::class, 'execute_read_tool' ), 10, 3 );
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
				'If the user asks to launch, publish, migrate, move, or keep this site, call wccom_get_migration_url and explain that the current migration button opens the WordPress.com migration flow. Do not claim the Playground archive is attached automatically; automatic archive handoff still needs WordPress.com/Playground integration.',
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
			__( 'Pointing you toward the WordPress.com migration flow when you are ready to move forward', 'wccom-ai-connector' ),
		);
	}

	/**
	 * Add the WordPress.com migration CTA to the Haydi greeting.
	 *
	 * @param string $footer Existing greeting footer HTML.
	 */
	public static function filter_greeting_footer( string $footer ): string {
		unset( $footer );

		$button = sprintf(
			'<div class="wccom-a12s-migration-cta"><p class="wccom-a12s-migration-cta__copy">%s</p><a class="button button-primary wccom-a12s-migration-cta__button" href="%s" target="_blank" rel="noopener noreferrer">%s</a><p class="wccom-a12s-migration-cta__note">%s</p></div>',
			esc_html__( 'Ready to move this sandbox toward a hosted WordPress.com site?', 'wccom-ai-connector' ),
			esc_url( self::MIGRATION_URL ),
			esc_html__( 'Start WordPress.com migration', 'wccom-ai-connector' ),
			esc_html__( 'Automatic Playground archive handoff is not wired yet; use the migration flow as the next step.', 'wccom-ai-connector' )
		);

		return $button;
	}

	/**
	 * Surface this customization in Haydi's extension inventory.
	 *
	 * @param array $extensions Existing extension metadata.
	 */
	public static function filter_known_extensions( array $extensions ): array {
		$extensions['wccom_ai_connector'] = array(
			'label'        => 'WooCommerce.com AI Connector',
			'description'  => 'Adds WCCOM proxy access, Woo store prompts, and the WordPress.com migration CTA.',
			'capabilities' => array(
				'wccom_ai_provider',
				'woo_store_suggestions',
				'wordpress_com_migration_cta',
			),
		);

		return $extensions;
	}

	/**
	 * Add a small read-only tool the model can call when users ask to publish.
	 *
	 * @param array $schemas Existing Haydi tool schemas.
	 */
	public static function filter_tool_schemas( array $schemas ): array {
		$schemas['wccom_get_migration_url'] = array(
			'description' => 'Return the WordPress.com hosted-site migration URL for moving the current Playground prototype toward a hosted WordPress.com site.',
			'fields'      => array(),
		);

		return $schemas;
	}

	/**
	 * Execute WCCOM read-only Haydi tools.
	 *
	 * @param mixed  $result Existing filtered result, or null.
	 * @param string $name   Tool name.
	 * @param array  $args   Tool arguments.
	 */
	public static function execute_read_tool( mixed $result, string $name, array $args ): mixed {
		unset( $args );

		if ( null !== $result || 'wccom_get_migration_url' !== $name ) {
			return $result;
		}

		return array(
			'url'     => self::MIGRATION_URL,
			'message' => __( 'Open the WordPress.com migration flow. Automatic Playground archive handoff is not yet wired.', 'wccom-ai-connector' ),
		);
	}

	/**
	 * Add CTA styles on Haydi admin screens.
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
.wccom-a12s-migration-cta {
	margin: 16px 0 2px;
	padding: 16px;
	border: 1px solid #bcd7ff;
	border-radius: 8px;
	background: #f4f9ff;
}
.wccom-a12s-migration-cta__copy {
	margin: 0 0 12px;
	font-weight: 600;
}
.wccom-a12s-migration-cta__button.button.button-primary {
	display: inline-flex;
	align-items: center;
	justify-content: center;
	min-height: 44px;
	padding: 0 20px;
	border-color: #0057d8;
	background: #0057d8;
	font-size: 15px;
	font-weight: 700;
}
.wccom-a12s-migration-cta__button.button.button-primary:hover,
.wccom-a12s-migration-cta__button.button.button-primary:focus {
	border-color: #0046ad;
	background: #0046ad;
}
.wccom-a12s-migration-cta__note {
	margin: 12px 0 0;
	color: #1e3a5f;
	font-size: 13px;
}
@media (max-width: 600px) {
	.wccom-a12s-migration-cta {
		padding: 14px;
	}
	.wccom-a12s-migration-cta__button.button.button-primary {
		width: 100%;
	}
}
CSS;
	}
}
