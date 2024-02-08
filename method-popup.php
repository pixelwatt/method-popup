<?php
/**
 * Plugin Name: Method Popup
 * Plugin URI: https://github.com/pixelwatt/method-popup
 * Description: This plugin adds the ability to create one-time popups. Cookies are used.
 * Version: 0.9.0
 * Author: Rob Clark
 * Author URI: https://robclark.io
 */

add_action( 'init', 'method_popup_post_type_init' );

function method_popup_post_type_init() {
    $labels = array(
        'name'               => _x( 'Popups', 'post type general name', 'method-popup' ),
        'singular_name'      => _x( 'Popup', 'post type singular name', 'method-popup' ),
        'menu_name'          => _x( 'Popups', 'admin menu', 'method-popup' ),
        'name_admin_bar'     => _x( 'Popup', 'add new on admin bar', 'method-popup' ),
        'add_new'            => _x( 'Add Popup', 'job', 'method-popup' ),
        'add_new_item'       => __( 'Add New Popup', 'method-popup' ),
        'new_item'           => __( 'New Popup', 'method-popup' ),
        'edit_item'          => __( 'Edit Popup', 'method-popup' ),
        'view_item'          => __( 'View Popup', 'method-popup' ),
        'all_items'          => __( 'Popups', 'method-popup' ),
        'search_items'       => __( 'Search Popups', 'method-popup' ),
        'parent_item_colon'  => __( 'Parent Popup:', 'method-popup' ),
        'not_found'          => __( 'No popups found.', 'method-popup' ),
        'not_found_in_trash' => __( 'No popups found in Trash.', 'method-popup' )
    );

    $args = array(
        'labels'             => $labels,
        'description'        => __( 'A description for the post type.', 'method-popup' ),
        'public'             => false,
        'publicly_queryable' => false,
        'show_ui'            => true,
        'query_var'          => false,
        'capability_type'    => 'post',
        'has_archive'        => false,
        'hierarchical'       => false,
        'menu_position' 	 => 5,
        'menu_icon'			 => 'dashicons-megaphone',
        'supports'           => array( 'title' )
    );

    register_post_type( 'method_popup', $args );
}

//-----------------------------------------------------
// Get an array of post IDs and titles
//-----------------------------------------------------

function method_popup_get_post_array( $type, $labels = false ) {
	//lets create an array of boroughs to loop through
	if ( ! empty( $none ) ) {
		$output[0] = $none;
	} else {
		$output = array();
	}

	$args = array(
		'post_type' => $type,
		'post_status' => 'publish',
		'posts_per_page' => -1,
		'orderby' => 'title',
		'order' => 'ASC'
	);

	//The Query
	$items = get_posts( $args );

	if ( $items ) {
		foreach ( $items as $post ) :
			setup_postdata( $post );
			$ptl = '';
			if ( $labels ) {
				global $wp_post_types;
				$lbs = $wp_post_types[$post->post_type]->labels;
				$ptl = ' (' . $lbs->singular_name . ')';
			}
			$output[ "{$post->ID}" ] = get_the_title( $post->ID ) . $ptl;
		endforeach;
		wp_reset_postdata();
	}

	return $output;
}

function method_popup_get_target_post_types() {
    $types = get_post_types( array( 'public' => true ), 'names' );
    $exclude = array( 'attachment', 'revision', 'nav_menu_item', 'method_popup' );
    return array_diff( $types, $exclude );
}

/**
* CTA Metabox
*/

add_action( 'cmb2_admin_init', 'method_popup_register_metabox' );

function method_popup_register_metabox() {
	$cmb_options = new_cmb2_box(
		array(
			'id'            => '_method_popup_metabox',
			'title'         => esc_html__( 'Popup Options', 'cmb2' ),
			'object_types'  => array( 'method_popup' ),
		)
	);
 
	$cmb_options->add_field (
        array(
            'name' => esc_html__( 'Popup Target(s)', 'cmb2' ),
            'id'   => '_method_popup_target',
            'type' => 'select',
            'show_option_none' => 'Do not load on any page (inactive)',
            'options' => method_popup_get_post_array( method_popup_get_target_post_types(), true ),
            'default' => '',
            'repeatable' => true,
        )
    );

    $cmb_options->add_field (
        array(
            'name' => esc_html__( 'Image', 'cmb2' ),
            'id'   => '_method_popup_img',
            'type' => 'file',
            'options' => array(
                'url' => false,
            ),
        )
    );

    $cmb_options->add_field (
        array(
            'name' => esc_html__( 'Link URL', 'cmb2' ),
            'id'   => '_method_popup_img_link',
            'type' => 'text_url',
        )
    );

    $cmb_options->add_field (
        array(
            'name' => esc_html__( 'Link Title / Button Label', 'cmb2' ),
            'id'   => '_method_popup_img_link_title',
            'type' => 'text',
        )
    );
 
}

function add_this_script_footer(){
    $pid = get_the_ID();
    if ( $pid ) {
        $pidi = (string) $pid;
        $args = array(
            'post_type' => 'method_popup',
            'meta_query' => array(
                array(
                    'key' => '_method_popup_target',
                    'value' => $pid,
                    'compare' => 'LIKE'
                ),
            ),
            'fields' => 'ids',
        );
        $popups = get_posts( $args );
        if ( $popups ) {
            if ( is_array( $popups ) ) {
                if ( count( $popups ) > 0 ) {
                    $popup = $popups[0];
                    
                        $img_id = get_post_meta( $popup, '_method_popup_img_id', true );
                        $link = get_post_meta( $popup, '_method_popup_img_link', true );
                        $title = get_post_meta( $popup, '_method_popup_img_link_title', true );
                        echo '
                            <div class="modal methodPopupModal" id="methodPopup' . $popup . '" tabindex="-1">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header d-none">
                                            <h5 class="modal-title">Popup</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <a href="' . $link . '" title="' . esc_attr( $title ) . '">
                                            ' . wp_get_attachment_image( $img_id, 'large', false, array( 'class' => 'img-fluid' ) ) . '
                                            </a>
                                            <div style="text-align: center; padding: 24px;">
                                                <a class="btn btn-lg btn-primary" href="' . $link . '">
                                                ' . esc_html( $title ) . '
                                                </a>&nbsp;&nbsp;&nbsp;
                                                <a class="btn btn-lg btn-secondary" data-bs-dismiss="modal" aria-label="Close">
                                                Close
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-cookie/1.4.1/jquery.cookie.min.js"></script>
                            <script>
                                jQuery(document).ready(function($) {
                                    if ($.cookie(\'pop' . $popup . '\') == null) {
                                        $(\'#methodPopup' . $popup . '\').modal(\'show\');
                                        $.cookie(\'pop' . $popup . '\', \'7\');
                                    }
                                });
                            </script>
                            <style>
                                .methodPopupModal .modal-body {
                                    padding: 0 !important;
                                }
                            </style>
                        ';
                }
                
            }
            
        }
    }
}
add_action('wp_footer', 'add_this_script_footer'); ?>