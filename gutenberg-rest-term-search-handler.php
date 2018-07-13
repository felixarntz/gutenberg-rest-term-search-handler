<?php
/**
 * Plugin Name: Gutenberg REST Term Search Handler
 * Plugin URI: https://github.com/felixarntz/gutenberg-rest-term-search-handler
 * Description: Search handler for supporting terms in the general REST search controller.
 * Version: 0.1.0
 * Author: Felix Arntz
 */

/**
 * Provides the term search handler to the list of search handlers to use.
 *
 * @since 0.1.0
 *
 * @param array $search_handlers List of search handlers to use in the controller.
 * @return array Filtered list.
 */
function gutenberg_provide_rest_term_search_handler( array $search_handlers ) {
	require_once plugin_dir_path( __FILE__ ) . 'class-wp-rest-term-search-handler.php';

	$search_handlers[] = new WP_REST_Term_Search_Handler();

	return $search_handlers;
}
add_filter( 'wp_rest_search_handlers', 'gutenberg_provide_rest_term_search_handler' );
