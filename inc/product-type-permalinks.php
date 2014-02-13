<?php
/**
 * Filters for hijacking the product permalinks by product type
 *
 * @package   WooCommerce: Product Type Permalinks
 * @author    Timothy Wood @codearachnid <tim@imaginesimplicity.com>
 * @license   GPLv3
 * @link      http://codearachnid.github.io/woocommerce-product-type-permalink/
 * @copyright Copyright (C) 2014, Imagine Simplicity LLC, All Rights Reserved.
 */

if( !class_exists( 'WC_Product_Type_Permalink' ) ){
	class WC_Product_Type_Permalink extends WC_Product_Permalink{

		const VERSION = '1.0.0';
		const MIN_WOO_VERSION = 'x.x';
		// private static $_this = null;
		private $option_flush_key = 'woocommerce_post_type_permalink_flush_rewrite_rules';
		private $product_types;
		private $query_var = 'strict_product_type';

		function __construct(){

			add_action( 'template_redirect', array( $this, 'template_redirect' ) );
			add_filter( 'WC_Product_Permalink/query_vars', array( $this, 'custom_query_vars' ) );
			add_filter( 'post_type_link', array( $this, 'post_type_link' ), 10, 3 );

			$this->product_types = apply_filters( 'WC_Product_Permalink/product_types', array( 
				'simple' => 'simple-product', 
				'variable' => 'variable-product', 
				'grouped' => 'grouped-product',
				'external' => 'external-product'
				));

		}

		/**
		 * init query vars for forcing strict product type permalinks
		 * @param  array $query_vars
		 * @return array
		 */
		public function custom_query_vars( $query_vars ) {
			array_push( $query_vars, $this->query_var );
			return $query_vars;
		}


		/**
		 * add custom permastructures for products
		 */
		public function create_rewrites(){

			// assuming default woocommerce product types:
			// * simple
			// * variable
			// * grouped
			// * external
			foreach( $this->product_types as $product_type => $slug ){
				$key = 'product_' . $product_type;
				add_rewrite_tag( '%' . $key . '%', $slug, $this->query_var . '=' . $product_type . '&product=' );
				add_permastruct( $key, '%' . $key . '%/%postname%/', array(
					'with_front' => false // matches the existing default structure of WooCommerce
					));
			}

			parent::flush_rewrites();
		}

		/**
		 * format pretty permalinks for products
		 */
		public function post_type_link( $post_link, $post, $leavename ){

			// exit gracefully if not a product or if pretty permalinks are disabled
			if( empty( $post->post_type ) || 'product' != $post->post_type || '' == get_option('permalink_structure') )
				return $post_link;

			global $the_product;

			if ( empty( $the_product ) || $the_product->id != $post->ID )
				$the_product = get_product( $post );

			if( array_key_exists( $the_product->product_type, $this->product_types ) ){
				$post_link = str_replace("{$post->post_type}/", trailingslashit( $this->product_types[ $the_product->product_type ] ), $post_link);
			}

			return apply_filters( 'WooCommerce_Product_Type_Permalink/post_type_link', $post_link, $post, $leavename );
		}

		/**
		 * force strict permalinks for defined product types
		 */
		public function template_redirect(){
			global $post, $the_product;
			$type_query_var = get_query_var( $this->query_var );

			// the product isn't setup properly
			if ( empty( $the_product ) || $the_product->id != $post->ID )
				$the_product = get_product( $post );

			if( ! empty( $type_query_var ) && ! empty($the_product->product_type) ){				
				
				// set is_404 since product type doesn't match the permalink
				if( $type_query_var != $the_product->product_type ) {

					parent::set_404();

				}
				
			}
		}

		public function activate(){
			$this->create_rewrites();
		}
	}
}