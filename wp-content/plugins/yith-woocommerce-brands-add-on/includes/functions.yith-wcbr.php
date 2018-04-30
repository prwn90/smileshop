<?php
/**
 * Utility functions
 *
 * @author Your Inspiration Themes
 * @package YITH WooCommerce Brands
 * @version 1.0.0
 */

/*
 * This file belongs to the YIT Framework.
 *
 * This source file is subject to the GNU GENERAL PUBLIC LICENSE (GPL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.gnu.org/licenses/gpl-3.0.txt
 */

if ( ! defined( 'YITH_WCBR' ) ) {
	exit;
} // Exit if accessed directly

if( ! function_exists( 'yith_wcbr_is_valid_url' ) ){
	/**
	 * Simple check for validating a URL, it must start with http:// or https://.
	 * and pass FILTER_VALIDATE_URL validation.
	 * 
	 * @param  string $url
	 * @return bool
	 * @since 1.0.7
	 */
	function yith_wcbr_is_valid_url( $url ){
		
		if( function_exists( 'wc_is_valid_url' ) ){
			return wc_is_valid_url( $url );
		}
		
		// Must start with http:// or https://
		if ( 0 !== strpos( $url, 'http://' ) && 0 !== strpos( $url, 'https://' ) ) {
			return false;
		}

		// Must pass validation
		if ( ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
			return false;
		}

		return true;
	}
}

if( ! function_exists( 'yith_wcbr_get_terms' ) ){
	/**
	 * Wrapper for get_terms function
	 * Prior 4.5 -> get_terms( taxonomy, args )
	 * After 4.5 -> get_terms( args )
	 * 
	 * @params $taxonomy string|array Taxonomy slug, or list of them
	 * @params $args mixed Arguments for the query
	 * @return mixed List of \WP_Terms or \WP_Error
	 * @since 1.0.7
	 */
	function yith_wcbr_get_terms( $taxonomy, $args ){
		global $wp_version;
		$terms = array();

		// is no orderby param is set, user order
		if( ! isset( $args['orderby'] ) || $args['orderby'] == 'none' ){
			$args['meta_query'] = isset( $args['meta_query'] ) ? $args['meta_query'] : array();
			$args['meta_query'][] = array(
				'relation' => 'OR',
				array(
					'key' => 'order',
					'compare' => 'EXISTS'
				),
				array(
					'key' => 'order',
					'compare' => 'NOT EXISTS'
				)
			);
			$args['orderby'] = 'meta_value_num';
		}
		
		if( version_compare( $wp_version, '4.5', '<' ) ){
			$terms = get_terms( $taxonomy, $args );
		}
		else{
			$args = array_merge( $args, array(
				'taxonomy' => $taxonomy
			) );
			
			$terms = get_terms( $args );
		}
		
		return $terms;
	}
}

if( ! function_exists( 'yith_wcbr_get_template' ) ){
	/**
	 * Get template for Brands plugin
	 *
	 * @param $filename string Template name (with or without extension)
	 * @param $args mixed Array of params to use in the template
	 * @param $section string Subdirectory where to search
	 */
	function yith_wcbr_get_template( $filename, $args = array(), $section = '' ){
		$ext = strpos( $filename, '.php' ) === false ? '.php' : '';

		$template_name      = $section . '/' . $filename . $ext;
		$template_path      = WC()->template_path() . 'yith-wcbr/';
		$default_path       = YITH_WCBR_DIR . 'templates/';

		if( defined( 'YITH_WCBR_PREMIUM' ) ){
			$premium_template   = str_replace( '.php', '-premium.php', $template_name );
			$located_premium    = wc_locate_template( $premium_template, $template_path, $default_path );
			$template_name      = file_exists( $located_premium ) ?  $premium_template : $template_name;
		}

		wc_get_template( $template_name, $args, $template_path, $default_path );
	}
}

if( ! function_exists( 'yith_wcbr_add_slider_post_class' ) ){
	/**
	 * Add classes to posts for sliders
	 *
	 * @param $classes mixed Array of available class
	 *
	 * @return mixed Filtered array of classes
	 * @since 1.0.0
	 */
	function yith_wcbr_add_slider_post_class( $classes ){
		$classes[] = 'swiper-slide';

		return $classes;
	}
}

if( ! function_exists( 'yith_wcbr_get_term_meta' ) ){
	/**
	 * Get term meta (wrapper added to handle backward compatibility with WC < 2.6 and WP < 4.4)
	 * @param $term_id int
	 * @param $key string
	 * @param $single bool (default true )
	 *
	 * @return mixed meta value
	 * @since 1.0.7
	 */
	function yith_wcbr_get_term_meta( $term_id, $key, $single = true ){
		if ( version_compare( preg_replace( '/-beta-([0-9]+)/', '', WC()->version ), '2.6', '>=' ) && function_exists( 'get_term_meta' ) ) {
			return get_term_meta( $term_id, $key, $single );
		}
		else{
			return get_metadata( 'woocommerce_term', $term_id, $key, $single );
		}
	}
}

if( ! function_exists( 'yith_wcbr_update_term_meta' ) ){
	/**
	 * Update term meta (wrapper added to handle backward compatibility with WC < 2.6 and WP < 4.4)
	 * @param $term_id int
	 * @param $meta_key string
	 * @param $meta_value mixed
	 * @param string $prev_value mixed
	 *
	 * @return bool|int|WP_Error
	 * @since 1.0.7
	 */
	function yith_wcbr_update_term_meta( $term_id, $meta_key, $meta_value, $prev_value = '' ){
		if ( version_compare( preg_replace( '/-beta-([0-9]+)/', '', WC()->version ), '2.6', '>=' ) && function_exists( 'update_term_meta' ) ) {
			return update_term_meta( $term_id, $meta_key, $meta_value, $prev_value );
		}
		else{
			return update_metadata( 'woocommerce_term', $term_id, $meta_key, $meta_value, $prev_value );
		}
	}
}