<?php

class CCF_Upgrader {

	public function setup() {
		add_action( 'admin_init', array( $this, 'start_upgrade' ), 100 );
	}

	public function start_upgrade() {
		global $wpdb;

		if ( ! empty( $upgraded ) ) {
			return false;
		}

		$forms = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}customcontactforms_forms" );

		$upgraded_forms = get_option( 'ccf_upgraded_forms' );

		if ( empty( $upgraded_forms ) ) {
			$upgraded_forms = array();
		}

		$type_mapping = array(
			'Dropdown' => 'dropdown',
			'Textarea' => 'paragraph_text',
			'Text' => 'single_line_text',
			'Checkbox' => 'checkboxes',
			'Radio' => 'radio',
			'fixedEmail' => 'email',
			'fixedWebsite' => 'website',
			'datePicker' => 'date',
		);

		foreach ( $forms as $form ) {
			$form_id = wp_insert_post( array(
				'post_type' => 'ccf_form',
				'post_title' => $form->form_title,
				'author' => 1,
				'post_status' => 'publish',
			) );

			if ( is_wp_error( $form_id ) ) {
				continue;
			}

			update_post_meta( $form_id, 'ccf_old_mapped_id', (int) $form->ID );

			$success_message = $form->form_success_message;
			$notification_email = $form->form_email;
			$submit_button_text = $form->submit_button_text;

			update_post_meta( $form_id, 'ccf_form_buttonText', sanitize_text_field( $submit_button_text ) );
			update_post_meta( $form_id, 'ccf_form_email_notification_addresses', sanitize_text_field( $notification_email ) );
			update_post_meta( $form_id, 'ccf_form_completion_message', sanitize_text_field( $success_message ) );
			update_post_meta( $form_id, 'ccf_form_send_email_notifications', ( ! empty( $notification_email ) ) ? true : false );

			/**
			 * Move fields over
			 */

			$fields = unserialize( $form->form_fields );

			if ( ! empty( $fields ) ) {
				$form_fields = array();

				foreach( $fields as $field_id ) {
					$field = $wpdb->get_row( sprintf( "SELECT * FROM {$wpdb->prefix}customcontactforms_fields WHERE ID='%d'", (int) $field_id ) );

					$type = $field->field_type;

					if ( ! empty( $type_mapping[$type] ) ) {
						$type = $type_mapping[$type];
					} else {
						continue;
					}

					$field_id = wp_insert_post( array(
						'post_type' => 'ccf_form',
						'post_title' => $form->form_title,
						'post_parent' => $form_id,
						'post_status' => 'publish',
					) );

					if ( ! is_wp_error( $field_id ) ) {
						$form_fields[] = $field_id;

						$slug = $field->field_slug;
						$label = $field->field_label;
						$required = $field->field_required;

						update_post_meta( $field_id, 'ccf_field_slug', sanitize_text_field( $slug ) );
						update_post_meta( $field_id, 'ccf_field_label', sanitize_text_field( $label ) );
						update_post_meta( $field_id, 'ccf_field_required', (bool) $required );
						update_post_meta( $field_id, 'ccf_field_type', sanitize_text_field( $type ) );

						if ( ( 'dropdown' === $type || 'radio' === $type || 'checkboxes' === $type ) && ! empty( $field->field_options ) ) {

							$choices = unserialize( $field->field_options );

							foreach ( $choices as $choice_id ) {
								$choice = $wpdb->get_row( sprintf( "SELECT * FROM {$wpdb->prefix}customcontactforms_field_options WHERE ID='%d'", (int) $choice_id ) );
							}
						}
					}
				}

				update_post_meta( $form_id, 'ccf_attached_fields', $form_fields );
			}

			/**
			 * Move submissions over
			 */

			$submissions = $wpdb->get_results( sprintf( "SELECT * FROM {$wpdb->prefix}customcontactforms_user_data WHERE data_formid = '%d'" , (int) $form->ID ) );

			foreach ( $submissions as $submission ) {
				$submission_id = wp_insert_post( array(
					'post_type' => 'ccf_submission',
					'post_title' => $form->form_title,
					'post_parent' => $form_id,
					'post_status' => 'publish',
					'post_date' => date( 'Y-m-d H:m:s', $submission->data_time ),
				) );

				if ( ! is_wp_error( $submission_id ) ) {
					update_post_meta( $submission_id, 'ccf_submission_data', unserialize( $submission->data_value ) );
				}
			}

			$upgraded_forms[] = (int) $form->ID;
			//update_option( 'ccf_upgraded_forms', $upgraded_forms );
		}
	}


	/**
	 * Return singleton instance of class
	 *
	 * @since 6.0
	 * @return object
	 */
	public static function factory() {
		static $instance;

		if ( ! $instance ) {
			$instance = new self();
			$instance->setup();
		}

		return $instance;
	}
}