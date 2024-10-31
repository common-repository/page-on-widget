<?php
/**
 * Plugin Name: Page on widget
 * Plugin URI: http://wordpress.org/extend/plugins/page-on-widget/
 * Description: Show content of any selected page in a widget
 * Version: 1.1
 * Author: Cavimaster
 * Author URI: http://www.devsector.ch
 *
 */

//*****************************************************************************
//***************************ADMIN META BOX************************************
//*****************************************************************************

add_action( 'admin_menu', 'pageonwidget_add_custom_box');
add_action( 'save_post', 'pageonwidget_save_postdata' );

//Ajout de la meta_box
function pageonwidget_add_custom_box() {
    add_meta_box(
        'pageonwidget_sectionid',
        __( 'Page on widget', 'pageonwidget_textdomain' ), 
        'pageonwidget_inner_custom_box',
        'page','normal', 'high'
    );
	
}

//contenu de la meta_box
function pageonwidget_inner_custom_box() {
  $post_id=$_GET['post'];
  $meta_value = get_post_meta($post_id, 'pageonwidget_id', true);
  wp_nonce_field( plugin_basename( __FILE__ ), 'pageonwidget_noncename' );
  echo '<label for="pageonwidget_new_field">';
       _e("Add this page on widget", 'pageonwidget_textdomain' );
  echo '</label> ';
  echo '<input type="hidden" name="pageonwidget_id_noncename" id="pageonwidget_id_noncename" value="'.wp_create_nonce('custom_pageonwidget_id').'" />';
   if ( $post_id == $meta_value ) {$checked = 'checked="checked"';} 
  echo '<input type="checkbox" id="pageonwidget_check" name="pageonwidget_check" value="'.$post_id.'"  '.$checked.'/>';
}

//Sauve le contenu de la meta_box
function pageonwidget_save_postdata( $post_id ) {

 if (!wp_verify_nonce($_POST['pageonwidget_id_noncename'], 'custom_pageonwidget_id')) 
 return $post_id;
   if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) 
   return $post_id;
    $pageonwidget_id = $_POST['pageonwidget_check'];
   update_post_meta($post_id, 'pageonwidget_id', $pageonwidget_id);
}

//*****************************************************************************
//***********************************WIDGET************************************
//*****************************************************************************

class page_on_widget extends WP_Widget {
    
	function page_on_widget() {
		$widget_ops = array('classname' => 'widget_pageonwidget', 'description' => __('Show content of a selected page in a widget'));
		$control_ops = array('width' => 400, 'height' => 350);
		$this->WP_Widget('pageonwidget', __('Page on widget'), $widget_ops, $control_ops);
	}
	


	function widget($args, $instance) {
	          global $post;
		      extract($args);
		
        $show = $instance['show'];//show title
		$mypages = get_pages('post_status=publish');
      foreach($mypages as $page)
	  {		
	    $meta_value = get_post_meta($page->ID, 'pageonwidget_id', true);
	if($meta_value == $page->ID){
	    

		$content = $page->post_content;
		if(!$content) // Check for empty page
			continue;
		$content = apply_filters('the_content', $content);
		if($show == 'yes'){
		$html_content = '<h2><a href="'.get_page_link($page->ID).'">'.$page->post_title.'</a></h2>';}
		$html_content.= '<div class="entry">'.content($instance['limit_words'],$content).'<span class="meta-nav"><a href="'.get_page_link($page->ID).'" class="more">'.$instance['more'].'</a></span></div>';
		     

		
		echo $before_widget.$html_content.$after_widget;
	    }
	   }	
	}



	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;

        $instance['more'] = $new_instance['more'];
		$instance['show'] = $new_instance['show'];

		
		if (empty($instance['limit_words'])){$instance['limit_words']=1000;}
		else $instance['limit_words'] = $new_instance['limit_words'];
		return $instance;
		
		if (empty($instance['show'])){$instance['show']='yes';}
		else $instance['show'] = $new_instance['show'];
		return $instance;
	}



	function form($instance) {
	global $post;
    //Liste des pages selectionnées
	echo '<p><label>'._e('Selected pages list: ').'</label>';
	$mypages = get_pages('post_status=publish');
    foreach($mypages as $page)
	 {
	  $meta_value = get_post_meta($page->ID, 'pageonwidget_id', true);
	  echo '<a href="'.get_page_link($page->ID).'" class="more" target="_blank">'.$meta_value.'</a> '; 
     }
	   echo '</p>';
	?>
	<!-- Boutons radio for next version -->
	<p><input  name="<?php echo $this->get_field_name('show'); ?>" type="radio"  value="yes" <?php if ( 'yes' == $instance['show'] ) {echo 'checked="checked"';} ?>/>
	<label for="<?php echo $this->get_field_id('show'); ?>"><?php _e('Show page title'); ?></label></p>
	
	<p><input  name="<?php echo $this->get_field_name('show'); ?>" type="radio"  value="no" <?php if ( 'no' == $instance['show'] ) {echo 'checked="checked"'; } ?>/>
	<label for="<?php echo $this->get_field_id('show'); ?>"><?php _e('Dont show page title'); ?></label></p>
	
	<!-- Limit words -->
	<p><label for="<?php echo $this->get_field_id('limit_words'); ?>"><?php _e('Limit words of content:'); ?></label>
	<input  name="<?php echo $this->get_field_name('limit_words'); ?>" type="text"  value="<?php echo $instance['limit_words']; ?>" size="4" /></p>
	
	<!-- More... link -->
	<p><label for="<?php echo $this->get_field_id('more'); ?>"><?php _e('Read more text:'); ?></label>
	<input  name="<?php echo $this->get_field_name('more'); ?>" type="text"  value="<?php echo $instance['more']; ?>" /></p>
	
	<?php
	
    }
}

    //limit word content function
	function content($limit,$content) {
  $content = explode(' ', $content, $limit);
  if (count($content)>=$limit) {
    array_pop($content);
    $content = implode(" ",$content).'...';
  } else {
    $content = implode(" ",$content);
  }	
  $content = preg_replace('/\[.+\]/','', $content);
  $content = apply_filters('the_content', $content); 
  $content = str_replace(']]>', ']]&gt;', $content);
  return $content;
} 
add_action('widgets_init', create_function('', 'return register_widget("page_on_widget");'));
