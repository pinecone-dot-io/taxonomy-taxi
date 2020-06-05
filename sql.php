<?php

namespace taxonomytaxi;

/*
*	filter for `posts_fields` to select joined taxonomy data into the main query
*	@param string 
*	@param WP_Query
*	@return string
*/
function posts_fields( $sql, &$wp_query ){
	foreach( taxonomies() as $tax ){
		$tax = esc_sql( $tax->name );
		
		$sql .= ", GROUP_CONCAT( 
						DISTINCT(
							IF(TX_AUTO.taxonomy = '{$tax}', T_AUTO.name, NULL)
						) 
						ORDER BY T_AUTO.name ASC 
				   ) AS `{$tax}_names`,
				   GROUP_CONCAT( 
				   		DISTINCT(
				   			IF(TX_AUTO.taxonomy = '{$tax}', T_AUTO.slug, NULL)
				   		) 
				   		ORDER BY T_AUTO.name ASC 
				   ) AS `{$tax}_slugs`";
	}
	 
	return $sql;
}

/*
*	filter for `posts_groupby` to group query by post id
*	@param string 
*	@param WP_Query
*	@return string
*/
function posts_groupby( $sql, &$wp_query ){
	global $wpdb;
	$sql = $wpdb->posts.".ID";
	
	return $sql;
}

/*
*	filter for `posts_join` to join taxonomy data into the main query
*	@param string 
*	@param WP_Query
*	@return string
*/
function posts_join( $sql, &$wp_query ){
	global $wpdb;
	$sql .= " LEFT JOIN ".$wpdb->term_relationships." TR_AUTO 
				ON ".$wpdb->posts.".ID = TR_AUTO.object_id
			  LEFT JOIN ".$wpdb->term_taxonomy." TX_AUTO 
			  	ON TR_AUTO.term_taxonomy_id = TX_AUTO.term_taxonomy_id 
			  LEFT JOIN ".$wpdb->terms." T_AUTO 
			  	ON TX_AUTO.term_id = T_AUTO.term_id ";
			  	
	return $sql;
}

/*
*	filter for `posts_orderby` 
*	@param string 
*	@param WP_Query
*	@return string
*/
function posts_orderby( $sql, &$wp_query ){
	global $wpdb;
	
	if( isset($wp_query->query_vars['orderby']) && array_key_exists($wp_query->query_vars['orderby'], taxonomies()) )
		$sql = $wp_query->query_vars['orderby']."_slugs ".$wp_query->query_vars['order'];
	
	return $sql;
}

/*
*	set up query to allow term=-1 to filter posts with no terms in the taxonomy
*	@param WP_Query
*	@return WP_Query
*/
function pre_get_posts( $wp_query ){
	// why the fuck do they use cat and tag for the query vars here? 
	if( isset($wp_query->query_vars['cat']) && $wp_query->query_vars['cat'] === '-1' ){
		$wp_query->query_vars['category_name'] = $wp_query->query_vars['cat'];
		unset( $wp_query->query_vars['cat'] );
	}

	// this is seriously the stupidest shit
	if( isset($_GET['tag']) && $_GET['tag'] === '-1' ){
		$wp_query->query_vars['tag'] = $_GET['tag'];
		unset( $wp_query->query_vars['cat'] );
	}

	foreach( taxonomies() as $k => $v ){
		if( $wp_query->query_vars[$v->query_var] === '-1' ){
			$all_terms = get_terms($k, [
				'fields' => 'ids'
			] );

			$wp_query->query_vars['tax_query'][] = [
				'taxonomy' => $k,
				'terms' => $all_terms,
				'field' => 'term_id',
				'operator' => 'NOT IN'
			];

			unset( $wp_query->query_vars[$v->query_var] );
		}
	}

	return $wp_query;
}

/*
*	just for debugging, view the sql query that populates the Edit table
*	@param string 
*	@return string
*/
function posts_request( $sql, &$wp_query ){
	//ddbug($wp_query->query_vars);
	//ddbug( $sql );
	return $sql;
}