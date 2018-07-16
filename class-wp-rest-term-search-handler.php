<?php
/**
 * REST API: WP_REST_Term_Search_Handler class
 *
 * @since 0.1.0
 */

/**
 * Core class representing a search handler for terms in the REST API.
 *
 * @since 0.1.0
 */
class WP_REST_Term_Search_Handler extends WP_REST_Search_Handler {

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 */
	public function __construct() {
		$this->type = 'term';

		// Support all public taxonomies.
		$this->subtypes = array_values( get_taxonomies( array(
			'public'       => true,
			'show_in_rest' => true,
		), 'names' ) );
	}

	/**
	 * Searches the object type content for a given search request.
	 *
	 * @since 0.1.0
	 *
	 * @param WP_REST_Request $request Full REST request.
	 * @return array Associative array containing an `WP_REST_Search_Handler::RESULT_IDS` containing
	 *               an array of found IDs and `WP_REST_Search_Handler::RESULT_TOTAL` containing the
	 *               total count for the matching search results.
	 */
	public function search_items( WP_REST_Request $request ) {

		// Get the taxonomies to search for the current request.
		$taxonomies = $request[ WP_REST_Search_Controller::PROP_SUBTYPE ];
		if ( in_array( WP_REST_Search_Controller::TYPE_ANY, $taxonomies, true ) ) {
			$taxonomies = $this->subtypes;
		}

		$offset = (int) $request['per_page'] * ( (int) $request['page'] - 1 );

		$query_args = array(
			'taxonomy'   => $taxonomies,
			'offset'     => $offset,
			'number'     => (int) $request['per_page'],
			'hide_empty' => false,
			'fields'     => 'ids',
		);

		if ( ! empty( $request['search'] ) ) {
			$query_args['search'] = $request['search'];
		}

		$query     = new WP_Term_Query();
		$found_ids = $query->query( $query_args );

		// Adjust the query to get the total count.
		$query_args['fields'] = 'count';
		unset( $query_args['offset'] );
		unset( $query_args['number'] );

		$query = new WP_Term_Query();
		$total = $query->query( $query_args );

		return array(
			self::RESULT_IDS   => $found_ids,
			self::RESULT_TOTAL => $total,
		);
	}

	/**
	 * Prepares the search result for a given ID.
	 *
	 * @since 0.1.0
	 *
	 * @param int   $id     Item ID.
	 * @param array $fields Fields to include for the item.
	 * @return array Associative array containing all fields for the item.
	 */
	public function prepare_item( $id, array $fields ) {
		$term = get_term( $id );

		$data = array();

		if ( in_array( WP_REST_Search_Controller::PROP_ID, $fields, true ) ) {
			$data[ WP_REST_Search_Controller::PROP_ID ] = (int) $term->term_id;
		}

		if ( in_array( WP_REST_Search_Controller::PROP_TITLE, $fields, true ) ) {
			$data[ WP_REST_Search_Controller::PROP_TITLE ] = $term->name;
		}

		if ( in_array( WP_REST_Search_Controller::PROP_URL, $fields, true ) ) {
			$data[ WP_REST_Search_Controller::PROP_URL ] = get_term_link( $term );
		}

		if ( in_array( WP_REST_Search_Controller::PROP_TYPE, $fields, true ) ) {
			$data[ WP_REST_Search_Controller::PROP_TYPE ] = $this->type;
		}

		if ( in_array( WP_REST_Search_Controller::PROP_SUBTYPE, $fields, true ) ) {
			$data[ WP_REST_Search_Controller::PROP_SUBTYPE ] = $term->taxonomy;
		}

		return $data;
	}

	/**
	 * Prepares links for the search result of a given ID.
	 *
	 * @since 0.1.0
	 *
	 * @param int $id Item ID.
	 * @return array Links for the given item.
	 */
	public function prepare_item_links( $id ) {
		$term = get_term( $id );

		$links = array();

		$item_route = $this->detect_rest_item_route( $term );
		if ( ! empty( $item_route ) ) {
			$links['self'] = array(
				'href'       => rest_url( $item_route ),
				'embeddable' => true,
			);
		}

		$links['about'] = array(
			'href' => rest_url( 'wp/v2/taxonomies/' . $term->taxonomy ),
		);

		return $links;
	}

	/**
	 * Attempts to detect the route to access a single item.
	 *
	 * @since 0.1.0
	 *
	 * @param WP_Term $term Term object.
	 * @return string REST route relative to the REST base URI, or empty string if unknown.
	 */
	protected function detect_rest_item_route( $term ) {
		$taxonomy = get_taxonomy( $term->taxonomy );
		if ( ! $taxonomy ) {
			return '';
		}

		// It's currently impossible to detect the REST URL from a custom controller.
		if ( ! empty( $taxonomy->rest_controller_class ) && 'WP_REST_Terms_Controller' !== $taxonomy->rest_controller_class ) {
			return '';
		}

		$namespace = 'wp/v2';
		$rest_base = ! empty( $taxonomy->rest_base ) ? $taxonomy->rest_base : $taxonomy->name;

		return sprintf( '%s/%s/%d', $namespace, $rest_base, $term->term_id );
	}
}
