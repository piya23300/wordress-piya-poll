<?php
/*
Plugin Name: Piya Poll
Plugin URI: http://www.iampiya.com
Description: esey to make poll for post
Author: Nattawud Sinprasert (PiYA)
Author URI: http://www.iampiya.com
Version: 0.0.1
tags: poll, survey
*/

require_once( 'models/poll.php' );

#================================
#==== Options Setting Page ======
#================================
require_once( 'settings.php' );
$settings_page = new Myprefix_Admin();
$settings_page->hooks();
#================================
#================================

// Initialize the metabox class
add_action( 'init', 'be_initialize_cmb_meta_boxes', 9999 );
function be_initialize_cmb_meta_boxes() {
  if ( file_exists( dirname( __FILE__ ) . '/lib/cmb2/init.php' ) ) {
    require_once dirname( __FILE__ ) . '/lib/cmb2/init.php';
  } elseif ( file_exists( dirname( __FILE__ ) . '/lib/CMB2/init.php' ) ) {
    require_once dirname( __FILE__ ) . '/lib/CMB2/init.php';
  }
}

add_filter( 'cmb2_init', 'piya_poll_metaboxes' );
function piya_poll_metaboxes( $meta_boxes ) {
  $prefix = 'piya_'; // Prefix for all fields

  $poll_metabox = new_cmb2_box( array(
    'id'            => $prefix . 'poll_metabox',
    'title'         => __( 'Poll (iampiya.com)', 'cmb2' ),
    'object_types'  => array( 'post', ), // Post type
    'context'       => 'normal',
    'priority'      => 'high',
    'show_names'    => true, // Show field names on the left
    // 'cmb_styles' => false, // false to disable the CMB stylesheet
    // 'closed'     => true, // true to keep the metabox closed by default
  ) );

  $poll_metabox->add_field( array(
    'name'       => __( 'Poll ID', 'cmb2' ),
    // 'desc'       => __( 'field description (optional)', 'cmb2' ),
    'id'         => $prefix . 'poll_id',
    'type'       => 'hidden',
    // 'show_on_cb' => 'yourprefix_hide_if_no_cats', // function should return a bool value
    // 'sanitization_cb' => 'my_custom_sanitization', // custom sanitization callback parameter
    // 'escape_cb'       => 'my_custom_escaping',  // custom escaping callback parameter
    // 'on_front'        => false, // Optionally designate a field to wp-admin only
    // 'repeatable'      => true,
  ) );

  $poll_metabox->add_field( array(
    'name'       => __( 'Question ID', 'cmb2' ),
    // 'desc'       => __( 'field description (optional)', 'cmb2' ),
    'id'         => $prefix . 'question_id',
    'type'       => 'hidden',
    // 'show_on_cb' => 'yourprefix_hide_if_no_cats', // function should return a bool value
    // 'sanitization_cb' => 'my_custom_sanitization', // custom sanitization callback parameter
    // 'escape_cb'       => 'my_custom_escaping',  // custom escaping callback parameter
    // 'on_front'        => false, // Optionally designate a field to wp-admin only
    // 'repeatable'      => true,
  ) );

  $poll_metabox->add_field( array(
    'name'       => __( 'Topic', 'cmb2' ),
    // 'desc'       => __( 'field description (optional)', 'cmb2' ),
    'id'         => $prefix . 'topic',
    'type'       => 'text',
    // 'show_on_cb' => 'yourprefix_hide_if_no_cats', // function should return a bool value
    // 'sanitization_cb' => 'my_custom_sanitization', // custom sanitization callback parameter
    // 'escape_cb'       => 'my_custom_escaping',  // custom escaping callback parameter
    // 'on_front'        => false, // Optionally designate a field to wp-admin only
    // 'repeatable'      => true,
  ) );

  $choice_group_id = $poll_metabox->add_field( array(
    'id'          => $prefix . 'choice_group',
    'type'        => 'group',
    // 'description' => __( 'Generates reusable form entries', 'cmb2' ),
    'options'     => array(
      'group_title'   => __( 'Entry {#}', 'cmb2' ), // {#} gets replaced by row number
      'add_button'    => __( 'Add Choice', 'cmb2' ),
      'remove_button' => __( 'Remove Choice', 'cmb2' ),
      'sortable'      => true, // beta
    ),
  ) );

  $poll_metabox->add_group_field( $choice_group_id, array(
    'name'       => __( 'Answer Id', 'cmb2' ),
    'id'         => 'answer_id',
    'type'       => 'hidden',
    // 'repeatable' => true, // Repeatable fields are supported w/in repeatable groups (for most types)
  ) );
  $poll_metabox->add_group_field( $choice_group_id, array(
    'name'       => __( 'Choice', 'cmb2' ),
    'id'         => 'choice',
    'type'       => 'text',
    // 'repeatable' => true, // Repeatable fields are supported w/in repeatable groups (for most types)
  ) );

  return $meta_boxes;
}


#====================================
#======== hook action save    ======
#====================================
add_action( 'publish_post', 'piya_poll_save', 10, 3 );
function piya_poll_save( $post_id, $post, $update ) {
  $poll = piya_poll_get_poll( $post_id );
  if( empty( $poll->id ) ) {
    $response_object = $poll->create($_POST);
  } else {
    $response_object = $poll->update($_POST);
  }
  
  # set response to update post
  $_POST['piya_poll_id'] = $response_object->id;
  $question = $response_object->questions[0];
  $_POST['piya_question_id'] = $question->id;
  $answers = $question->answers;
  foreach ($answers as $index => $answer) {
    $_POST['piya_choice_group'][$index]['answer_id'] = $answer->id;
  }
}

add_action( 'transition_post_status', 'piya_poll_update_status', 10, 3 );
function piya_poll_update_status( $new_status, $old_status, $post ) {
  if ( ($new_status != $old_status) && ($new_status != 'publish' ) ) {
    $poll = piya_poll_get_poll( $post->ID );
    if( !empty( $poll->id ) ) {
      $poll->update( array( 'post_status' => $new_status ) );
    } 
  }
}


function piya_poll_get_poll( $post_id ) {
  $settings_page = $GLOBALS['settings_page'];
  $secrect_key = cmb2_get_option( $settings_page->key, 'secrect_key' );
  $poll = new Poll( $post_id, $secrect_key );
  return $poll;
}