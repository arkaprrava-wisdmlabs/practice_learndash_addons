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
        if ( isset( $_POST['learndash-group-access-settings']['start-date'] ) ) {
            $my_settings_value = $_POST['learndash-group-access-settings']['start-date'];
            // Then update the post meta
            update_post_meta( $post_id, 'start_date', $my_settings_value );
        }
        else{
            update_post_meta( $post_id, 'start_date', '' );
        }
    },
    30,
    3
);
 
// You have to save your own field. This is no longer handled by LD. This is on purpose.