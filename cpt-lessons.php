<?php
/*
Plugin Name: Lesson CPT
Description: This plugin handles the registration of Custom Post Types, Taxonomy and Meta Data.
Plugin URI: http://www.pixeljar.com
Author: Pixel Jar
Author URI: http://www.pixeljar.com
Version: 1.0
License: GPL2
Text Domain: cpt
Domain Path: /lang

	Copyright (C) Apr 29, 2015  Pixel Jar  info@pixeljar.com

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License, version 2, as
	published by the Free Software Foundation.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

/**
 * BabyPips School Data Architecture Class
 *
 * Registers and defines all custom post types, taxonomies, and meta data
 */
class CPT_School extends CPT {

	const CPT_DATA_SLUG = 'cpt-lesson';
	const CPT_URL_SLUG = 'school';

	const TAX_DATA_SLUG = 'cpt-section';
	const TAX_URL_SLUG = 'school';

	function __construct() {

		// Kick off the parent construct
		parent::__construct();

		// Fix permalink strings
		add_filter( 'post_type_link', array( &$this, 'fix_post_type_link' ), 10, 2 );

		// Adds a rewrite rule for the single post to fix issues with same section and
		// post title slugs
		add_action( 'rewrite_rules_array', array( &$this, 'fix_rewrite_rules' ) );

	}

	/**
	 * Initializes School Post Type and Section Taxonomy
	 */
	public static function initialize_architecture() {

		$tax_args = array(
			'labels'            => array(
				'name'                  => _x( 'Sections', 'Taxonomy plural name', 'cpt' ),
				'singular_name'         => _x( 'Section', 'Taxonomy singular name', 'cpt' ),
				'search_items'          => __( 'Search Sections', 'cpt' ),
				'popular_items'         => __( 'Popular Sections', 'cpt' ),
				'all_items'             => __( 'All Sections', 'cpt' ),
				'parent_item'           => __( 'Parent Section', 'cpt' ),
				'parent_item_colon'     => __( 'Parent Section', 'cpt' ),
				'edit_item'             => __( 'Edit Section', 'cpt' ),
				'update_item'           => __( 'Update Section', 'cpt' ),
				'add_new_item'          => __( 'Add New Section', 'cpt' ),
				'new_item_name'         => __( 'New Section Name', 'cpt' ),
				'add_or_remove_items'   => __( 'Add or remove Sections', 'cpt' ),
				'choose_from_most_used' => __( 'Choose from most used cpt', 'cpt' ),
				'menu_name'             => __( 'Sections', 'cpt' ),
			),
			'public'            => true,
			'show_in_nav_menus' => true,
			'show_admin_column' => true,
			'hierarchical'      => true,
			'show_tagcloud'     => true,
			'show_ui'           => true,
			'query_var'         => self::TAX_DATA_SLUG,
			'rewrite'           => array(
				'slug'          => self::TAX_URL_SLUG,
				'hierarchical'  => true,
			),
			'query_var'         => true,
		);
		register_taxonomy( self::TAX_DATA_SLUG, array(), $tax_args );

		$post_type_args = array(
			'labels'              => array(
				'name'                => _x( 'Lessons', 'Post type plural name', 'cpt' ),
				'singular_name'       => _x( 'Lesson', 'Post type singular name', 'cpt' ),
				'all_items'           => __( 'Lessons', 'cpt' ),
				'add_new'             => __( 'Add New Lesson', 'cpt' ),
				'add_new_item'        => __( 'Add New Lesson', 'cpt' ),
				'edit_item'           => __( 'Edit Lesson', 'cpt' ),
				'new_item'            => __( 'New Lesson', 'cpt' ),
				'view_item'           => __( 'View Lesson', 'cpt' ),
				'search_items'        => __( 'Search Lessons', 'cpt' ),
				'not_found'           => __( 'No Lessons found', 'cpt' ),
				'not_found_in_trash'  => __( 'No Lessons found in Trash', 'cpt' ),
				'menu_name'           => __( 'School', 'cpt' ),
			),
			'hierarchical'        => false,
			'description'         => '',
			'taxonomies'          => array( self::TAX_DATA_SLUG ),
			'public'              => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'show_in_admin_bar'   => true,
			'menu_position'       => null,
			'menu_icon'           => 'dashicons-welcome-learn-more',
			'show_in_nav_menus'   => true,
			'publicly_queryable'  => true,
			'exclude_from_search' => false,
			'has_archive'         => self::CPT_URL_SLUG,
			'query_var'           => self::CPT_DATA_SLUG,
			'can_export'          => true,
			'rewrite'             => array(
				'slug'       => self::CPT_URL_SLUG . '/%' . self::TAX_DATA_SLUG. '%',
				'with_front' => false,
			),
			'capability_type'     => 'post',
			'supports'            => array(
				'title',
				'editor',
				'thumbnail',
				'excerpt',
				'revisions',
				'page-attributes',
			),
		);
		register_post_type( self::CPT_DATA_SLUG, $post_type_args );

		parent::initialize_architecture();

	} // initialize_architecture

	/**
	 * Adds the hierarchical sections to the post link
	 * @param  string  $post_link the link with the %self::TAX_DATA_SLUG% placeholder in it
	 * @param  integer $id        the ID of the post in question
	 * @return string             the modified pernalink
	 */
	function fix_post_type_link( $post_link = '', $id = 0 ) {

		// Get the post
		$post = get_post( $id );

	 	// Not what we're looking for
		if (
			is_wp_error( $post ) ||
			self::CPT_DATA_SLUG != $post->post_type ||
			empty( $post->post_name )
		) {
			return $post_link;
		}

		// Get the section
		$terms = get_the_terms( $post->ID, self::TAX_DATA_SLUG );

		// No appropriate terms found
		if ( is_wp_error( $terms ) || ! $terms ) {
			return $post_link;
		}

		$section_obj = array_pop( $terms );
		$section_slugs = array();

		$ancestors = get_ancestors( $section_obj->term_id, self::TAX_DATA_SLUG );
		foreach ( $ancestors as $ancestor_id ) {

			$ancestor = get_term( $ancestor_id, self::TAX_DATA_SLUG );
			$section_slugs[] = $ancestor->slug;

		}
		$section_slugs[] = $section_obj->slug;

		return untrailingslashit(
			str_replace(
				'%' . self::TAX_DATA_SLUG . '%',
				implode( '/', $section_slugs ),
				$post_link
			)
		) . '.html';

	} // fix_post_type_link

	/**
	 * Fixes same Section/Lesson name rewrite issue.
	 * @param  array $rules rewrite rules array
	 * @return array        our rewrite rule on top, the old rewrite rules below
	 */
	function fix_rewrite_rules( $rules = array() ) {

		$new_rules = array();
		$new_rules[self::CPT_URL_SLUG . '/(.+?)/([^/]+)\.html$'] = sprintf(
			'index.php?post_type=%s&name=$matches[2]',
			self::CPT_DATA_SLUG
		);
		return $new_rules + $rules;

	} // fix_rewrite_rules

} // CPT_School

/**
 * BabyPips Data Architecture Base Class
 */
abstract class CPT {

	const PERMALINK_FLAG = 'cpt-setup-permalinks';

	/**
	 * Hook Setup
	 */
	public function __construct() {

		// Initialize CPTs & Taxonomies
		add_action( 'init', array( &$this, 'initialize_architecture' ), 0 );

		// Set the permalinks
		add_action( 'init', array( __CLASS__, 'initialize_permalinks' ), 100 );

	} // __construct

	/**
	 * Potentially sets a flag to notify the system that permalinks need to be updated.
	 */
	public static function initialize_architecture() {

		// Retrieve the permalink flushing_status
		$is_flushed = get_option( self::PERMALINK_FLAG, 0 );

		// Checks if the permalinks have been flushed or WP_DEBUG is on
		if ( ! $is_flushed || true === WP_DEBUG ) {

			// Flag the permalinks to be updated
			update_option( self::PERMALINK_FLAG, 0 );

		} // endif

	} // initialize_architecture

	/**
	 * Checks for flag indicating that permalinks need to be flushed or not. This only happens the
	 * first time the CPTs have been created or if WP_DEBUG is on.
	 */
	public static function initialize_permalinks() {

		// Retrieve the permalink flushing_status
		$is_flushed = get_option( self::PERMALINK_FLAG, 0 );

		// Checks if the permalinks have been flushed
		if ( ! $is_flushed ) {

			// Flush the rewrite rules
			flush_rewrite_rules();

			// Don't flush the permalinks again
			update_option( self::PERMALINK_FLAG, 1 );

		} // endif

	} // initialize_permalinks

} // CPT

new CPT_School;
