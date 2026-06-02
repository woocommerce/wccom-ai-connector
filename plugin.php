<?php
/**
 * Plugin Name: WCCOM AI Connector
 * Plugin URI: https://github.com/Automattic/wccom-ai-connector
 * Description: Routes WordPress AI Client calls through woocommerce.com's per-user OpenAI proxy. Intended to be installed inside a WordPress Playground sandbox launched from woocommerce.com/start/.
 * Requires at least: 7.0
 * Requires PHP: 8.1
 * Version: 0.1.0
 * Author: Automattic
 * License: GPL-2.0-or-later
 * Text Domain: wccom-ai-connector
 *
 * @package Automattic\WccomAiConnector
 */

declare(strict_types=1);

namespace Automattic\WccomAiConnector;

use WordPress\AiClient\AiClient;
use Automattic\WccomAiConnector\Haydi\WccomHaydi;
use Automattic\WccomAiConnector\Provider\WccomProvider;

if ( ! defined( 'ABSPATH' ) ) {
	return;
}

require_once __DIR__ . '/src/autoload.php';

/**
 * Register the WCCOM provider with the WP AI Client registry. Mirrors the
 * upstream `ai-provider-for-openai` registration pattern — `init` priority 5
 * so we land before plugins that probe the registry at default priority.
 */
function register_provider(): void {
	if ( ! class_exists( AiClient::class ) ) {
		return;
	}
	$registry = AiClient::defaultRegistry();
	if ( $registry->hasProvider( WccomProvider::class ) ) {
		return;
	}
	$registry->registerProvider( WccomProvider::class );
}

add_action( 'init', __NAMESPACE__ . '\\register_provider', 5 );
WccomHaydi::init();
