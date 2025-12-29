<?php
/**
 * Plugin Name: WP MCP Abilities Example
 * Description: Example implementation of WordPress Abilities API exposed via an MCP server.
 * Author: Gajendra Singh
 * Author URI: https://profiles.wordpress.org/gajendrasingh/
 * Requires at least: 6.8
 * Version:           1.0.0
 * Requires PHP:      7.4
 * Requires Plugins: 
 *
 * This file demonstrates:
 * - Registering Ability Categories
 * - Registering Abilities with input/output schemas
 * - Making abilities discoverable by AI agents
 * - Attaching abilities to an MCP Server
 *
 * Tested with WordPress 6.9+ and the MCP Adapter plugin.
 */


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use WP\MCP\Core\McpAdapter;

function wp_add_McpAdapter(){
    if ( ! class_exists( McpAdapter::class ) ) {
        return; // or show an admin notice   
       
    }
}

/**
 * ------------------------------------------------------------
 * Step 1: Register Ability Categories
 * First, I created the ability category (Create Post). 
 * This step is required because abilities must be assigned to a category at the time they are registered.
 * ------------------------------------------------------------
 */

add_action( 'wp_abilities_api_categories_init', 'wpv_register_ability_categories' );

function wpv_register_ability_categories() {
	wp_register_ability_category(
		'site-post',
		[
			'label'       => 'Create Post',
			'description' => 'Abilities related to creating site content',
		]
	);
}

/**
 * ------------------------------------------------------------
 * Step 2: Register Abilities
 * 
 * Each ability defines:
 * Permission callback → checks user capability
 * Input schema → tells AI what input is required
 * Output schema → defines what the response returns
 * Execute callback → performs the actual task
 * ------------------------------------------------------------
 */
add_action( 'wp_abilities_api_init', 'wpv_register_abilities' );

function wpv_register_abilities() {

	wp_register_ability(
		'wpv/create-post',
		[
			'label'       => 'Create Post',
			'description' => 'Create a new WordPress post using structured input.',
			'category'    => 'site-post',

			'input_schema' => [
				'type'       => 'object',
				'properties' => [
					'title' => [
						'type'        => 'string',
						'description' => 'Title of the post',
					],
					'content' => [
						'type'        => 'string',
						'description' => 'Post content (block editor markup supported)',
					],
					'status' => [
						'type'        => 'string',
						'description' => 'Post status',
						'default'     => 'draft',
						'enum'        => [ 'draft', 'publish' ],
					],
				],
				'required' => [ 'title', 'content' ],
			],

			'output_schema' => [
				'type'       => 'object',
				'properties' => [
					'success' => [
						'type'        => 'boolean',
						'description' => 'Whether the post was created successfully',
					],
					'url' => [
						'type'        => 'string',
						'description' => 'URL of the created post',
					],
					'error' => [
						'type'        => 'string',
						'description' => 'Error message if creation failed',
					],
				],
			],

			'execute_callback'    => 'wpv_create_post',
			'permission_callback' => '__return_true', // Demo only. Restrict in production.

			'meta' => [
				'show_in_rest' => true,
				'mcp'          => [
					'public' => true,
					'type'   => 'tool',
				],
			],
		]
	);
}

/**
 * ------------------------------------------------------------
 * Step 3: Ability Logic
 * Handles post creation using the input provided by the ability.
 * ------------------------------------------------------------
 */
function wpv_create_post( $input ) {

	if ( empty( $input['title'] ) || empty( $input['content'] ) ) {
		return [
			'success' => false,
			'error'   => 'Invalid input data.',
		];
	}

	$post_data = [
		'post_title'   => sanitize_text_field( $input['title'] ),
		'post_content' => wp_kses_post( $input['content'] ),
		'post_status'  => isset( $input['status'] ) && in_array(
			$input['status'],
			[ 'draft', 'publish' ],
			true
		)
			? sanitize_text_field( $input['status'] )
			: 'draft',
		'post_author'  => get_current_user_id(),
	];

	$post_id = wp_insert_post( $post_data, true );

	if ( is_wp_error( $post_id ) ) {
		return [
			'success' => false,
			'error'   => $post_id->get_error_message(),
		];
	}

	return [
		'success' => true,
		'url'     => get_permalink( $post_id ),
	];
}

/**
 * ------------------------------------------------------------
 * Step 4: Register MCP Server
 * Finally, attach abilities to an MCP server. 
 * ------------------------------------------------------------
 */
add_action(
	'mcp_adapter_init',
	function ( $adapter ) {

		$adapter->create_server(
			'site-content-server',  // Server ID
			'site-content-server',  // REST namespace
			'mcp',                  // REST route
			'Site Content Server',  // Server name
			'MCP server for creating WordPress posts.', // Description
			'1.0.0',
			[
				\WP\MCP\Transport\HttpTransport::class, // Transport methods
			],
			\WP\MCP\Infrastructure\ErrorHandling\ErrorLogMcpErrorHandler::class, // Error handler
			\WP\MCP\Infrastructure\Observability\NullMcpObservabilityHandler::class, // Observability handler
			[
				'wpv/create-post', //Abilities to expose as tools
			]
		);
	}
);
?>
