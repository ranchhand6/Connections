<?php

/**
 * Class for working with a category object.
 *
 * @package     Connections
 * @subpackage  Category
 * @copyright   Copyright (c) 2013, Steven A. Zahm
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       unknown
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

class cnCategory {
	private $id;
	private $name;
	private $slug;
	private $termGroup;
	private $taxonomy;
	private $description;
	private $parent;
	private $count;
	private $children;

	/**
	 * The cnFormmatting class.
	 * @var (object)
	 */
	private $format;

	/**
	 * The cnValidate class.
	 * @var (object)
	 */
	private $validate;

	function __construct( $data = NULL ) {
		if ( isset( $data ) ) {
			if ( isset( $data->term_id ) ) $this->id = $data->term_id;
			if ( isset( $data->name ) ) $this->name = $data->name;
			if ( isset( $data->slug ) ) $this->slug = $data->slug;
			if ( isset( $data->term_group ) ) $this->termGroup = $data->term_group;
			if ( isset( $data->taxonomy ) ) $this->taxonomy = $data->taxonomy;
			if ( isset( $data->description ) ) $this->description = $data->description;
			if ( isset( $data->parent ) ) $this->parent = $data->parent;
			if ( isset( $data->count ) ) $this->count = $data->count;
			if ( isset( $data->children ) ) $this->children = $data->children;
		}

		// Load the validation class.
		$this->validate = new cnValidate();

		// Load the formatting class for sanitizing the get methods.
		$this->format = new cnFormatting();
	}

	/**
	 * Returns $children.
	 *
	 * @see cnCategory::$children
	 */
	public function getChildren() {
		return $this->children;
	}

	/**
	 * Sets $children.
	 *
	 * @param object  $children
	 * @see cnCategory::$children
	 */
	public function setChildren( $children ) {
		$this->children = $children;
	}

	/**
	 * Returns $count.
	 *
	 * @see cnCategory::$count
	 */
	public function getCount() {
		return $this->count;
	}

	/**
	 * Sets $count.
	 *
	 * @param object  $count
	 * @see cnCategory::$count
	 */
	public function setCount( $count ) {
		$this->count = $count;
	}

	/**
	 * Returns $description.
	 *
	 * @see cnCategory::$description
	 */
	public function getDescription() {
		return $this->format->sanitizeString( $this->description, TRUE );
	}

	/**
	 * Sets $description.
	 *
	 * @param object  $description
	 * @see cnCategory::$description
	 */
	public function setDescription( $description ) {
		$this->description = $description;
	}

	/**
	 * Echo or returns the category description.
	 *
	 * Registers the global $wp_embed because the run_shortcode method needs
	 * to run before the do_shortcode function for the [embed] shortcode to fire.
	 *
	 * Filters:
	 *   cn_output_default_atts_cat_desc
	 *
	 * @access public
	 * @since 0.7.8
	 * @uses apply_filters()
	 * @uses run_shortcode()
	 * @uses do_shortcode()
	 * @param array $atts [optional]
	 * @return (string)
	 */
	public function getDescriptionBlock( $atts = array() ) {
		global $wp_embed;

		$defaults = array(
			'container_tag' => 'div',
			'before'        => '',
			'after'         => '',
			'return'        => FALSE
		);

		$defaults = apply_filters( 'cn_output_default_atts_cat_desc' , $defaults );

		$atts = $this->validate->attributesArray( $defaults, $atts );

		$out = __( $wp_embed->run_shortcode( $this->getDescription() ) );

		$out = do_shortcode( $out );

		$out = sprintf( '<%1$s class="cn-cat-description">%2$s</%1$s>',
				$atts['container_tag'],
				$out
			);

		if ( $atts['return'] ) return ( "\n" . ( empty( $atts['before'] ) ? '' : $atts['before'] ) ) . $out . ( ( empty( $atts['after'] ) ? '' : $atts['after'] ) ) . "\n";
		echo ( "\n" . ( empty( $atts['before'] ) ? '' : $atts['before'] ) ) . $out . ( ( empty( $atts['after'] ) ? '' : $atts['after'] ) ) . "\n";
	}

	/**
	 * Create excerpt from the supplied text. Default is the description.
	 *
	 * Filters:
	 *   cn_cat_excerpt_length => change the default excerpt length of 55 words.
	 *   cn_cat_excerpt_more  => change the default more string of &hellip;
	 *   cn_trim_cat_excerpt  => change returned string
	 *
	 * @access public
	 * @since 0.7.8
	 * @param (string)  $atts [optional]
	 * @param (string)  $text [optional]
	 * @return (string)
	 */
	public function getExcerpt( $atts = array(), $text = NULL ) {

		$defaults = array(
			'length' => apply_filters( 'cn_cat_excerpt_length', 55 ),
			'more'   => apply_filters( 'cn_cat_excerpt_more', '&hellip;' )
		);

		$atts = $this->validate->attributesArray( $defaults, $atts );

		$text = empty( $text ) ? $this->getDescription() : $this->format->sanitizeString( $text, FALSE );

		$words = preg_split( "/[\n\r\t ]+/", $text, $atts['length'] + 1, PREG_SPLIT_NO_EMPTY );

		if ( count( $words ) > $atts['length'] ) {

			array_pop( $words );
			$text = implode( ' ', $words ) . $atts['more'];

		} else {

			$text = implode( ' ', $words );
		}

		return apply_filters( 'cn_trim_cat_excerpt', $text );
	}

	/**
	 * Returns $id.
	 *
	 * @see cnCategory::$id
	 */
	public function getId() {
		return $this->id;
	}

	/**
	 * Sets $id.
	 *
	 * @param object  $id
	 * @see cnCategory::$id
	 */
	public function setId( $id ) {
		$this->id = $id;
	}

	/**
	 * Returns $name.
	 *
	 * @see cnCategory::$name
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * Sets $name.
	 *
	 * @param object  $name
	 * @see cnCategory::$name
	 */
	public function setName( $name ) {
		$this->name = $name;
	}

	/**
	 * Returns $parent.
	 *
	 * @see cnCategory::$parent
	 */
	public function getParent() {
		return $this->parent;
	}

	/**
	 * Sets $parent.
	 *
	 * @param object  $parent
	 * @see cnCategory::$parent
	 */
	public function setParent( $parent ) {
		$this->parent = $parent;
	}

	/**
	 * Returns $slug.
	 *
	 * @see cnCategory::$slug
	 */
	public function getSlug() {
		return $this->slug;
	}

	/**
	 * Sets $slug.
	 *
	 * @param object  $slug
	 * @see cnCategory::$slug
	 */
	public function setSlug( $slug ) {
		$this->slug = $slug;
	}

	/**
	 * Returns $taxonomy.
	 *
	 * @see cnCategory::$taxonomy
	 */
	public function getTaxonomy() {
		return $this->taxonomy;
	}

	/**
	 * Sets $taxonomy.
	 *
	 * @param object  $taxonomy
	 * @see cnCategory::$taxonomy
	 */
	public function setTaxonomy( $taxonomy ) {
		$this->taxonomy = $taxonomy;
	}

	/**
	 * Returns $termGroup.
	 *
	 * @see cnCategory::$termGroup
	 */
	public function getTermGroup() {
		return $this->termGroup;
	}

	/**
	 * Sets $termGroup.
	 *
	 * @param object  $termGroup
	 * @see cnCategory::$termGroup
	 */
	public function setTermGroup( $termGroup ) {
		$this->termGroup = $termGroup;
	}

	/**
	 * Saves the category to the database via the cnTerm class.
	 *
	 * @return bool
	 */
	public function save() {

		$args = array(
			'slug'        => $this->slug,
			'description' => $this->description,
			'parent'      => $this->parent,
		);

		$result = cnTerm::insert( $this->name, 'category', $args );

		if ( is_wp_error( $result ) ) {

			cnMessage::set( 'error', $result->get_error_message() );
			return FALSE;

		} else {

			cnMessage::set( 'success', 'category_added' );
			return TRUE;
		}

	}

	/**
	 * Updates the category to the database via the cnTerm class.
	 *
	 * @access public
	 * @since unknown
	 * @return bool
	 */
	public function update() {

		$args = array(
			'name'        => $this->name,
			'slug'        => $this->slug,
			'description' => $this->description,
			'parent'      => $this->parent,
		);

		// Make sure the category isn't being set to itself as a parent.
		if ( $this->id === $this->parent ) {

			cnMessage::set( 'error', 'category_self_parent' );
			return FALSE;
		}

		// @todo Add option for user to set the default category, which should not be able to be deleted.
		//$defaults['default'] = get_option( 'cn_default_category' );

		// Temporarily hard code the default category to the Uncategorized category
		// and ensure it can not be deleted. This should be removed when the default
		// category can be set by the user.
		$default_category = cnTerm::getBy( 'slug', 'uncategorized', 'category' );
		$defaults['default'] = $default_category->term_id;

		// Do not change the default category.
		// This should be able to be removed after the user configurable default category is implemented.
		if ( $this->id == $default_category->term_id ) {

			cnMessage::set( 'error', 'category_update_uncategorized' );
			return FALSE;
		}

		$result = cnTerm::update( $this->id, 'category', $args );

		if ( is_wp_error( $result ) ) {

			cnMessage::set( 'error', $result->get_error_message() );
			return FALSE;

		} else {

			cnMessage::set( 'success', 'category_updated' );
			return TRUE;
		}

	}

	/**
	 * Deletes the category from the database via the cnTerm class.
	 *
	 * @return bool The success or error message.
	 */
	public function delete() {

		// @todo Add option for user to set the default category, which should not be able to be deleted.
		//$defaults['default'] = get_option( 'cn_default_category' );

		// Temporarily hard code the default category to the Uncategorized category
		// and ensure it can not be deleted. This should be removed when the default
		// category can be set by the user.
		$default_category = cnTerm::getBy( 'slug', 'uncategorized', 'category' );
		$defaults['default'] = $default_category->term_id;

		// Do not change the default category.
		// This should be able to be removed after the user configurable default category is implemented.
		if ( $this->id == $default_category->term_id ) {

			cnMessage::set( 'error', 'category_delete_uncategorized' );
			return FALSE;
		}

		$result = cnTerm::delete( $this->id, 'category' );

		if ( is_wp_error( $result ) ) {

			cnMessage::set( 'error', $result->get_error_message() );
			return FALSE;

		} else {

			cnMessage::set( 'success', 'category_deleted' );
			return TRUE;
		}

	}
}
