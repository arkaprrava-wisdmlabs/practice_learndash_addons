<?php
/*
Plugin Name: Custom LearnDash Plugin
Description: Adds a custom field to the group page in the group builder interface to set an group's course access date for group's users.
Text Domain: ldad
*/
// Hook into the Group Builder page
add_filter(
    'learndash_settings_fields',
    function ( $setting_option_fields = array(), $settings_metabox_key = '' ) {
        // Check the metabox includes/settings/settings-metaboxes/class-ld-settings-metabox-group-access-settings.php line 23 where
        // settings_metabox_key is set. Each metabox or section has a unique settings key.
        if ( 'learndash-group-access-settings' === $settings_metabox_key ) {
            $post_id           = get_the_ID();
            $my_settings_value = get_post_meta( $post_id, 'start_date', true );
            $s = '';
            if ( empty( $my_settings_value ) ) {
                        $s = '';
            }
            else{
                $arr = maybe_unserialize( $my_settings_value );
                $s = sprintf(
                    '%04d-%02d-%02d %02d:%02d:00',
                    intval( $arr['aa'] ),
                    intval( $arr['mm'] ),
                    intval( $arr['jj'] ),
                    intval( $arr['hh'] ),
                    intval( $arr['mn'] )
                );
            }
            // print_r(date( 'Y-m-d H:i'));
            if ( ! isset( $setting_option_fields['check'] ) ) {
                $setting_option_fields['check'] = array(
                    'name'      => 'check',
                    'label'     => sprintf(
                        // translators: placeholder: Course.
                        esc_html_x( '%s\'s Course Access Start Date', 'placeholder: Groups', 'learndash' ),
                        learndash_get_custom_label( 'group' )
                    ),
                    // Check the LD fields ligrary under incldues/settings/settings-fields/
                    'type'      => 'checkbox-switch',
                    'options' => array('Add a start date.'),
                );
            }
            if ( ! isset( $setting_option_fields['start-date'] ) ) {
                $setting_option_fields['start-date'] = array(
                    'name'           => 'start-date',
					'type'           => 'date-entry',
					'class'          => '-medium',
					'label'          => 'Start Date',
					// 'input_label'    => '',
					'value'          => $s,
					'default'        => '',
					// 'attrs'          => array(
					// 	'step' => 1,
					// 	'min'  => 0,
					// ),
					'parent_setting' => 'check',
                );
            }
        }
        return $setting_option_fields;
    },
    30,
    2
);

add_action(
    'save_post',
    function( $post_id, $post, $update) {
        // All the metabox fields are in sections. Here we are grabbing the post data
        // within the settings key array where the added the custom field.
        if(isset($_POST['learndash-group-access-settings']['check'])){
            if ( isset( $_POST['learndash-group-access-settings']['start-date'] ) ) {
                $my_settings_value = $_POST['learndash-group-access-settings']['start-date'];
                // Then update the post meta
                update_post_meta( $post_id, 'start_date', $my_settings_value );
            }
        }
        else{
            update_post_meta( $post_id, 'start_date', '' );
        }
    },
    30,
    3
);
 
// You have to save your own field. This is no longer handled by LD. This is on purpose.

add_filter(
    'sfwd_lms_has_access',
    function( $return, $post_id, $user_id ) {
        if ( empty( $user_id ) ) {
            if ( ! is_user_logged_in() ) {
                return $return;
            }
            else {
                $user_id = get_current_user_id();
            }
        }
        if( is_super_admin( $user_id )){
            return $return;
        }
        
        $course_id = learndash_get_course_id( $post_id );
        if ( empty( $course_id ) ) {
            return $return;
        }
        $group_ids = learndash_get_users_group_ids($user_id);
        if ( empty( $group_ids ) ) {
            return $return;
        }
        foreach($group_ids as $group_id){
            $course_ids = learndash_get_group_courses_list($group_id);
            if(!empty($course_ids)){
                if(in_array($course_id, $course_ids)){
                    $start_date = get_post_meta($group_id, 'start_date', true);
                    if(!empty($start_date)){
                        $today = strtotime(date( 'Y-m-d' ));
                        $s = sprintf(
                            '%04d-%02d-%02d',
                            $start_date['aa'],
                            $start_date['mm'],
                            $start_date['jj'],
                        );
                        $upto_date = strtotime($s);
                        if($today < $upto_date){
                            return false;
                        }
                        else{
                            return $return;
                        }
                    }
                }
            }
        }
        return $return;
    },
    10,
    3
);
 
// You have to save your own field. This is no longer handled by LD. This is on purpose.
add_filter(
    'learndash_payment_button_markup',
    function($html){
        if ( empty( $user_id ) ) {
            if ( ! is_user_logged_in() ) {
                return $html;
            }
            else {
                $user_id = get_current_user_id();
            }
        }
        // if( is_super_admin( $user_id )){
        //     return $return;
        // }
        
        $course_id = learndash_get_course_id( get_the_ID(  ) );
        if ( empty( $course_id ) ) {
            return $html;
        }
        $group_ids = learndash_get_users_group_ids($user_id);
        if ( empty( $group_ids ) ) {
            return $html;
        }
        foreach($group_ids as $group_id){
            $course_ids = learndash_get_group_courses_list($group_id);
            if(!empty($course_ids)){
                if(in_array($course_id, $course_ids)){
                    $start_date = get_post_meta($group_id, 'start_date', true);
                    if(!empty($start_date)){
                        $today = strtotime(date( 'Y-m-d' ));
                        $s = sprintf(
                            '%04d-%02d-%02d',
                            $start_date['aa'],
                            $start_date['mm'],
                            $start_date['jj']
                        );
                        $button_text = sprintf(
                            'Course access will start from %02d-%02d-%04d',
                            $start_date['jj'],
                            $start_date['mm'],
                            $start_date['aa']
                        );
                        $upto_date = strtotime($s);
                        if($today < $upto_date){
                            return $button_text;
                        }
                        else{
                            return $html;
                        }
                    }
                }
            }
        }
        return $html;
    },
    10,2
);