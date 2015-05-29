<?php
/*
Plugin Name: Contact Form 7 to Post
Plugin URI:
Description: Save contact form 7 submissions as new posts
Version: 1.0.0
Author: bastho
Author URI: http://ba.stienho.fr
License: GPLv2
Text Domain: cf72post
Domain Path: /languages/
*/

$CF7_2_Post = new CF7_2_Post();

class CF7_2_Post{
    function CF7_2_Post(){
	load_plugin_textdomain('cf72post', false, 'contact-form-7-to-post/languages');
	__('Contact Form 7 to Post','cf72post');
	__('Save contact form 7 submissions as new posts','cf72post');

	add_action('wpcf7_before_send_mail', array($this, 'send_mail'), 1, 1);
        add_action('wpcf7_add_meta_boxes', array($this, 'add_meta_boxes'));
	add_action('wpcf7_admin_after_form', array($this, 'meta_box'), 1, 1);
	add_action('wpcf7_save_contact_form', array($this, 'save_form'), 1, 1);
    }

    function save_form($form){
	$properties = array();
	if ( isset( $_POST['wpcf7-form-post-type'] ) ) {
	    $properties['post_type']=  esc_attr(filter_input(INPUT_POST, 'wpcf7-form-post-type', FILTER_SANITIZE_STRING));
	}
	if ( isset( $_POST['wpcf7-form-post-status'] ) ) {
	    $properties['post_status']=esc_attr(filter_input(INPUT_POST, 'wpcf7-form-post-status', FILTER_SANITIZE_STRING));
	}
	if ( isset( $_POST['wpcf7-form-post-title'] ) ) {
	    $properties['post_title']=esc_attr(filter_input(INPUT_POST, 'wpcf7-form-post-title', FILTER_SANITIZE_STRING));
	}
	if ( isset( $_POST['wpcf7-form-post-content'] ) ) {
	    $properties['post_content']=esc_textarea(filter_input(INPUT_POST, 'wpcf7-form-post-content'));
	}
	update_post_meta($form->id(), 'post', $properties);
    }
    function get_post($form){
	return wp_parse_args( get_post_meta($form->id(), 'post', true), array(
		'post_type'=>'',
		'post_status'=>'draft',
		'post_title'=>'[your-subject]',
		'post_content'=>'[your-message]'
		)
	    );
    }
    function add_meta_boxes(){
	add_meta_box('postdiv', __('Save to post', 'cf72post'), array($this, 'edit_form'), null, '2_post', 'core');
    }
    function meta_box($form){
	do_meta_boxes( null, '2_post', $form);
    }
    function edit_form($form, $box){
	$post = $this->get_post($form);
	$post_types = get_post_types(array('show_ui'=>true),  'objects');
	?>
<div class="half-left">
    <div class="post-field">
	<label for="wpcf7-form-post-type"><?php echo esc_html( __( 'Post type:', 'cf72post' ) ); ?></label><br />
	<select id="wpcf7-form-post-type" class="wide" name="wpcf7-form-post-type">
	    <option value=""><?php echo esc_html( __( 'None', 'cf72post' ) ); ?></option>
	    <?php foreach ($post_types as $type=>$post_type): ?>
	    <option value="<?php echo $type; ?>" <?php selected($type, $post['post_type']); ?>><?php echo $post_type->labels->singular_name; ?></option>
	    <?php endforeach; ?>
	</select>
    </div>
    <div class="post-field">
	<label for="wpcf7-form-post-status"><?php echo esc_html( __( 'Post status:', 'cf72post' ) ); ?></label><br />
	<select id="wpcf7-form-post-status" class="wide" name="wpcf7-form-post-status">
	    <option value="draft" <?php selected('draft', $post['post_status']); ?>><?php echo esc_html( __( 'Draft') ); ?></option>
	    <option value="publish" <?php selected('publish', $post['post_status']); ?>><?php echo esc_html( __( 'Published') ); ?></option>
	</select>
    </div>
    <div class="post-field">
	<label for="wpcf7-form-post-title"><?php echo esc_html( __( 'Post title:', 'cf72post' ) ); ?></label><br />
	<input type="text" id="wpcf7-form-post-title" class="wide" name="wpcf7-form-post-title" value="<?php echo esc_html( $post['post_title'] ); ?>"/>
    </div>
</div>
<div class="half-right">
    <div class="post-field">
	<label for="wpcf7-form-post-content"><?php echo esc_html( __( 'Post content:', 'cf72post' ) ); ?></label><br />
	<textarea id="wpcf7-form-post-content" class="wide" name="wpcf7-form-post-content" rows="8"><?php echo esc_html( $post['post_content'] ); ?></textarea>
    </div>
</div>
<br class="clear" />
	<?php
    }
    function shortcode_check($form, $content){
	$tags = $form->form_scan_shortcode();
	foreach ( (array) $tags as $tag ) {
	    $content = str_replace('['.$tag['name'].']', esc_textarea(filter_input(INPUT_POST, $tag['name'])), $content);
	}
	return $content;
    }
    function send_mail($form){
	$post = $this->get_post($form);
	$post_types = get_post_types(array('show_ui'=>true));
	if($post['post_type']!='' && in_array($post['post_type'], $post_types)){
	    $new_post = array(
		'post_type'=>$post['post_type'],
		'post_status'=>$post['post_status'],
		'post_title'=>$this->shortcode_check($form, $post['post_title']),
		'post_content'=>$this->shortcode_check($form, $post['post_content'])
	    );
	    wp_insert_post($new_post);
	}
    }
}