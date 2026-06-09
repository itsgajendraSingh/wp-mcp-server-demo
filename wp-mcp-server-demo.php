<?php
/**
 * Plugin Name: WP MCP Abilities Example
 * Description: Example implementation of WordPress Abilities API exposed via an MCP server.
 * Author: Gajendra Singh
 * Author URI: https://profiles.wordpress.org/gajendrasingh/
 * Requires at least: 6.9
 * Version: 1.0.0
 * Tested up to: 7.0
 * MCP Adapter: 0.5.0
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

use WP\MCP\Domain\Prompts\McpPrompt;
use WP\MCP\Domain\Prompts\McpPromptBuilder;


if ( ! class_exists( \WP\MCP\Core\McpAdapter::class ) ) {
	add_action(
		'admin_notices',
		function () {
			echo '<div class="notice notice-error"><p>MCP Adapter plugin is not active.</p></div>';
		}
	);

	return;
}


/**
 * ------------------------------------------------------------
 * Step 1: Register Ability Categories
 * First, I created the ability category (Create Post). 
 * This step is required because abilities must be assigned to a category at the time they are registered.
 * ------------------------------------------------------------
 */

add_action( 'wp_abilities_api_categories_init',function () {
	wp_register_ability_category(
		'site-post',
		[
			'label'       => 'Create Post',
			'description' => 'Abilities related to creating site content',
		]
	);
});

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
add_action( 'wp_abilities_api_init', function () {
	wp_register_ability(
		'wpv/get-posts',
		[
			'label'       => 'Get Posts',
			'description' => 'Retrieve a list of WordPress posts with optional filters.',
			'category'    => 'site-post',
			'input_schema' => [
				'type'       => 'object',
				'properties' => [
					'numberposts' => [
						'type'        => 'integer',
						'description' => 'Number of posts to return. Default is 5.',
						'default'     => 5,
						'minimum'     => 1,
						'maximum'     => 100,
					],
					'post_status' => [
						'type'        => 'string',
						'description' => 'Post status to filter by.',
						'enum'        => [ 'publish', 'draft', 'private' ],
						'default'     => 'publish',
					],
				],
			],
			'output_schema' => [
				'type'  => 'array',
				'items' => [
					'type'       => 'object',
					'properties' => [
						'ID'          => [ 'type' => 'integer' ],
						'post_title'  => [ 'type' => 'string' ],
						'post_status' => [ 'type' => 'string' ],
						'post_date'   => [ 'type' => 'string' ],
						'permalink'   => [ 'type' => 'string' ],
					],
				],
			],
			'execute_callback' => function ( $input ) {
				$posts = get_posts( [
					'numberposts' => $input['numberposts'] ?? 5,
					'post_status' => $input['post_status'] ?? 'publish',
				] );

				return array_map( function ( $post ) {
					return [
						'ID'          => $post->ID,
						'post_title'  => $post->post_title,
						'post_status' => $post->post_status,
						'post_date'   => $post->post_date,
						'permalink'   => get_permalink( $post->ID ),
					];
				}, $posts );
			},
			'permission_callback' => function () {
				return current_user_can( 'read' );
			},
			'meta' => [
				'show_in_rest' => true,
				'mcp'          => [
					'public' => true,
					'type'   => 'resource',
				],
			],
		]
	);

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
			'permission_callback' => function () {
				// Always use a real capability check. Never use __return_true in production.
				return current_user_can( 'edit_posts' );
			},

			'meta' => [
				'show_in_rest' => true,
				'mcp'          => [
					'public' => true,
					'type'   => 'tool',
				],
			],
		]
	);
});

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
add_action( 'mcp_adapter_init', function ( $adapter ) {

	$create_post_prompt = McpPrompt::fromBuilder(
		( new McpPromptBuilder() )
			->set_name( 'wpv/create-post-prompt' )
			->set_title( 'Create Post Prompt' )
			->set_description( 'Guides the AI to create a WordPress post with title, content, and status.' )
			->add_argument( 'topic', 'The topic or subject of the post', true )
			->add_argument( 'tone', 'Writing tone: formal, casual, or technical', false )
			->set_handler( function ( $args ) {
				$topic = $args['topic'] ?? 'a general topic';
				$tone  = $args['tone'] ?? 'professional';

				return [
					[
						'role'    => 'user',
						'content' => "Write a WordPress blog post about: {$topic}. " .
						             "Use a {$tone} tone. " .
						             "Provide a clear title, structured content with headings, and set status to draft.",
					],
				];
			} )
			->set_permission( function () {
				return current_user_can( 'edit_posts' );
			} )
	);

	$adapter->create_server(
		'site-content-server',
		'site-content-server',
		'mcp',
		'Site Content Server',
		'MCP server for reading and creating WordPress posts.',
		'1.0.0',
		[
			\WP\MCP\Transport\HttpTransport::class,
		],
		\WP\MCP\Infrastructure\ErrorHandling\ErrorLogMcpErrorHandler::class,
		\WP\MCP\Infrastructure\Observability\NullMcpObservabilityHandler::class,
		[ 'wpv/create-post' ],
		[ 'wpv/get-posts' ],
		[ $create_post_prompt ]
	);
} );
?>
