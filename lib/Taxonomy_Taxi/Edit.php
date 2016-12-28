<?php 

namespace Taxonomy_Taxi;

// should only be used on wp-admin/edit.php
class Edit{
	protected static $post_type = '';
	protected static $taxonomies = array();

	/**
	*
	*	@param string
	*/
	public static function init( $post_type = '' ){
		self::$post_type = $post_type;
		self::set_taxonomies( $post_type );

		add_filter( 'disable_categories_dropdown', '__return_true' );
		add_action( 'restrict_manage_posts', __CLASS__.'::restrict_manage_posts', 10, 1 );

		// edit.php columns
		add_filter( 'manage_edit-'.$post_type.'_sortable_columns', __CLASS__.'::register_sortable_columns', 10, 1 );	
		add_filter( 'manage_pages_columns', __CLASS__.'::manage_posts_columns', 10, 1 );
		add_filter( 'manage_posts_columns', __CLASS__.'::manage_posts_columns', 10, 1 );
		add_action( 'manage_pages_custom_column', __CLASS__.'::manage_posts_custom_column', 10, 2 );
		add_action( 'manage_posts_custom_column', __CLASS__.'::manage_posts_custom_column', 10, 2 );

		add_filter( 'request', __CLASS__.'::request', 10, 1 );	
	}

	/**
	*	attached to `manage_posts_columns` filter
	*	adds columns for custom taxonomies in Edit table
	*	@param array $headings
	*	@return array $headings
	*/
	public static function manage_posts_columns( $headings ){
		//  arbitary placement in table if it cant replace categories
		$keys = array_keys( $headings );
		$key = array_search( 'categories', $keys );

		if( !$key )
			$key = max( 1, count($keys) );
			
		// going to replace stock columns with sortable ones
		unset( $headings['categories'] );
		unset( $headings['tags'] );
		
		$a = array_slice( $headings, 0, $key );
		$b = array_map( function($tax){
			return $tax->label;
		}, Settings::get_active_for_post_type(self::$post_type) );
		
		$c = array_slice( $headings, $key );
		
		$headings = array_merge( $a, $b, $c );
	
		return $headings;
	}

	/**
	*	attached to `manage_posts_custom_column` action
	*	echos column data inside each table cell
	*	@param string 
	*	@param int
	*	@return NULL
	*/
	public static function manage_posts_custom_column( $column_name, $post_id ){
		global $post;
		
		if( !isset($post->taxonomy_taxi[$column_name]) || !count($post->taxonomy_taxi[$column_name]) )
			return print '&nbsp;';
		
		$links = array_map( function($column){
			return sprintf( '<a href="?post_type=%s&amp;%s=%s">%s</a>', 
				$column['post_type'],
				$column['taxonomy'],
				$column['slug'],
				$column['name'] 
			);
		}, $post->taxonomy_taxi[$column_name] );

		echo implode( ', ', $links );
	}

	/**
	*	register custom taxonomies for sortable columns
	*	@param array
	*	@return array
	*/
	public static function register_sortable_columns( $columns ){
		$keys = array_map( function($tax){
			return $tax->name;
		}, Settings::get_active_for_post_type(self::$post_type) );

		if( count($keys) ){
			$keys = array_combine( $keys, $keys );
			$columns = array_merge( $columns, $keys ); 
		}

		return $columns;
	}

	/**
	*	fix bug in setting post_format query varaible
	*	wp-includes/post.php function _post_format_request()
	*		$tax = get_taxonomy( 'post_format' );
	*		$qvs['post_type'] = $tax->object_type;
	*		sets global $post_type to an array
	*	attached to `request` filter
	*	@param array
	*	@return array
	*/
	public static function request( $qvs ){
		if( isset($qvs['post_type']) && is_array($qvs['post_type']) )
			$qvs['post_type'] = $qvs['post_type'][0];
			
		return $qvs;
	}

	/**
	*	action for `restrict_manage_posts` 
	*	to display drop down selects for custom taxonomies
	*/
	public static function restrict_manage_posts(){
		foreach( Settings::get_active_for_post_type(self::$post_type) as $taxonomy => $props ){		
			$html = wp_dropdown_categories( array(
				'echo' => 0,
				'hide_empty' => TRUE,
				'hide_if_empty' => TRUE,
				'hierarchical' => TRUE,
				'name' => $props->query_var,
				'selected' => isset( $_GET[$props->query_var] ) ? $_GET[$props->query_var] : FALSE,
				'show_option_all' => 'View '.$props->view_all,
				'show_option_none' => '[None]',
				'taxonomy' => $taxonomy,
				'walker' => new Walker_Taxo_Taxi
			) );
			
			echo $html;
		}
	}

	public static function get_taxonomies(){
		return self::$taxonomies;
	}

	/**
	*
	*/
	protected static function set_taxonomies( $post_type ){
		self::$taxonomies = get_object_taxonomies( $post_type, 'objects' );
	}
}