<?php


namespace Jet_Form_Builder\Form_Response\Types;

use Jet_Form_Builder\Dev_Mode\Logger;
use Jet_Form_Builder\Dev_Mode\Manager;
use Jet_Form_Builder\Form_Messages;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

class Ajax_Response extends Response_It {

	public function get_field_errors( $errors ) {
		return $errors;
	}

	public function parse_status( $status ) {
		return $status;
	}

	public function send( array $query_args ) {
		if ( Manager::instance()->active() ) {
			$query_args['__logger'] = Logger::instance()->get_logs();
		}

		$messages = $this->get_message_builder()->set_form_status( $query_args['status'] );

		$query_args['message'] = $messages->get_rendered_messages();

        $statusCode = 200;
        if ( $query_args['status'] == 'error' ) {
            $statusCode = 400;
        }
		wp_send_json( $query_args, $statusCode );
	}

	public function additional_fields() {
		return array( 'form_id', 'actions', 'response' );
	}

	public function get_message_builder() {
		return jet_form_builder()->msg_router->get_builder(
			array(
				'form_id' => $this->additional->form_id ?? false,
				'actions' => $this->additional->actions ?? false,
			)
		);
	}

}
