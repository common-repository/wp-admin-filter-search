<?php
/*
  Plugin Name: WP Admin Filter & Search
  Plugin URI: http://makong.kiev.ua/plugings/mkg-ccolumns
  Description: Extend inner WP search and filters functionality by custom fields and terms to the admin's post manager.
  Version: 1.0
  Author: Makong
  Author URI:  http://makong.kiev.ua
  License: GPL2
 */

class MKGCColumns {

	public function __construct(){
		add_action('admin_init', array(&$this, 'init'));
		add_action('admin_menu', array(&$this, 'menu'));
		
		$this->post_types = array();
	}
	
	public function init() {
		
		global $pagenow;
		
        if ( ! is_admin() ) {
			return false;
		}
 		
		if( $this->post_types = array_merge(array('page', 'post'),
			get_post_types(array('public' => true, '_builtin' => false))) ){
			
			foreach($this->post_types as $pt){
				add_filter( "manage_edit-".$pt."_sortable_columns", array(&$this, "mkg_column_sortable") );
				add_filter(	"manage_".$pt."_posts_columns", array(&$this, "mkg_columns") );
				add_action(	"manage_".$pt."_posts_custom_column", array(&$this, "mkg_column") );
			}
		}
		add_action( "pre_get_posts", array(&$this, "mkg_column_orderby") );
		
		$this->options = get_option('mkg_ccolumns_options');
		
		
		if ( 'edit.php' != $pagenow ) {
			return false;
		}
		
		add_filter( 'posts_join', array(&$this, 'mkg_posts_join' ) );
		add_filter( 'posts_where', array(&$this, 'mkg_posts_where' ) );
		add_filter( 'posts_groupby', array(&$this, 'mkg_posts_groupby' ) );
	}
	
	public function menu(){
		add_options_page(
			'MkgCColumns', 
			'MkgCColumns', 
			'manage_options', 
			'mkg_ccolumns', 
			array(&$this, 'settings_page')
		);
	}
	
	public function settings(){

		if($this->options['keys'] = $_POST['keys']){
			$result = update_option('mkg_ccolumns_options', $this->options);
			return true;
		}
		return false;
	}
	
	public function key_name($key){
		
		$key = str_replace(array('-', '_'), ' ', $key);
		$key = ucwords($key);
		
		return $key;
	}

    function mkg_columns($columns) {
		
		$post_type = get_query_var('post_type');
		
		if($keys = $this->options['keys'][$post_type]){
			foreach($keys as $key => $on){
				$columns[$key] = $this->key_name($key);
			}
		}
        		
        return $columns;
    }

    function mkg_column($column) {
		global $wpdb;
		
		$post_type = get_query_var('post_type');
		if($keys = $this->options['keys'][$post_type]){
			foreach($keys as $key => $on){
				if($key = $column){
					$meta_value = get_post_meta(get_the_ID(), $key, true);
					
					if(is_string($meta_value)){
						if(strlen($meta_value) > 100 || filter_var($meta_value, FILTER_VALIDATE_URL)){
							$meta_value = __('YES');
						}
					}elseif(is_array($meta_value)){
						foreach($meta_value as $meta){
							
							if(intval($meta) > 0 &&	$term = $wpdb->get_var( $wpdb->prepare(
								"SELECT name FROM {$wpdb->terms} WHERE term_id = %d", $meta
							))){
								$meta = $term;
							}
							
							$metas[] = $meta;
						}
						
						$meta_value = implode(', ', $metas);
						unset($metas);
						
					}elseif(is_object($meta_value)){
						$meta_value = (array)$meta_value;
						
						foreach($meta_value as $meta){
							$metas[] = $meta;
						}
						
						$meta_value = implode(', ', $metas);
						unset($metas);
					}else{
						$meta_value = __('Unknown');
					}
					
					echo $meta_value;
					
					break;
				}
			}
		}
    }
	
	function mkg_column_sortable($column){
		
		$post_type = get_query_var('post_type');
		if($keys = $this->options['keys'][$post_type]){
			foreach($keys as $key => $on){
				$column[$key] = $key;
			}
		}
	 
		return $column;
	}
	
	function mkg_column_orderby($query){
		if( ! is_admin() )
			return;
 
		$orderby = $query->get('orderby');
		
		$post_type = get_query_var('post_type');
		if($keys = $this->options['keys'][$post_type]){
			foreach($keys as $key => $on){
				if($orderby == $key){
					$query->set('meta_key', $key);
					break;
				}
			}
		}
	}
	
	function is_active() {
		global $pagenow, $wp_query;

		if ( ! is_admin() ) {
			return false;
		}

		if ( 'edit.php' != $pagenow ) {
			return false;
		}

		if ( ! isset( $_GET['s'] ) ) {
			return false;
		}

		if ( ! $wp_query->is_search ) {
			return false;
		}

		return true;
	}
    
	function mkg_posts_join( $join ) {
		
		global $wpdb;

		if ( ! $this->is_active() ) {
			return $join;
		}
		
		$join .= " LEFT JOIN $wpdb->postmeta ON($wpdb->posts.ID = $wpdb->postmeta.post_id)";
		$join .= " LEFT JOIN $wpdb->term_relationships ON($wpdb->posts.ID = $wpdb->term_relationships.object_id)";
		$join .= " LEFT JOIN $wpdb->term_taxonomy ON($wpdb->term_relationships.term_taxonomy_id = $wpdb->term_taxonomy.term_taxonomy_id)";
		$join .= " LEFT JOIN $wpdb->terms ON($wpdb->term_taxonomy.term_id = $wpdb->terms.term_id)";
		
		return $join;
	}
	
	function mkg_posts_where( $where ) {
		
		global $wpdb, $wp;
		$s = $wp->query_vars['s'];
		$pt = $wp->query_vars['post_type'];

		if ( ! $this->is_active() ) {
			return $where;
		}
				
		$where = str_replace(
			"(($wpdb->posts.post_title LIKE '%$s%') OR ($wpdb->posts.post_excerpt LIKE '%$s%') OR ($wpdb->posts.post_content LIKE '%$s%'))",
			"(($wpdb->posts.post_title LIKE '%$s%') OR ($wpdb->postmeta.meta_value LIKE '%$s%') OR ($wpdb->terms.name LIKE '%$s%'))",
			$where
		);
		
		return $where;
	}
	
	function mkg_posts_groupby( $groupby ) {
		
		global $wpdb;

		if ( ! $this->is_active() ) {
			return $groupby;
		}

		if ( empty( $groupby ) ) {
			$groupby = "$wpdb->posts.ID";
		}

		return $groupby;
	}
	
	public function settings_page(){
		
		if(!current_user_can('manage_options')){
			wp_die(__('You do not have sufficient permissions to access this page.'));
		}
		
		if(isset($_REQUEST['action'])){
			$action = sanitize_title($_REQUEST['action']);
			$nonce = "mkg-ccolumns-$action";
			if(isset($_REQUEST['_wpnonce']) && !wp_verify_nonce($_REQUEST['_wpnonce'], $nonce)){
				die( 'Security check failure!' ); 
			}
			else{
				if((function_exists ('check_admin_referer'))) check_admin_referer($nonce);
			}
			$result = $this->$action();
		}

		require_once(sprintf("%s/mkg-ccolumns-settings.php", dirname(__FILE__)));
	}
}

$MKGCColumns = new MKGCColumns();?>