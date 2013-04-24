<?php

/**
 * Static class for displaying template parts.
 *
 * @package     Connections
 * @subpackage  Template Parts
 * @copyright   Copyright (c) 2013, Steven A. Zahm
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       0.7.6
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

class cnTemplatePart {

	/**
	 * Register the default template actions.
	 *
	 * @access private
	 * @since 0.7.6.5
	 * @uses add_action()
	 * @return (void)
	 */
	public static function init() {
		add_action( 'cn_action_list_actions', array( __CLASS__, 'listActions' ) );
		add_action( 'cn_action_return_to_target', array( __CLASS__, 'returnToTopTarget' ) );
		add_action( 'cn_action_character_index', array( __CLASS__, 'characterIndex' ) );
	}

	/**
	 * Output the entry list actions.
	 *
	 * @access public
	 * @since 0.7.6.5
	 * @param  (array)  $atts [optional]
	 * @uses wp_parse_args()
	 * @uses apply_filters()
	 * @return (string)
	 */
	public static function listActions( $atts = array() ) {
		$out = '';
		$actions = array();

		$defaults = array(
			'before'      => '<ul id="cn-list-actions">',
			'before-item' => '<li class="cn-list-action-item">',
			'after-item'  => '</li>',
			'after'       => '</ul>',
			'return'      => FALSE
		);

		$atts = wp_parse_args( $atts, $defaults );

		if ( cnSettingsAPI::get( 'connections', 'connections_display_list_actions', 'view_all' ) && get_query_var( 'cn-view' ) !== 'all' )
			$actions['view_all'] = cnURL::permalink( array( 'type' => 'all', 'text' => __( 'View All', 'connections' ), 'rel' => 'canonical', 'return' => TRUE ) );

		$actions = apply_filters( 'cn_filter_list_actions', $actions );

		foreach ( $actions as $key => $action ) {
			$out .= "\n" . ( empty( $atts['before-item'] ) ? '' : $atts['before-item'] ) . $action . ( empty( $atts['after-item'] ) ? '' : $atts['after-item'] ) . "\n";
		}

		if ( $atts['return'] ) return "\n" . ( empty( $atts['before'] ) ? '' : $atts['before'] ) . $out . ( empty( $atts['after'] ) ? '' : $atts['after'] ) . "\n";
		echo "\n" . ( empty( $atts['before'] ) ? '' : $atts['before'] ) . $out . ( empty( $atts['after'] ) ? '' : $atts['after'] ) . "\n";
	}

	/**
	 * Output the return to top div.
	 *
	 * @access public
	 * @since 0.7.6.5
	 * @param  (array)  $atts [optional]
	 * @uses wp_parse_args()
	 * @uses apply_filters()
	 * @return (string)
	 */
	public static function returnToTopTarget( $atts = array() ) {

		$defaults = array(
			'return' => FALSE
		);

		$atts = wp_parse_args( $atts, $defaults );

		$out = apply_filters( 'cn_filter_return_to_top_target', '<div id="cn-top" style="position: absolute; top: 0; right: 0;"></div>' );

		if ( $atts['return'] ) return $out;
		echo $out;
	}

	/**
	 * The return to top anchor.
	 *
	 * @access public
	 * @version 1.0
	 * @since 0.7.3
	 * @uses wp_parse_args()
	 * @param array $atts [optional]
	 * @return string
	 */
	public static function returnToTop( $atts = array() ) {
		$styles = '';

		$defaults = array(
			'tag'    => 'span',
			'href'   => '#cn-top',
			'style'  => array(),
			'title'  => __('Return to top.', 'connections'),
			'text'   => '<img src="' . CN_URL . 'images/uparrow.gif" alt="' . __('Return to top.', 'connections') . '"/>',
			'before' => '',
			'after'  => '',
			'return' => FALSE
		);

		$atts = wp_parse_args( $atts, $defaults );

		if ( is_array( $atts['style'] ) && ! empty( $atts['style'] ) ) {

			array_walk( $atts['style'], create_function( '&$i, $property', '$i = "$property: $i";' ) );
			$styles = implode( $atts['style'], '; ' );
		}

		$anchor = '<a href="#cn-top" title="' . __('Return to top.', 'connections') . '"><img src="' . CN_URL . 'images/uparrow.gif" alt="' . __('Return to top.', 'connections') . '"/></a>';

		$out = '<' . $atts['tag'] . ( $styles ? ' style="' . $styles . '"' : ''  ) . '>' . $anchor . '</' . $atts['tag'] . '>';

		if ( $atts['return'] ) return "\n" . ( empty( $atts['before'] ) ? '' : $atts['before'] ) . $out . ( empty( $atts['after'] ) ? '' : $atts['after'] ) . "\n";
		echo "\n" . ( empty( $atts['before'] ) ? '' : $atts['before'] ) . $out . ( empty( $atts['after'] ) ? '' : $atts['after'] ) . "\n";
	}

	/**
	 * The last updated messagefor an entry.
	 *
	 * @access public
	 * @since 0.7.6.5
	 * @uses wp_parse_args()
	 * @uses human_time_diff()
	 * @uses current_time()
	 * @param (array) $atts [optional]
	 * @return (string)
	 */
	public static function updated( $atts = array() ) {
		$out = '';
		$styles = '';

		$defaults = array(
			'timestamp'   => '',
			'tag'         => 'span',
			'style'       => array(
				'font-size'    => 'x-small',
				'font-variant' => 'small-caps',
				'position'     => 'absolute',
				'right'        => '26px',
				'bottom'       => '8px'
				),
			'before'      => '',
			'after'       => '',
			'return'      => FALSE
		);

		$atts = wp_parse_args( $atts, $defaults );

		// No need to continue if the timestamp was not supplied.
		if ( ! isset( $atts['timestamp'] ) || empty( $atts['timestamp'] ) ) {

			if ( $atts['return'] ) return $out;
			echo $out;
		}

		$age = (int) abs( time() - strtotime( $atts['timestamp'] ) );

		if ( $age < 657000 ) // less than one week: red
			$atts['style']['color'] = 'red';
		elseif ( $age < 1314000 ) // one-two weeks: maroon
			$atts['style']['color'] = 'maroon';
		elseif ( $age < 2628000 ) // two weeks to one month: green
			$atts['style']['color'] = 'green';
		elseif ( $age < 7884000 ) // one - three months: blue
			$atts['style']['color'] = 'blue';
		elseif ( $age < 15768000 ) // three to six months: navy
			$atts['style']['color'] = 'navy';
		elseif ( $age < 31536000 ) // six months to a year: black
			$atts['style']['color'] = 'black';
		else      // more than one year: don't show the update age
			$atts['style']['display'] = 'none';

		if ( is_array( $atts['style'] ) && ! empty( $atts['style'] ) ) {

			array_walk( $atts['style'], create_function( '&$i, $property', '$i = "$property: $i";' ) );
			$styles = implode( $atts['style'], '; ' );
		}

		$updated = sprintf( __( 'Updated %1$s ago.' ), human_time_diff( strtotime( $atts['timestamp'] ), current_time( 'timestamp' ) ) );

		$out = '<' . $atts['tag'] . ( $styles ? ' style="' . $styles . '"' : ''  ) . '>' . $updated . '</' . $atts['tag'] . '>';

		if ( $atts['return'] ) return "\n" . ( empty( $atts['before'] ) ? '' : $atts['before'] ) . $out . ( empty( $atts['after'] ) ? '' : $atts['after'] ) . "\n";
		echo "\n" . ( empty( $atts['before'] ) ? '' : $atts['before'] ) . $out . ( empty( $atts['after'] ) ? '' : $atts['after'] ) . "\n";
	}

	/**
	 * Outputs the legacy character index. This is being deprecated in favor of cnTemplatePart::index().
	 * This was added for backward compatibility only for the legacy templates.
	 *
	 * @access public
	 * @since 0.7.6.5
	 * @deprecated since 0.7.6.5
	 * @uses wp_parse_args()
	 * @uses is_ssl()
	 * @uses add_query_arg()
	 * @param  (array) $atts [optional]
	 * @return (string)
	 */
	public static function characterIndex( $atts = array() ) {
		static $out = '';
		$links = array();
		$alphaindex = range( "A", "Z" );

		$defaults = array(
			'return' => FALSE
		);

		$atts = wp_parse_args( $atts, $defaults );

		/*
		 * $out is a static variable so if is not empty, this method was already run,
		 * so there is no need to rebuild the chracter index.
		 */
		if ( ! empty( $out ) ) {
			if ( $atts['return'] ) return $out;
			echo $out;
			return;
		}

		// The URL in the address bar
		$requestedURL  = is_ssl() ? 'https://' : 'http://';
		$requestedURL .= $_SERVER['HTTP_HOST'];
		$requestedURL .= $_SERVER['REQUEST_URI'];

		$parsedURL   = @parse_url( $requestedURL );

		$redirectURL = explode( '?', $requestedURL );
		$redirectURL = $redirectURL[0];

		// Ensure array index is set, prevent PHP error notice.
		if( ! isset( $parsedURL['query'] ) ) $parsedURL['query'] = array();

		$parsedURL['query'] = preg_replace( '#^\??&*?#', '', $parsedURL['query'] );

		// Add back on to the URL any remaining query string values.
		if ( $redirectURL && ! empty( $parsedURL['query'] ) ) {
			parse_str( $parsedURL['query'], $_parsed_query );
			$_parsed_query = array_map( 'rawurlencode_deep',  $_parsed_query );
		}

		foreach ( $alphaindex as $letter ) {

			if ( empty( $parsedURL['query'] ) ) {
				$links[] = '<a href="#cn-char-' . $letter . '">' . $letter . '</a>';
			} else {
				$links[] = '<a href="' . add_query_arg( $_parsed_query, $redirectURL . '#cn-char-' . $letter ) . '">' . $letter . '</a>';
			}

		}

		$out = "\n" . '<div class="cn-alphaindex">' . implode( ' ', $links ). '</div>' . "\n";

		if ( $atts['return'] ) return $out;
		echo $out;
	}

	/**
	 * Create the search input.
	 *
	 * Accepted option for the $atts property are:
	 * 	return (bool) Whether or not to return or echo the result.
	 *
	 * @access public
	 * @version 1.0
	 * @since 0.7.3
	 * @uses wp_parse_args()
	 * @uses get_query_var()
	 * @param array $atts [optional]
	 * @return string
	 */
	public static function search( $atts = array() ) {
		$out = '';

		$defaults = array(
			'show_label' => TRUE,
			'return'     => FALSE
		);

		$atts = wp_parse_args( $atts, $defaults );

		$searchValue = ( get_query_var('cn-s') ) ? get_query_var('cn-s') : '';

		$out .= '<span class="cn-search">';
			if ( $atts['show_label'] ) $out .= '<label for="cn-s">Search Directory</label>';
			$out .= '<input type="text" id="cn-search-input" name="cn-s" value="' . esc_attr( $searchValue ) . '" placeholder="' . __('Search', 'connections') . '"/>';
			$out .= '<input type="submit" name="" id="cn-search-submit" class="cn-search-button" value="" tabindex="-1" />';
		$out .= '</span>';

		// Output the the search input.
		if ( $atts['return']) return $out;
		echo $out;
	}

	/**
	 * Outputs a submit button.
	 *
	 * Accepted option for the $atts property are:
	 * 	name (string) The input name attribute.
	 * 	value (string) The input value attribute.
	 * 	return (bool) Whether or not to return or echo the result.
	 *
	 * @access public
	 * @version 1.0
	 * @since 0.7.3
	 * @uses wp_parse_args()
	 * @param array $atts [optional]
	 * @return string
	 */
	public static function submit( $atts = array() ) {
		$out = '';

		$defaults = array(
			'name'   => '',
			'value'  => __('Submit', 'connections'),
			'return' => FALSE
		);

		$atts = wp_parse_args( $atts, $defaults );

		$out .= '<input type="submit" name="' . $atts['name'] . '" id="cn-submit" class="button" value="' . $atts['value'] . '" tabindex="-1" />';

		// Output a submit button.
		if ( $atts['return'] ) return $out;
		echo $out;
	}

	/**
	 * Creates the initisl character filter control.
	 *
	 * Accepted option for the $atts property are:
	 * 	return (bool) Whether or not to return or echo the result.
	 *
	 * @access public
	 * @since 0.7.4
	 * @uses add_query_arg()
	 * @uses get_query_var()
	 * @uses wp_parse_args()
	 * @uses is_admin()
	 * @param  (array)  $atts [description]
	 * @return (string)
	 */
	public static function index( $atts = array() ) {
		$out = '';
		$current = '';

		$defaults = array(
			'status' => array( 'approved' ),
			'return' => FALSE
		);

		$atts = wp_parse_args( $atts, $defaults );

		$characters = cnRetrieve::getCharacters( $atts );
		$currentPageURL = add_query_arg( array( 'page' => FALSE , 'connections_process' => TRUE , 'process' => 'manage' , 'action' => 'filter' )  );

		// If in the admin init an instance of the cnFormObjects class to be used to create the URL nonce.
		if ( is_admin() ) $form = new cnFormObjects();

		// Current character
		if ( is_admin() ) {
			if ( isset( $_GET['cn-char'] ) && 0 < strlen( $_GET['cn-char'] ) ) $current = urldecode( $_GET['cn-char'] );
		} else {
			if ( get_query_var('cn-char') ) $current = urldecode( get_query_var('cn-char') );
		}

		foreach ( $characters as $key => $char ) {
			$char = strtoupper( $char );

			// If we're in the admin, add the nonce to the URL to be verified when settings the current user filter.
			if ( is_admin() ) {
				$out .= '<a' . ( $current == $char ? ' class="cn-char-current"' : ' class="cn-char"' ) . ' href="' . $form->tokenURL( add_query_arg( array( 'cn-char' => urlencode( $char ) ) , $currentPageURL ) , 'filter' ) . '">' . $char . '</a> ';
			} else {
				$out .= '<a' . ( $current == $char ? ' class="cn-char-current"' : ' class="cn-char"' ) . ' href="' . add_query_arg( array( 'cn-char' => urlencode( $char ) ) , $currentPageURL ) . '">' . $char . '</a> ';
			}

		}

		if ( $atts['return'] ) return $out;
		echo $out;
	}

	/**
	 * Retrieves the current character and outs a hidden form input.
	 *
	 * @access public
	 * @since 0.7.4
	 * @uses wp_parse_args()
	 * @uses is_admin()
	 * @uses get_query_var()
	 * @uses esc_attr()
	 * @param  (array)
	 * @return (string)
	 */
	public static function currentCharacter( $atts = array() ) {
		$out = '';
		$current = '';

		$defaults = array(
			'type'   => 'input',	// Resevered for future use. Will define the type of output to render. In this case a form input.
			'hidden' => TRUE,
			'return' => FALSE
		);

		$atts = wp_parse_args( $atts, $defaults );

		// Current character
		if ( is_admin() ) {
			if ( isset( $_GET['cn-char'] ) && 0 < strlen( $_GET['cn-char'] ) ) $current = urldecode( $_GET['cn-char'] );
		} else {
			if ( get_query_var('cn-char') ) $current = urldecode( get_query_var('cn-char') );
		}

		// Only output if there is a current character set in the query string.
		if ( 0 < strlen( $current ) ) $out .= '<input class="cn-current-char-input" name="cn-char" title="' . __('Current Character', 'connections') . '" type="' . ( $atts['hidden'] ? 'hidden' : 'text' ) . '" size="1" value="' . esc_attr( $current ) . '">';

		if ( $atts['return'] ) return $out;
		echo $out;
	}

	/**
	 * Creates the pagination controls.
	 *
	 * Accepted option for the $atts property are:
	 * 	limit (int) The pagination page limit.
	 * 	return (bool) Whether or not to return or echo the result.
	 *
	 * @access public
	 * @since 0.7.3
	 * @version 1.0
	 * @uses wp_parse_args()
	 * @uses get_permalink()
	 * @uses get_query_var()
	 * @uses add_query_arg()
	 * @uses absint()
	 * @uses trailingslashit()
	 * @param array $atts [optional]
	 * @return string
	 */
	public static function pagination( $atts = array() ) {
		global $wp_rewrite, $post,  $connections;

		$out = '';

		$defaults = array(
			'limit'  => 20,
			'return' => FALSE
		);

		$atts = wp_parse_args($atts, $defaults);

		$pageCount = ceil( $connections->retrieve->resultCountNoLimit / $atts['limit'] );

		if ( $pageCount > 1 ) {
			$current = 1;
			$disabled = array();
			$url = array();
			$page = array();
			$queryVars = array();

			// Get page/post permalink.
			// Only slash it when using pretty permalinks.
			$permalink = $wp_rewrite->using_permalinks() ? trailingslashit( get_permalink() ) : get_permalink();

			// Get the settings for the base of each data type to be used in the URL.
			$base = get_option('connections_permalink');

			// Store the query vars
			if ( get_query_var('cn-s') ) $queryVars['cn-s'] = get_query_var('cn-s');
			if ( get_query_var('cn-char') ) $queryVars['cn-char'] = get_query_var('cn-char');
			if ( get_query_var('cn-cat') ) $queryVars['cn-cat'] = get_query_var('cn-cat');
			if ( get_query_var('cn-near-coord') ) $queryVars['cn-near-coord'] = get_query_var('cn-near-coord');
			if ( get_query_var('cn-radius') ) $queryVars['cn-radius'] = get_query_var('cn-radius');
			if ( get_query_var('cn-unit') ) $queryVars['cn-unit'] = get_query_var('cn-unit');

			// Current page
			if ( get_query_var('cn-pg') ) $current = absint( get_query_var('cn-pg') );

			$page['first'] = 1;
			$page['previous'] = ( $current - 1 >= 1 ) ? $current - 1 : 1;
			$page['next'] = ( $current + 1 <= $pageCount ) ? $current + 1 : $pageCount;
			$page['last'] = $pageCount;

			// The class to apply to the disabled links.
			( $current > 1 ) ? $disabled['first'] = '' : $disabled['first'] = ' disabled';
			( $current - 1 >= 1 ) ? $disabled['previous'] = '' : $disabled['previous'] = ' disabled';
			( $current + 1 <= $pageCount ) ? $disabled['next'] = '' : $disabled['next'] = ' disabled';
			( $current < $pageCount ) ? $disabled['last'] = '' : $disabled['last'] = ' disabled';

			/*
			 * Create the page permalinks. If on a post or custom post type, use query vars.
			 */
			if ( is_page() && $wp_rewrite->using_permalinks() ) {

				// Add the category base and path if paging thru a category.
				if ( get_query_var('cn-cat-slug') ) $permalink = trailingslashit( $permalink . $base['category_base'] . '/' . get_query_var('cn-cat-slug') );

				$url['first'] = add_query_arg( $queryVars , $permalink . 'pg/' . $page['first'] );
				$url['previous'] = add_query_arg( $queryVars , $permalink . 'pg/' . $page['previous'] );
				$url['next'] = add_query_arg( $queryVars , $permalink . 'pg/' . $page['next'] );
				$url['last'] = add_query_arg( $queryVars , $permalink . 'pg/' . $page['last'] );

			} else {

				// If on the front page, add the query var for the page ID.
				if ( is_front_page() ) $permalink = add_query_arg( 'page_id' , $post->ID );

				// Add back on the URL any other Connections query vars.
				$permalink = add_query_arg( $queryVars , $permalink );

				$url['first'] = add_query_arg( array( 'cn-pg' => $page['first'] ) , $permalink );
				$url['previous'] = add_query_arg( array( 'cn-pg' => $page['previous'] ) , $permalink );
				$url['next'] = add_query_arg( array( 'cn-pg' => $page['next'] ) , $permalink );
				$url['last'] = add_query_arg( array( 'cn-pg' => $page['last'] ) , $permalink );
			}

			// Build the html page nav.
			$out .= '<span class="cn-page-nav" id="cn-page-nav">';

			$out .= '<a href="' . $url['first'] . '" title="' . __('First Page', 'connections') . '" class="cn-first-page' . $disabled['first'] . '">&laquo;</a> ';
			$out .= '<a href="' . $url['previous'] . '" title="' . __('Previous Page', 'connections') . '" class="cn-prev-page' . $disabled['previous'] . '" rel="prev">&lsaquo;</a> ';

			$out .= '<span class="cn-paging-input"><input type="text" size="1" value="' . $current . '" name="cn-pg" title="' . __('Current Page', 'connections') . '" class="current-page"> ' . __('of', 'connections') . ' <span class="total-pages">' . $pageCount . '</span></span> ';

			$out .= '<a href="' . $url['next'] . '" title="' . __('Next Page', 'connections') . '" class="cn-next-page' . $disabled['next'] . '" rel="next">&rsaquo;</a> ';
			$out .= '<a href="' . $url['last'] . '" title="' . __('Last Page', 'connections') . '" class="cn-last-page' . $disabled['last'] . '">&raquo;</a>';

			$out .= '</span>';
		}

		// Output the page nav.
		if ( $atts['return']) return $out;
		echo $out;
	}

	/**
	 * Parent public function that outputs the various categories output formats.
	 *
	 * Accepted option for the $atts property are:
	 * 	type (string) The ouput type of the categories. Valid options options are: select || multiselect || radio || checkbox
	 * 	group (bool) Whether or not to create option groups using the root parent as the group label. Used for select && multiselect only.
	 * 	default (string) The default string to show as the first item in the list. Used for select && multiselect only.
	 * 	show_select_all (bool) Whether or not to show the "Select All" option. Used for select && multiselect only.
	 * 	select_all (string) The string to use for the "Select All" option. Used for select && multiselect only.
	 * 	show_empty (bool) Whether or not to display empty categories.
	 * 	show_count (bool) Whether or not to display the category count.
	 * 	depth (int) The number of levels deap to show categories. Setting to 0 will show all levels.
	 * 	parent_id (array) An array of root parent category IDs to limit the list to.
	 * 	return (bool) Whether or not to return or echo the result.
	 *
	 * NOTE: The $atts array is passed to a number of private methods to output the categories.
	 *
	 * @access public
	 * @version 1.0
	 * @since 0.7.3
	 * @uses wp_parse_args()
	 * @param array $atts [optional]
	 * @return string
	 */
	public static function category( $atts = NULL ) {

		$defaults = array(
			'type'            => 'select',
			'group'           => FALSE,
			'default'         => __('Select Category', 'connections'),
			'show_select_all' => TRUE,
			'select_all'      => __('Show All Categories', 'connections'),
			'show_empty'      => TRUE,
			'show_count'      => FALSE,
			'depth'           => 0,
			'parent_id'       => array(),
			'return'          => FALSE
		);

		$atts = wp_parse_args( $atts, $defaults );

		switch ( $atts['type'] ) {
			case 'select':
				self::categorySelect( $atts );
				break;

			case 'multiselect':
				self::categorySelect( $atts );
				break;

			case 'radio':
				self::categoryInput( $atts );
				break;

			case 'checkbox':
				self::categoryInput( $atts );
				break;

			case 'link':
				self::categoryLink( $atts );
				break;
		}
	}

	/**
	 * The private function called by cnTemplate::category that outputs the select, multiselect; grouped and ungrouped.
	 *
	 * Accepted option for the $atts property are:
	 * 	type (string) The ouput type of the categories. Valid options options are: select || multiselect
	 * 	group (bool) Whether or not to create option groups using the root parent as the group label. Used for select && multiselect only.
	 * 	default (string) The default string to show as the first item in the list. Used for select && multiselect only.
	 * 	show_select_all (bool) Whether or not to show the "Select All" option. Used for select && multiselect only.
	 * 	select_all (string) The string to use for the "Select All" option. Used for select && multiselect only.
	 * 	show_empty (bool) Whether or not to display empty categories.
	 * 	show_count (bool) Whether or not to display the category count.
	 * 	depth (int) The number of levels deap to show categories. Setting to 0 will show all levels.
	 * 	parent_id (array) An array of root parent category IDs to limit the list to.
	 * 	return (bool) Whether or not to return or echo the result.
	 *
	 * @access private
	 * @version 1.0
	 * @since 0.7.3
	 * @uses get_query_var()
	 * @uses wp_parse_args()
	 * @param array $atts
	 * @return string
	 */
	private static function categorySelect( $atts ) {
		global $connections;
		$selected = '';

		// $selected = get_query_var('cn-cat-slug') ? get_query_var('cn-cat-slug') : array();

		if ( get_query_var( 'cn-cat' ) ) {
			$selected = get_query_var( 'cn-cat' );
		} elseif( get_query_var( 'cn-cat-slug' ) ) {
			$selected = get_query_var( 'cn-cat-slug' );
		}

		$level = 1;
		$out = '';

		$categories = $connections->retrieve->categories();

		$defaults = array(
			'type'            => 'select',
			'group'           => FALSE,
			'default'         => __( 'Select Category', 'connections' ),
			'show_select_all' => TRUE,
			'select_all'      => __( 'Show All Categories', 'connections' ),
			'show_empty'      => TRUE,
			'show_count'      => FALSE,
			'depth'           => 0,
			'parent_id'       => array(),
			'return'          => FALSE
		);

		$atts = wp_parse_args( $atts, $defaults );

		if ( ! is_array( $atts['parent_id'] ) ) {
			// Trim extra whitespace.
			$atts['parent_id'] = trim( str_replace( ' ', '', $atts['parent_id'] ) );

			// Convert to array.
			$atts['parent_id'] = explode( ',', $atts['parent_id'] );
		}

		$out .= "\n" . '<select class="cn-cat-select" name="' . ( ( $atts['type'] == 'multiselect' ) ? 'cn-cat[]' : 'cn-cat' ) . '"' . ( ( $atts['type'] == 'multiselect' ) ? ' MULTIPLE ' : '' ) . ( ( $atts['type'] == 'multiselect' ) ? '' : ' onchange="this.form.submit()" ' ) . 'data-placeholder="' . esc_attr($atts['default']) . '">';

		$out .= "\n" . '<option value=""></option>';

		if ( $atts['show_select_all'] ) $out .= "\n" . '<option value="">' . esc_attr( $atts['select_all'] ) . '</option>';

		foreach ( $categories as $key => $category ) {
			// Limit the category tree to only the supplied root parent categories.
			if ( ! empty( $atts['parent_id'] ) && ! in_array( $category->term_id, $atts['parent_id'] ) ) continue;

			// If grouping by root parent is enabled, open the optiongroup tag.
			if ( $atts['group'] && ! empty( $category->children ) )
				$out .= sprintf( '<optgroup label="%1$s">' , $category->name );

			// Call the recursive function to build the select options.
			$out .= self::categorySelectOption( $category, $level, $atts['depth'], $selected, $atts );

			// If grouping by root parent is enabled, close the optiongroup tag.
			if ( $atts['group'] && ! empty( $category->children ) )
				$out .= '</optgroup>' . "\n";
		}

		$out .= '</select>' . "\n";

		if ( $atts['type'] == 'multiselect' ) $out .= self::submit( array( 'return' => TRUE ) );

		if ( $atts['return'] ) return $out;
		echo $out;
	}

	/**
	 * The private recursive function to build the select options.
	 *
	 * Accepted option for the $atts property are:
	 * 	group (bool) Whether or not to create option groups using the root parent as the group label. Used for select && multiselect only.
	 * 	show_empty (bool) Whether or not to display empty categories.
	 * 	show_count (bool) Whether or not to display the category count.
	 *
	 * @param object $category A category object.
	 * @param int $level The current category level.
	 * @param int $depth The depth limit.
	 * @param array $selected An array of the selected category IDs.
	 * @param array $atts
	 * @return string
	 */
	private static function categorySelectOption( $category, $level, $depth, $selected, $atts ) {

		$out = '';

		$defaults = array(
			'group'      => FALSE,
			'show_empty' => TRUE,
			'show_count' => TRUE
		);

		$atts = wp_parse_args( $atts, $defaults );

		// The padding in px to indent descendant categories. The 7px is the default pad applied in the CSS which must be taken in to account.
		$pad = ( $level > 1 ) ? $level * 12 + 7 : 7;
		//$pad = str_repeat($atts['pad_char'], max(0, $level));

		// Set the option SELECT attribute if the category is one of the currently selected categories.
		if ( is_array( $selected ) ) {
			$strSelected = ( ( in_array( $category->term_id, $selected ) ) || ( in_array( $category->slug, $selected ) ) ) ? ' SELECTED ' : '';
		} else {
			$strSelected = ( ( $selected == $category->term_id ) || ( $selected == $category->slug ) ) ? ' SELECTED ' : '';
		}
		// $strSelected = $selected ? ' SELECTED ' : '';

		// Category count to be appended to the category name.
		$count = ( $atts['show_count'] ) ? ' (' . $category->count . ')' : '';

		// If option grouping is TRUE, show only the select option if it is a descendant. The root parent was used as the option group label.
		if ( ( $atts['group'] && $level > 1 ) && ( $atts['show_empty'] || ! empty( $category->count ) || ! empty( $category->children ) ) ) {
			$out .= sprintf('<option style="padding-left: %1$dpx !important" value="%2$s"%3$s>' . /*$pad .*/ $category->name . $count . '</option>' , $pad , $category->term_id , $strSelected );
		}
		// If option grouping is FALSE, show the root parent and descendant options.
		elseif ( ! $atts['group'] && ( $atts['show_empty'] || ! empty($category->count) || ! empty($category->children) ) ) {
			$out .= sprintf('<option style="padding-left: %1$dpx !important" value="%2$s"%3$s>' . /*$pad .*/ $category->name . $count . '</option>' , $pad , $category->term_id , $strSelected );
		}

		/*
		 * Only show the descendants based on the following criteria:
		 * 	- There are descendant categories.
		 * 	- The descendant depth is < than the current $level
		 *
		 * When descendant depth is set to 0, show all descendants.
		 * When descendant depth is set to < $level, call the recursive function.
		 */
		if ( ! empty( $category->children ) && ($depth <= 0 ? -1 : $level) < $depth ) {
			foreach ( $category->children as $child ) {
				$out .= self::categorySelectOption( $child, $level + 1, $depth, $selected, $atts );
			}
		}

		return $out;
	}

	/**
	 * The private function called by cnTemplate::category that outputs the radio && checkbox in a table layout.
	 * Each category root parent and its descendants are output in an unordered list.
	 *
	 * Accepted option for the $atts property are:
	 * 	type (string) The ouput type of the categories. Valid options options are: select || multiselect
	 * 	show_empty (bool) Whether or not to display empty categories.
	 * 	show_count (bool) Whether or not to display the category count.
	 * 	depth (int) The number of levels deap to show categories. Setting to 0 will show all levels.
	 * 	parent_id (array) An array of root parent category IDs to limit the list to.
	 * 	layout (string) The layout to be used for rendering the categories. Valid options are: list || table
	 * 	columns (int) The number of columns in the table.
	 * 	return (bool) Whether or not to return or echo the result.
	 *
	 * @access private
	 * @version 1.0
	 * @since 0.7.3
	 * @uses get_query_var()
	 * @uses wp_parse_args()
	 * @param array $atts
	 * @return string
	 */
	private function categoryInput( $atts = NULL ) {
		global $connections;

		$selected = ( get_query_var('cn-cat') ) ? get_query_var('cn-cat') : array();
		$categories = array();
		$level = 0;
		$out = '';
		$trClass = 'alternate';

		$categories = $connections->retrieve->categories();

		$defaults = array(
			'type'       => 'radio',
			'show_empty' => TRUE,
			'show_count' => TRUE,
			'depth'      => 0,
			'parent_id'  => array(),
			'layout'     => 'list',
			'columns'    => 3,
			'return'     => FALSE
		);

		$atts = wp_parse_args( $atts, $defaults );


		if ( ! empty( $atts['parent_id'] ) && ! is_array( $atts['parent_id'] ) ) {
			// Trim extra whitespace.
			$atts['parent_id'] = trim( str_replace( ' ', '', $atts['parent_id'] ) );

			// Convert to array.
			$atts['parent_id'] = explode( ',', $atts['parent_id'] );
		}

		foreach ( $categories as $key => $category ) {
			// Remove any empty root parent categories so the table builds correctly.
			if ( ! $atts['show_empty'] && ( empty($category->count ) && empty( $category->children ) ) ) unset( $categories[ $key ] );

			// Limit the category tree to only the supplied root parent categories.
			if ( ! empty( $atts['parent_id'] ) && ! in_array( $category->term_id, $atts['parent_id'] ) ) unset( $categories[ $key ] );
		}

		switch ( $atts['layout'] ) {

			case 'table':

				// Build the table grid.
				$table = array();
				$rows = ceil(count( $categories ) / $atts['columns'] );
				$keys = array_keys( $categories );

				for ( $row = 1; $row <= $rows; $row++ )
					for ( $col = 1; $col <= $atts['columns']; $col++ )
						$table[$row][$col] = array_shift($keys);

				$out .= '<table cellspacing="0" cellpadding="0" class="cn-cat-table">';
					$out .= '<tbody>';

					foreach ( $table as $row => $cols ) {

						$trClass = ( $trClass == 'alternate' ) ? '' : 'alternate';

						$out .= '<tr' . ( $trClass ? ' class="' . $trClass . '"' : '' ) . '>';

						foreach ( $cols as $col => $key ) {

							// When building the table grid, NULL will be the result of the array_shift when it runs out of $keys.
							if ( $key === NULL ) continue;

							$tdClass = array('cn-cat-td');
							if ( $row == 1 ) $tdClass[] = '-top';
							if ( $row == $rows ) $tdClass[] = '-bottom';
							if ( $col == 1 ) $tdClass[] = '-left';
							if ( $col == $atts['columns'] ) $tdClass[] = '-right';

							$out .= '<td class="' . implode( '', $tdClass ) . '" style="width: ' . floor( 100 / $atts['columns'] ) . '%">';

								$out .= '<ul class="cn-cat-tree">';

									$out .= self::categoryInputOption( $categories[ $key ], $level + 1, $atts['depth'], $selected, $atts);

								$out .= '</ul>';

							$out .= '</td>';
						}

						$out .= '</tr>';
					}

					$out .= '</tbody>';
				$out .= '</table>';

				break;

			case 'list':

				$out .= '<ul class="cn-cat-tree">';

				foreach ( $categories as $key => $category ) {

					// Limit the category tree to only the supplied root parent categories.
					if ( ! empty( $atts['parent_id'] ) && ! in_array( $category->term_id, $atts['parent_id'] ) ) continue;

					// Call the recursive function to build the select options.
					$out .= self::categoryInputOption( $categories[ $key ], $level + 1, $atts['depth'], $selected, $atts);
				}

				$out .= '</ul>';

				break;
		}


		if ( $atts['return']) return $out;
		echo $out;
	}

	/**
	 * The private recursive function to build the list item.
	 *
	 * Accepted option for the $atts property are:
	 * 	type (string)
	 * 	show_empty (bool) Whether or not to display empty categories.
	 * 	show_count (bool) Whether or not to display the category count.
	 *
	 * @param object $category A category object.
	 * @param int $level The current category level.
	 * @param int $depth The depth limit.
	 * @param array $selected An array of the selected category IDs.
	 * @param array $atts
	 * @return string
	 */
	private function categoryInputOption( $category, $level, $depth, $selected, $atts ) {

		$out = '';

		$defaults = array(
			'type'       => 'radio',
			'show_empty' => TRUE,
			'show_count' => TRUE
		);

		$atts = wp_parse_args($atts, $defaults);

		if ( $atts['show_empty'] || ! empty( $category->count ) || ! empty( $category->children ) ) {

			$count = ( $atts['show_count'] ) ? ' (' . $category->count . ')' : '';

			$out .= '<li class="cn-cat-parent">';

			$out .= sprintf( '<input type="%1$s" class="cn-radio" id="%2$s" name="cn-cat" value="%3$s" %4$s/>', $atts['type'], $category->slug, $category->term_id, checked( $selected, $category->term_id, FALSE ) );
			$out .= sprintf( '<label for="%1$s"> %2$s</label>', $category->slug, $category->name . $count );

			/*
			 * Only show the descendants based on the following criteria:
			 * 	- There are descendant categories.
			 * 	- The descendant depth is < than the current $level
			 *
			 * When descendant depth is set to 0, show all descendants.
			 * When descendant depth is set to < $level, call the recursive function.
			 */
			if ( ! empty( $category->children ) && ( $depth <= 0 ? -1 : $level ) < $depth ) {

				$out .= '<ul class="cn-cat-children">';

				foreach ( $category->children as $child ) {
					$out .= self::categoryInputOption( $child, $level + 1, $depth, $selected, $atts );
				}

				$out .= '</ul>';
			}

			$out .= '</li>';
		}

		return $out;
	}

	/**
	 * The private function called by cnTemplate::category that outputs the category links in two formats:
	 *  - A table layout with one cell per root parent category containing all descendants in an unordered list.
	 *  - An unordered list.
	 *
	 * Accepted option for the $atts property are:
	 * 	show_empty (bool) Whether or not to display empty categories.
	 * 	show_count (bool) Whether or not to display the category count.
	 * 	depth (int) The number of levels deap to show categories. Setting to 0 will show all levels.
	 * 	parent_id (array) An array of root parent category IDs to limit the list to.
	 * 	layout (string) The layout to be used for rendering the categories. Valid options are: list || table
	 * 	columns (int) The number of columns in the table.
	 * 	return (bool) Whether or not to return or echo the result.
	 *
	 * @access private
	 * @version 1.0
	 * @since 0.7.3
	 * @uses get_query_var()
	 * @uses wp_parse_args()
	 * @param array $atts
	 * @return string
	 */
	private function categoryLink( $atts = NULL ) {
		global $connections;

		$categories = array();
		$level = 0;
		$out = '';
		$trClass = 'alternate';

		$categories = $connections->retrieve->categories();

		$defaults = array(
			'show_empty' => TRUE,
			'show_count' => TRUE,
			'depth'      => 0,
			'parent_id'  => array(),
			'layout'     => 'list',
			'columns'    => 3,
			'return'     => FALSE
		);

		$atts = wp_parse_args( $atts, $defaults );

		if ( ! empty( $atts['parent_id'] ) && ! is_array( $atts['parent_id'] ) ) {
			// Trim extra whitespace.
			$atts['parent_id'] = trim( str_replace(' ', '', $atts['parent_id'] ) );

			// Convert to array.
			$atts['parent_id'] = explode( ',', $atts['parent_id'] );
		}

		foreach ( $categories as $key => $category ) {
			// Remove any empty root parent categories so the table builds correctly.
			if ( ! $atts['show_empty'] && ( empty( $category->count ) && empty( $category->children ) ) ) unset( $categories[ $key ] );

			// Limit the category tree to only the supplied root parent categories.
			if ( ! empty( $atts['parent_id'] ) && ! in_array( $category->term_id, $atts['parent_id'] ) ) unset( $categories[ $key ] );
		}

		switch ( $atts['layout'] ) {

			case 'table':

				// Build the table grid.
				$table = array();
				$rows = ceil(count( $categories ) / $atts['columns'] );
				$keys = array_keys( $categories );
				for ( $row = 1; $row <= $rows; $row++ )
					for ( $col = 1; $col <= $atts['columns']; $col++ )
						$table[ $row ][ $col ] = array_shift( $keys );

				$out .= '<table cellspacing="0" cellpadding="0" class="cn-cat-table">';
					$out .= '<tbody>';

					foreach ( $table as $row => $cols ) {
						$trClass = ( $trClass == 'alternate' ) ? '' : 'alternate';

						$out .= '<tr' . ( $trClass ? ' class="' . $trClass . '"' : '' ) . '>';

						foreach ( $cols as $col => $key ) {
							// When building the table grid, NULL will be the result of the array_shift when it runs out of $keys.
							if ( $key === NULL ) continue;

							$tdClass = array('cn-cat-td');
							if ( $row == 1 ) $tdClass[] = '-top';
							if ( $row == $rows ) $tdClass[] = '-bottom';
							if ( $col == 1 ) $tdClass[] = '-left';
							if ( $col == $atts['columns'] ) $tdClass[] = '-right';

							$out .= '<td class="' . implode( '', $tdClass) . '" style="width: ' . floor( 100 / $atts['columns'] ) . '%">';

								$out .= '<ul class="cn-cat-tree">';

									$out .= self::categoryLinkDescendant( $categories[ $key ], $level + 1, $atts['depth'], array(), $atts );

								$out .= '</ul>';

							$out .= '</td>';
						}

						$out .= '</tr>';
					}

					$out .= '</tbody>';
				$out .= '</table>';

				break;

			case 'list':

				$out .= '<ul class="cn-cat-tree">';

				foreach ( $categories as $key => $category )
				{
					// Limit the category tree to only the supplied root parent categories.
					if ( ! empty( $atts['parent_id'] ) && ! in_array( $category->term_id, $atts['parent_id'] ) ) continue;

					// Call the recursive function to build the select options.
					$out .= self::categoryLinkDescendant( $category, $level + 1, $atts['depth'], array(), $atts );
				}

				$out .= '</ul>';

				break;
		}

		if ( $atts['return'] ) return $out;
		echo $out;
	}

	/**
	 * The private recursive function to build the category link item.
	 *
	 * Accepted option for the $atts property are:
	 * 	type (string)
	 * 	show_empty (bool) Whether or not to display empty categories.
	 * 	show_count (bool) Whether or not to display the category count.
	 *
	 * @param object $category A category object.
	 * @param int $level The current category level.
	 * @param int $depth The depth limit.
	 * @param array $slug An array of the category slugs to be used to build the permalink.
	 * @param array $atts
	 * @return string
	 */
	private function categoryLinkDescendant ( $category, $level, $depth, $slug, $atts ) {
		global $wp_rewrite, $connections;

		$out = '';

		$defaults = array(
			'show_empty' => TRUE,
			'show_count' => TRUE
		);

		$atts = wp_parse_args($atts, $defaults);

		if ( $atts['show_empty'] || ! empty( $category->count ) || ! empty ( $category->children ) ) {

			$count = ( $atts['show_count'] ) ? ' (' . $category->count . ')' : '';

			/*
			 * Determine of pretty permalink is enabled.
			 * If it is, add the category slug to the array which will be imploded to be used to build the URL.
			 * If it is not, set the $slug to the category term ID.
			 */
			if ( $wp_rewrite->using_permalinks() ) {
				$slug[] = $category->slug;
			} else {
				$slug = array( $category->slug );
			}

			/*
			 * Get tge current category from the URL / query string.
			 */
			if ( get_query_var( 'cn-cat-slug' ) ) {

				// Category slug
				$queryCategorySlug = get_query_var( 'cn-cat-slug' );
				if ( ! empty( $queryCategorySlug ) ) {
					// If the category slug is a descendant, use the last slug from the URL for the query.
					$queryCategorySlug = explode( '/' , $queryCategorySlug );

					if ( isset( $queryCategorySlug[ count( $queryCategorySlug )-1 ] ) ) $currentCategory = $queryCategorySlug[ count( $queryCategorySlug )-1 ];
				}

			} elseif ( get_query_var( 'cn-cat' ) ) {

				$currentCategory = get_query_var( 'cn-cat' );

			} else {

				$currentCategory = '';

			}

			$out .= '<li class="cat-item cat-item-' . $category->term_id . ( $currentCategory == $category->slug || $currentCategory == $category->term_id ? ' current-cat' : '' ) . ' cn-cat-parent">';

			// Create the permalink anchor.
			$out .= $connections->url->permalink( array(
				'type'   => 'category',
				'slug'   => implode( '/' , $slug ),
				'title'  => $category->name,
				'text'   => $category->name . $count,
				'return' => TRUE
				)
			);

			/*
			 * Only show the descendants based on the following criteria:
			 * 	- There are descendant categories.
			 * 	- The descendant depth is < than the current $level
			 *
			 * When descendant depth is set to 0, show all descendants.
			 * When descendant depth is set to < $level, call the recursive function.
			 */
			if ( ! empty( $category->children ) && ( $depth <= 0 ? -1 : $level ) < $depth ) {

				$out .= '<ul class="children cn-cat-children">';

				foreach ( $category->children as $child ) {
					$out .= self::categoryLinkDescendant( $child, $level + 1, $depth, $slug, $atts );
				}

				$out .= '</ul>';
			}

			$out .= '</li>';
		}

		return $out;
	}

}