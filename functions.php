<?php
/**
 * Child functions and definitions.
 */

/**
 * Process single location
 *
 * @return void
 * 
 * 
 * 
 */

use Jet_Form_Builder\Exceptions\Action_Exception;

function cb_child_process_location( $location = null ) {

	if ( ! function_exists( 'jet_theme_core' ) ) {
		return false;
	}
	if( ! defined( 'ELEMENTOR_VERSION' ) ) {
		return false;
	}

	$done = jet_theme_core()->locations->do_location( $location );

	return $done;

}


function allow_glb_upload($mimes) {
    $mimes['glb'] = 'model/gltf-binary';
    return $mimes;
}
add_filter('upload_mimes', 'allow_glb_upload');


//EP checkin 
function checkin_webhook( $f , $r) {

	 // Convert the data to JSON format
	 $json_data = json_encode($r);

	 // Your custom webhook URL
	 $webhook_url = 'https://n8n.m50.lv:5678/webhook/anahata-checkin';
    
   	 // Send the data to the webhook endpoint using wp_remote_post
   	 $response = wp_remote_post(
       		 $webhook_url,
       		 array(
           		 'headers' => array(
           		     'Content-Type' => 'application/json',
           		 ),
				    'timeout' => 30, 
           		    'body' => $json_data,
					'sslverify' => false
        		)
   		 );
	
	return $r;
}

add_filter( 'jet-form-builder/custom-filter/checkin-webhook', 'checkin_webhook' , 2, 10);

//EP checkin 
function checkin_process( $form_id, $form_data , $action_handler) {
	
	
    // Extract necessary data from the submitted form
    $user_id = isset($form_data['user_id']) ? intval($form_data['user_id']) : 0;
    $event_id = isset($form_data['post_id']) ? intval($form_data['post_id']) : 0;
	
    if (!$user_id || !$event_id) {
          throw new Action_Exception( 'User ID is required' );
    }

	$user_info = get_userdata($user_id);

    if ($user_info) {
      $user_name = $user_info->display_name; // Outputs the user's display name
    }
	
    // Retrieve user meta data (membership info)
    $membership_start = get_user_meta($user_id, 'membership_start', true);
    $membership_end = get_user_meta($user_id, 'membership_end', true);
    $membership_type = get_user_meta($user_id, 'membership_type', true);
    $checkins_remaining = get_user_meta($user_id, 'checkins_remaining', true);

    // Retrieve event meta data
    $event_date = get_post_meta($event_id, 'event_date', true);
    $event_start_time = get_post_meta($event_id, 'start_time', true);
    // class-type terms
	$class_type = get_the_terms($event_id, 'class-type');
	if ($class_type && !is_wp_error($class_type)) {
		$valid_type = 0;
        foreach ($class_type as $type) {
            if ( $type->name == $membership_type ) {
				 
				 $valid_type = 1 ;
			}		
        }
    }
	
		
	
    $event_title = get_the_title($event_id);

    // Check if the check-in already exists
    $existing_checkin = new WP_Query([
        'post_type'  => 'checkins',
        'meta_query' => [
            [
                'key'   => 'user_id',
                'value' => $user_id,
                'compare' => '='
            ],
            [
                'key'   => 'class_id',
                'value' => $event_id,
                'compare' => '='
            ]
        ]
    ]);

    if ($existing_checkin->have_posts()) {
	    throw new Action_Exception( 'User has already checked in for this event.' );
       
    }

    // Check membership validity
    $current_time = time();
    if (
        $valid_type == 0  ||
        $checkins_remaining <= 0 ||
        $current_time < $membership_start ||
        $current_time > $membership_end
    ) {
        // Log failed check-in attempt
        $i = wp_insert_post([
            'post_type'    => 'activities',
            'post_title'   => "Checkin Failed: $event_title",
            'post_status'  => 'publish',
            'meta_input'   => [
                'user_id'  => $user_id,
                'datetime' => $current_time
            ]
        ]);
		
		
		echo ($valid_type);
		exit;
			
		throw new Action_Exception( 'User is not eligible for check-in.' );
       
    }

    // Process check-in: reduce remaining check-ins and log the check-in
    update_user_meta($user_id, 'checkins_remaining', max(0, $checkins_remaining - 1));


    // Log successful check-in
    $checkin_id = wp_insert_post([
        'post_type'    => 'checkins',
        'post_title'   => "$event_title - $user_name",
        'post_status'  => 'publish',
        'meta_input'   => [
            'user_id'  => $user_id,
            'datetime' => $current_time,
            'class_id' => $event_id
        ]
    ]);

    // Create relationship (if applicable, based on JetFormBuilder custom logic)
    $relation_id = 94; // Update based on JetFormBuilder relationship ID
    wp_insert_post([
        'post_type'    => 'jet_relations',
        'post_status'  => 'publish',
        'meta_input'   => [
            'parent_id' => $user_id,
            'child_id'  => $event_id,
            'relation_id' => $relation_id
        ]
    ]);

    return $form_data;
}



add_filter( 'jet-form-builder/custom-filter/checkin', 'checkin_process' , 3, 10);


add_filter( 'jet-form-builder/custom-filter/checkin-webhook', 'checkin_webhook' , 2, 10);

//EP checkin 
function checkin_error( $form_id, $form_data , $action_handler) {
	
     throw new Action_Exception( "Error : User Id is required" );
}   


add_filter( 'jet-form-builder/custom-filter/checkin-error', 'checkin_error' , 3, 10);



//EP customer_registration 
function customer_registration( $f , $r) {

	 // Convert the data to JSON format
	 $json_data = json_encode($r);

	 // Your custom webhook URL
	 $webhook_url = 'https://n8n.m50.lv:5678/webhook/anahata-new-customer';
    
   	 // Send the data to the webhook endpoint using wp_remote_post
   	 $response = wp_remote_post(
       		 $webhook_url,
       		 array(
           		 'headers' => array(
           		     'Content-Type' => 'application/json',
           		 ),
           		    'body' => $json_data,
					'sslverify' => false
        		)
   		 );
	
	return $r;
}

add_filter( 'jet-form-builder/custom-filter/customer-registration', 'customer_registration' , 2, 10);

//EP create events
function create_events( $f , $r) {

	 // Convert the data to JSON format
	 $json_data = json_encode($r);

	 // Your custom webhook URL
	 $webhook_url = 'https://n8n.m50.lv:5678/webhook/anahata-create-events';
    
   	 // Send the data to the webhook endpoint using wp_remote_post
   	 $response = wp_remote_post(
       		 $webhook_url,
       		 array(
           		 'headers' => array(
           		     'Content-Type' => 'application/json',
           		 ),  
				    'timeout' => 60,
           		    'body' => $json_data,
					'sslverify' => false
        		)
   		 );
	
	return $r;
}

add_filter( 'jet-form-builder/custom-filter/create-events', 'create_events' , 2, 10);


add_filter( 'jet-engine-calculated-callback/config', function( $callbacks = array() ) {

	/**
	 * Dynamic total price depending on guests number
	 * $field_value - is default price per guest for example
	 */
	$callbacks['checkin_button'] = function( $field_value ) {
		


			
    return   "Checkin" ;

	};

	return $callbacks;

} );

