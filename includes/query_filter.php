<?php
/**
 * Filter for post query
 *
 * @package   AnsPress
 * @author    Rahul Aryan <admin@rahularyan.com>
 * @license   GPL-2.0+
 * @link      http://rahularyan.com
 * @copyright 2014 Rahul Aryan
 * @since 2.0.1
 */

class AnsPress_Query_Filter
{

    /**
     * Initialize the class
     */
    public function __construct()
    {
        
		
        // TODO: move to admin
		// custom columns in CPT answer
        add_filter('manage_edit-answer_columns', array($this,'cpt_answer_columns'));

        // TODO: move to admin
		// Sortable question CPT columns
        add_filter('manage_edit-question_sortable_columns', array($this, 'admin_column_sort_flag'));
		
        // TODO: move to admin
		// Sortable answer CPT columns
        add_filter('manage_edit-answer_sortable_columns', array($this, 'admin_column_sort_flag'));
		
        // TODO: move to admin
		// Sortable flag columns
        add_action('pre_get_posts', array( $this, 'admin_column_sort_flag_by'));
		
        // TODO: move to admin
        add_action('manage_answer_posts_custom_column', array($this, 'answer_row_actions'), 10, 2);
		
        // TODO: move to admin
        add_filter('wp_insert_post_data', array($this, 'post_data_check'), 99);
        // TODO: move to admin
        add_filter('post_updated_messages', array($this,'post_custom_message'));
		
		//
		// TODO: move to admin
		add_action( 'admin_init', array( $this, 'init_actions' ) ); 
		add_action( 'init', array($this, 'ap_make_post_parent_public') );
		add_action( 'save_post', array($this, 'ans_parent_post'), 0, 2 );	
	
		add_action('wp_ajax_search_questions', array($this, 'suggest_questions'));

		//add_filter( 'post_type_link', array($this, 'custom_question_link'), 10, 2 );		
		add_filter('get_pagenum_link', array($this, 'custom_page_link'));
		
		add_action( 'posts_clauses', array($this, 'answer_sort_newest'), 10, 2 );
		add_action( 'posts_clauses', array($this, 'user_favorites'), 10, 2 );
        // TODO: move to admin
		add_action('admin_footer-post.php', array($this, 'append_post_status_list'));
		
		add_action( 'posts_clauses', array($this, 'main_question_query'), 10, 2 );

    }


	public function init_actions(){
		add_meta_box( 'ap_ans_parent_q','Parent Question', array($this, 'ans_parent_q_metabox'),'answer','side', 'high' );
		
		//add_action('delete_post', array($this, 'delete_action'));		
	}
    
    
    // custom columns in CPT answer
    public function cpt_answer_columns($columns)
    {
        $columns = array(
            "cb" => "<input type=\"checkbox\" />",
            "answerer" => __('Answerer', 'ap'),
            //"parent_question" => __('Question', 'ap'),
            //"answer_content" => __('Content', 'ap'),
            "comments" => __('Comments', 'ap'),
            //"vote" => __('Vote', 'ap'),
            "flag" => __('Flag', 'ap'),
            "date" => __('Date', 'ap')
        );
        return $columns;
    }
    
    //make flag sortable
    public function admin_column_sort_flag($columns)
    {
        $columns['flag'] = 'flag';
        return $columns;
    }
    
    public function admin_column_sort_flag_by($query)
    {
        if (!is_admin())
            return;
        
        $orderby = $query->get('orderby');
        
        if ('flag' == $orderby) {
            $query->set('meta_key', ANSPRESS_FLAG_META);
            $query->set('orderby', 'meta_value_num');
        }
    }
    
    
   
    
    public function answer_row_actions($column, $post_id)
    {
        global $post, $mode;
        
        if ('answer_content' != $column)
            return;
        
        $content = get_the_excerpt();
        // get the first 80 words from the content and added to the $abstract variable
        preg_match('/^([^.!?\s]*[\.!?\s]+){0,40}/', strip_tags($content), $abstract);
        // pregmatch will return an array and the first 80 chars will be in the first element 
        echo $abstract[0] . '...';
        
        //First set up some variables
        $actions          = array();
        $post_type_object = get_post_type_object($post->post_type);
        $can_edit_post    = current_user_can($post_type_object->cap->edit_post, $post->ID);
        
        //Actions to delete/trash
        if (current_user_can($post_type_object->cap->delete_post, $post->ID)) {
            if ('trash' == $post->post_status) {
                $_wpnonce           = wp_create_nonce('untrash-post_' . $post_id);
                $url                = admin_url('post.php?post=' . $post_id . '&action=untrash&_wpnonce=' . $_wpnonce);
                $actions['untrash'] = "<a title='" . esc_attr(__('Restore this item from the Trash')) . "' href='" . $url . "'>" . __('Restore') . "</a>";
                
            } elseif (EMPTY_TRASH_DAYS) {
                $actions['trash'] = "<a class='submitdelete' title='" . esc_attr(__('Move this item to the Trash')) . "' href='" . get_delete_post_link($post->ID) . "'>" . __('Trash') . "</a>";
            }
            if ('trash' == $post->post_status || !EMPTY_TRASH_DAYS)
                $actions['delete'] = "<a class='submitdelete' title='" . esc_attr(__('Delete this item permanently')) . "' href='" . get_delete_post_link($post->ID, '', true) . "'>" . __('Delete Permanently') . "</a>";
        }
        if ($can_edit_post)
			$actions['edit'] = '<a href="' . get_edit_post_link($post->ID, '', true) . '" title="' . esc_attr(sprintf(__('Preview &#8220;%s&#8221;'),$post->title)) . '" rel="permalink">' . __('Edit') . '</a>';
			
        //Actions to view/preview
        if (in_array($post->post_status, array(
            'pending',
            'draft',
            'future'
        ))) {
            if ($can_edit_post)
                $actions['view'] = '<a href="' . esc_url(add_query_arg('preview', 'true', get_permalink($post->ID))) . '" title="' . esc_attr(sprintf(__('Preview &#8220;%s&#8221;'),$post->title)) . '" rel="permalink">' . __('Preview') . '</a>';
            
        } elseif ('trash' != $post->post_status) {
            $actions['view'] = '<a href="' . get_permalink($post->ID) . '" title="' . esc_attr(__('View &#8220;%s&#8221; question')) . '" rel="permalink">' . __('View') . '</a>';
        }
        
        //***** END  -- Our actions  *******//
        
        //Echo the 'actions' HTML, let WP_List_Table do the hard work
		$WP_List_Table = new WP_List_Table();
        echo $WP_List_Table->row_actions($actions);
    }
    
    public function post_data_check($data)
    {
        global $pagenow;
        if ($pagenow == 'post.php' && $data['post_type'] == 'answer') {
            $parent_q = isset($_REQUEST['ap_q']) ? $_REQUEST['ap_q'] : $data['post_parent'];
            if (!isset($parent_q) || $parent_q == '0' || $parent_q == '') {
                add_filter('redirect_post_location', array(
                    $this,
                    'custom_post_location'
                ), 99);
                return;
            }
        }
        
        return $data;
    }
    
    public function custom_post_location($location)
    {
        remove_filter('redirect_post_location', __FUNCTION__, 99);
        $location = add_query_arg('message', 99, $location);
        return $location;
    }
    
    public function post_custom_message($messages)
    {
        global $post;
        
        if ($post->post_type == 'answer' && isset($_REQUEST['message']) && $_REQUEST['message'] == 99)
            add_action('admin_notices', array(
                $this,
                'ans_notice'
            ));
        
        return $messages;
    }
    
    public function ans_notice()
    {
        echo '<div class="error">
           <p>' . __('Please fill parent question field, Answer was not saved!', 'ap') . '</p>
        </div>';
    }
	
	public function ans_parent_q_metabox( $answer ) {
		echo '<input type="hidden" name="ap_ans_noncename" id="ap_ans_noncename" value="' .wp_create_nonce( plugin_basename(__FILE__) ) . '" />';
		echo '<input type="hidden" name="ap_q" id="ap_q" value="'.$answer->post_parent.'" />';
		echo '<input type="text" name="ap_q_search" id="ap_q_search" value="'.get_the_title($answer->post_parent).'" />';
	}
	
	// set question for the answer
	public function ans_parent_post( $post_id, $post ) {
		
		if ( !isset($_POST['ap_ans_noncename']) || !wp_verify_nonce( $_POST['ap_ans_noncename'], plugin_basename(__FILE__) )) {
			return $post->ID;
		}
		if ( !current_user_can( 'edit_post', $post->ID ))
			return $post->ID;
		
		// return on autosave
		if(defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) { return; }
		
		if ( $post->post_type == 'answer' ) {
			$parent_q = sanitize_text_field($_POST['ap_q']);
			if( !isset( $parent_q ) || $parent_q == '0' || $parent_q =='' ){
				return $post->ID;
			}else{
				global $wpdb;
				$wpdb->update( $wpdb->posts, array( 'post_parent' => $parent_q ), array( 'ID' => $post->ID ) );
			}
			
		}
	}
	
	
	// make post_parent public for admin_init
	public function ap_make_post_parent_public() {
		if ( is_admin() )
			$GLOBALS['wp']->add_query_var( 'post_parent' );
	}
	
	 
	
	
	public function delete_action($post_id){
		$post = get_post($post_id);
		
		if($post->post_type == 'question')
			ap_do_event('delete_question', $post->ID, $post->post_author);
		
		elseif($post->post_type == 'answer')
			ap_do_event('delete_answer', $post->ID, $post->post_author);
	}
	
	
	
	
	
	
	function suggest_questions() {
		// Query for suggestions  
		$posts = get_posts( array(  
			's' =>$_REQUEST['term'],  
			'post_type'=> 'question'
		) );  
	  
		// Initialise suggestions array  
		$suggestions=array();  
	   // global $post;  
		foreach ($posts as $post): setup_postdata($post);  
			// Initialise suggestion array  
			$suggestion = array();  
			$suggestion['label'] = esc_html($post->post_title);  
			$suggestion['id'] = $post->ID;  
	  
			// Add suggestion to suggestions array  
			$suggestions[]= $suggestion;  
		endforeach;  
	 
		// JSON encode and echo  
		$response = $_GET["callback"] . "(" . json_encode($suggestions) . ")";  
		echo $response;  
	  
		// Don't forget to exit!  
		exit;  
	}

	public function custom_question_link( $url, $post ) {
        /**
         * TODO: Remove this filter if not needed anymore
         */
		if ( 'question' == get_post_type( $post ) ) {
			if(get_option('permalink_structure')){
				$question_slug = ap_opt('question_prefix');
				$question_slug = strlen($question_slug) > 0 ? $question_slug.'/' : '';
				return  ap_get_link_to($question_slug.$post->ID.'/'.$post->post_name); 
			}else
				return add_query_arg( array('apq' => false, 'page_id' => ap_opt('base_page'), 'question_id' =>$post->ID), $url );
		}
		return $url;
	}
	
	public function custom_page_link( $result ){
		//print_r($result);
		if(ap_opt('base_page') == get_option('page_on_front'))
			$result = str_replace('?paged', '?page_id='.ap_opt('base_page').'&paged', $result);
		return $result ;
	}
	
	public function answer_sort_newest($sql, $query){
		global $wpdb;
		if(isset($query->query['ap_query']) && $query->query['ap_query'] == 'answer_sort_newest'){		
			$sql['orderby'] = 'IF('.$wpdb->prefix.'postmeta.meta_key = "'.ANSPRESS_BEST_META.'" AND '.$wpdb->prefix.'postmeta.meta_value = 1, 0, 1), '.$sql['orderby'];
		}elseif(isset($query->query['ap_query']) && $query->query['ap_query'] == 'answer_sort_voted'){
			$sql['orderby'] = 'IF(mt1.meta_value = 1, 0, 1), '.$sql['orderby'];
		}
		return $sql;
	}
	
	public function user_favorites($sql, $query){
		global $wpdb;
		if(isset($query->query['ap_query']) && $query->query['ap_query'] == 'user_favorites'){			
			$sql['join'] = 'LEFT JOIN '.$wpdb->prefix.'ap_meta apmeta ON apmeta.apmeta_actionid = ID '.$sql['join'];
			$sql['where'] = 'AND apmeta.apmeta_userid = post_author AND apmeta.apmeta_type ="favorite" '.$sql['where'];
		}
		return $sql;
	}
	
	public function append_post_status_list(){
		 global $post;
		 $complete = '';
		 $label = '';
		
		 if($post->post_type == 'question' || $post->post_type == 'answer'){
			  if($post->post_status == 'moderate'){
				   $complete = ' selected=\'selected\'';
				   $label = '<span id=\'post-status-display\'>'.__('Moderate', 'ap').'</span>';
			  }elseif($post->post_status == 'private_post'){
				   $complete = ' selected=\'selected\'';
				   $label = '<span id=\'post-status-display\'>'.__('Private Post', 'ap').'</span>';
			  }elseif($post->post_status == 'closed'){
				   $complete = ' selected=\'selected\'';
				   $label = '<span id=\'post-status-display\'>'.__('Closed', 'ap').'</span>';
			  }
			  ?>
			  
			  <?php
			  echo '<script>
					  jQuery(document).ready(function(){
						   jQuery("select#post_status").append("<option value=\'moderate\' '.$complete.'>'.__('Moderate', 'ap').'</option>");
						   jQuery("select#post_status").append("<option value=\'private_post\' '.$complete.'>'.__('Private Post', 'ap').'</option>");
						   jQuery("select#post_status").append("<option value=\'closed\' '.$complete.'>'.__('Closed', 'ap').'</option>");
						   jQuery(".misc-pub-section label").append("'.$label.'");
					  });
			  </script>';
		 }
	}
	
	public function main_question_query($sql, $query){
		global $wpdb;
		if(isset($query->query['ap_query']) && $query->query['ap_query'] == 'main_questions_active'){
			$sql['orderby'] = 'case when mt1.post_id IS NULL then '.$wpdb->posts.'.post_date else '.$wpdb->postmeta.'.meta_value end DESC';
			//var_dump($sql);
		}elseif(isset($query->query['ap_query']) && $query->query['ap_query'] == 'related'){
			$keywords = explode(' ', $query->query['ap_title']);

			$where = "AND (";
			$i =1;
			foreach ($keywords as $key){
				if(strlen($key) > 1){
					$key = $wpdb->esc_like( $key );
					if($i != 1)
					$where .= "OR ";
					$where .= "(($wpdb->posts.post_title LIKE '%$key%') AND ($wpdb->posts.post_content LIKE '%$key%')) ";
					$i++;
				}
			}
			$where .= ")";
			
			$sql['where'] = $sql['where'].' '.$where;

		}
		return $sql;
	}
	
	public function question_feed(){
		include ap_get_theme_location('feed-question.php');
	}

}
