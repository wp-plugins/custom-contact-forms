<?php

class CCF_API extends WP_JSON_Posts {

	/**
	 * Array of field attributes with sanitization/escaping callbacks
	 *
	 * @var array
	 * @since 6.0
	 */
	protected $field_attribute_keys;

	/**
	 * Array of field choice attributes with sanitization/escaping callbacks
	 *
	 * @var array
	 * @since 6.0
	 */
	protected $choice_attribute_keys;

	/**
	 * Setup hook to prepare returned form. Setup field/choice attributes with callbacks
	 *
	 * @param WP_JSON_ResponseHandler $server
	 * @since 6.0
	 */
	public function __construct( $server ) {
		parent::__construct( $server );

		add_filter( 'json_prepare_post', array( $this, 'filter_prepare_post' ), 10, 3 );

		$this->field_attribute_keys = apply_filters( 'ccf_field_attributes', array(
			'type' => array(
				'sanitize' => 'esc_attr',
				'escape' => 'esc_attr',
			),
			'slug' => array(
				'sanitize' => 'esc_attr',
				'escape' => 'esc_attr',
			),
			'placeholder' => array(
				'sanitize' => 'esc_attr',
				'escape' => 'esc_attr',
			),
			'className' => array(
				'sanitize' => 'esc_attr',
				'escape' => 'esc_attr',
			),
			'label' => array(
				'sanitize' => 'sanitize_text_field',
				'escape' => 'esc_html',
			),
			'value' => array(
				'sanitize' => 'sanitize_text_field',
				'escape' => 'esc_html',
			),
			'required' => array(
				'sanitize' => array( $this, 'boolval' ),
				'escape' => array( $this, 'boolval' ),
			),
			'showDate' => array(
				'sanitize' => array( $this, 'boolval' ),
				'escape' => array( $this, 'boolval' ),
			),
			'addressType' => array(
				'sanitize' => 'esc_attr',
				'escape' => 'esc_attr',
			),
			'phoneFormat' => array(
				'sanitize' => 'esc_attr',
				'escape' => 'esc_attr',
			),
			'emailConfirmation' => array(
				'sanitize' => array( $this, 'boolval' ),
				'escape' => array( $this, 'boolval' ),
			),
			'showTime' => array(
				'sanitize' => array( $this, 'boolval' ),
				'escape' => array( $this, 'boolval' ),
			),
			'heading' => array(
				'sanitize' => 'sanitize_text_field',
				'escape' => 'esc_html',
			),
			'subheading' => array(
				'sanitize' => 'sanitize_text_field',
				'escape' => 'esc_html',
			),
			'html' => array(
				'sanitize' => 'wp_kses_post',
				'escape' => 'wp_kses_post',
			),
		) );

		$this->choice_attribute_keys = apply_filters( 'ccf_choice_attributes', array(
			'label' => array(
				'sanitize' => 'sanitize_text_field',
				'escape' => 'esc_html',
			),
			'value' => array(
				'sanitize' => 'esc_attr',
				'escape' => 'esc_attr',
			),
			'selected' => array(
				'sanitize' => array( $this, 'boolval' ),
				'escape' => array( $this, 'boolval' ),
			),
		) );
	}

	/**
	 * Ensure value is boolean
	 *
	 * @param mixed $value
	 * @since 6.0
	 * @return bool
	 */
	protected function boolval( $value ) {
		return !! $value;
	}

	/**
	 * Register API routes. We only need to get all forms and get specific forms. Right now specific endpoints
	 * for fields/choices are not really necessary.
	 *
	 * @param array $routes
	 * @since 6.0
	 * @return array
	 */
	public function register_routes( $routes ) {
		$routes['/ccf/forms'] = array(
			array( array( $this, 'get_forms'), WP_JSON_Server::READABLE ),
			array( array( $this, 'create_form'), WP_JSON_Server::CREATABLE | WP_JSON_Server::ACCEPT_JSON ),
		);

		$routes['/ccf/forms/(?P<id>\d+)'] = array(
			array( array( $this, 'get_form'), WP_JSON_Server::READABLE ),
			array( array( $this, 'edit_form'), WP_JSON_Server::EDITABLE | WP_JSON_Server::ACCEPT_JSON ),
			array( array( $this, 'delete_form'), WP_JSON_Server::DELETABLE ),
		);

		$routes['/ccf/forms/(?P<id>\d+)/fields'] = array(
			array( array( $this, 'get_fields'), WP_JSON_Server::READABLE ),
		);

		$routes['/ccf/forms/(?P<id>\d+)/submissions'] = array(
			array( array( $this, 'get_submissions'), WP_JSON_Server::READABLE ),
		);

		return $routes;
	}

	/**
	 * Handle field deletion. We need to delete choices attached to the field too. Not an API route.
	 *
	 * @param int $form_id
	 * @since 6.0
	 */
	public function delete_fields( $form_id ) {
		$attached_fields = get_post_meta( $form_id, 'ccf_attached_fields', true );

		if ( ! empty( $attached_fields ) ) {
			foreach ( $attached_fields as $field_id ) {
				$this->delete_choices( $field_id );

				wp_delete_post( $field_id, true );
			}
		}
	}

	/**
	 * Delete all submissions associated with a post.
	 *
	 * @param int $form_id
	 * @since 6.0
	 */
	public function delete_submission( $form_id ) {
		$submissions = get_children( array( 'post_parent' => $form_id, 'numberposts' => apply_filters( 'ccf_max_submissions', 5000, get_post( $form_id ) ) ) );
		if ( ! empty( $submissions ) ) {
			foreach ( $submissions as $submission ) {
				wp_delete_post( $submission->ID, true );
			}
		}
	}

	/**
	 * Delete field choices. Not an API route.
	 *
	 * @param int $field_id
	 * @since 6.0
	 */
	public function delete_choices( $field_id ) {
		$attached_choices = get_post_meta( $field_id, 'ccf_attached_choices', true );

		if ( ! empty( $attached_choices ) ) {
			foreach ( $attached_choices as $choice_id ) {
				wp_delete_post( $choice_id, true );
			}
		}

	}

	/**
	 * Add in some extra attributes unique to forms to return from the API
	 *
	 * @param array $_post
	 * @param array $post
	 * @param string $context
	 * @since 6.0
	 * @return array
	 */
	public function filter_prepare_post( $_post, $post, $context ) {
		if ( 'ccf_form' === $_post['type'] ) {
			$_post['fields'] = $this->_get_fields( $post['ID'] );

			$_post['buttonText'] = esc_attr( get_post_meta( $post['ID'], 'ccf_form_buttonText', true ) );
			$_post['description'] = esc_html( get_post_meta( $post['ID'], 'ccf_form_description', true ) );
			$_post['completionActionType'] = esc_attr( get_post_meta( $post['ID'], 'ccf_form_completion_action_type', true ) );
			$_post['completionRedirectUrl'] = esc_url_raw( get_post_meta( $post['ID'], 'ccf_form_completion_redirect_url', true ) );
			$_post['completionMessage'] = esc_html( get_post_meta( $post['ID'], 'ccf_form_completion_message', true ) );
			$_post['sendEmailNotifications'] = (bool) get_post_meta( $post['ID'], 'ccf_form_send_email_notifications', true );
			$_post['emailNotificationAddresses'] = esc_html( get_post_meta( $post['ID'], 'ccf_form_email_notification_addresses', true ) );

			$submissions = get_children( array( 'post_parent' => $post['ID'], 'numberposts' => array( 'ccf_max_submissions', 5000, $post ) ) );
			$_post['submissions'] = esc_html( count( $submissions ) );
		} elseif ( 'ccf_submission' === $_post['type'] ) {
			$_post['data'] = get_post_meta( $_post['ID'], 'ccf_submission_data', true );
		}

		return $_post;
	}

	/**
	 * Get fields. This is an API endpoint.
	 *
	 * @param int $id
	 * @since 6.0
	 * @return WP_Error|WP_JSON_Response
	 */
	public function get_fields( $id ) {
		$id = (int) $id;

		if ( empty( $id ) ) {
			return new WP_Error( 'json_invalid_id_ccf_form', esc_html__( 'Invalid form ID.', 'custom-contact-forms' ), array( 'status' => 404 ) );
		}

		$post_type = get_post_type_object( 'ccf_form' );
		if ( ! current_user_can( $post_type->cap->edit_posts, $id ) ) {
			return new WP_Error( 'json_cannot_view_ccf_forms', esc_html__( 'Sorry, you cannot view forms.', 'custom-contact-forms' ), array( 'status' => 403 ) );
		}

		$fields = $this->_get_fields( $id );

		$response = new WP_JSON_Response();
		$response->set_status( 200 );

		$response->set_data( $fields );

		return $response;
	}

	/**
	 * Get fields given a form ID. Not an API route.
	 *
	 * @param int $form_id
	 * @return array
	 */
	public function _get_fields( $form_id ) {
		$fields = array();

		$attached_fields = get_post_meta( $form_id, 'ccf_attached_fields', true );

		if ( ! empty( $attached_fields ) ) {
			foreach ( $attached_fields as $field_id ) {
				$field = array( 'ID' => $field_id );

				foreach ( $this->field_attribute_keys as $key => $functions ) {
					$value = get_post_meta( $field_id, 'ccf_field_' . $key );

					if ( isset( $value[0] ) ) {
						$field[$key] = call_user_func( $functions['escape'], $value[0] );
					}
				}

				$choices = get_post_meta( $field_id, 'ccf_attached_choices' );

				if ( ! empty( $choices ) ) {
					$field['choices'] = array();

					if ( ! empty( $choices[0] ) ) {
						foreach ( $choices[0] as $choice_id ) {
							$choice = array( 'ID' => $choice_id );

							foreach ( $this->choice_attribute_keys as $key => $functions ) {
								$value = get_post_meta( $choice_id, 'ccf_choice_' . $key );

								if ( isset( $value[0] ) ) {
									$choice[$key] = call_user_func( $functions['escape'], $value[0] );
								}
							}

							$field['choices'][] = $choice;
						}
					}
				}

				$fields[] = $field;
			}
		}

		return $fields;
	}

	/**
	 * Create a form. This is an API endpoint
	 *
	 * @param array $data
	 * @since 6.0
	 * @return int|WP_Error|WP_JSON_ResponseInterface
	 */
	public function create_form( $data ) {
		unset( $data['ID'] );

		$result = $this->insert_post( $data );
		if ( $result instanceof WP_Error ) {
			return $result;
		}

		if ( ! empty( $data['fields'] ) ) {
			$this->create_and_map_fields( $data['fields'], $result );
		}

		if ( isset( $data['buttonText'] ) ) {
			update_post_meta( $result, 'ccf_form_buttonText', sanitize_text_field( $data['buttonText'] ) );
		}

		if ( isset( $data['description'] ) ) {
			update_post_meta( $result, 'ccf_form_description', sanitize_text_field( $data['description'] ) );
		}

		if ( isset( $data['completionActionType'] ) ) {
			update_post_meta( $result, 'ccf_form_completion_action_type', sanitize_text_field( $data['completionActionType'] ) );
		}

		if ( isset( $data['completionMessage'] ) ) {
			update_post_meta( $result, 'ccf_form_completion_message', sanitize_text_field( $data['completionMessage'] ) );
		}

		if ( isset( $data['completionRedirectUrl'] ) ) {
			update_post_meta( $result, 'ccf_form_completion_redirect_url', esc_url_raw( $data['completionRedirectUrl'] ) );
		}

		if ( isset( $data['sendEmailNotifications'] ) ) {
			update_post_meta( $result, 'ccf_form_send_email_notifications', (bool) $data['sendEmailNotifications'] );
		}

		if ( isset( $data['emailNotificationAddresses'] ) ) {
			update_post_meta( $result, 'ccf_form_email_notification_addresses', sanitize_text_field( $data['emailNotificationAddresses'] ) );
		}

		$response = json_ensure_response( $this->get_post( $result ) );
		$response->set_status( 201 );
		$response->header( 'Location', json_url( '/ccf/forms/' . $result ) );

		return $response;
	}

	/**
	 * Create field choices and attach them to fields. Not an API route.
	 *
	 * @param array $choices
	 * @param int $field_id
	 * @since 6.0
	 */
	public function create_and_map_choices( $choices, $field_id ) {
		$new_choices = array();

		foreach ( $choices as $choice ) {
			if ( ! empty( $choice['label'] ) ) {
				if ( empty( $choice['ID'] ) ) {
					$args = array(
						'post_title' => $choice['label'] . '-' . (int) $field_id,
						'post_author' => 1,
						'post_status' => 'publish',
						'post_parent' => $field_id,
						'post_type' => 'ccf_choice',
					);

					$choice_id = wp_insert_post( $args );
				} else {
					$choice_id = $choice['ID'];
				}

				if ( ! is_wp_error( $choice_id ) ) {
					foreach ( $this->choice_attribute_keys as $key => $functions ) {
						if ( isset( $choice[$key] ) ) {
							update_post_meta( $choice_id, 'ccf_choice_' . $key, call_user_func( $functions['sanitize'], $choice[$key] ) );
						}
					}

					$new_choices[] = $choice_id;
				}
			} else {
				if ( ! empty( $choice['ID'] ) ) {
					wp_delete_post( $choice['ID'], true );
				}
			}
		}

		$current_choices = get_post_meta( $field_id, 'ccf_attached_choices', true );
		$new_choices = array_map( 'absint', $new_choices );

		if ( ! empty( $current_choices ) ) {
			$deleted_choices = array_diff( $current_choices, $new_choices );
			foreach ( $deleted_choices as $choice_id ) {
				wp_delete_post( $choice_id, true );
			}
		}

		update_post_meta( $field_id, 'ccf_attached_choices', array_map( 'absint', $new_choices ) );
	}

	/**
	 * Create fields and map them to forms. Not an API route.
	 *
	 * @param array $fields
	 * @param int $form_id
	 * @since 6.0
	 */
	public function create_and_map_fields( $fields, $form_id ) {
		$new_fields = array();

		foreach ( $fields as $field ) {
			if ( empty( $field['ID'] ) ) {
				$args = array(
					'post_title' => $field['slug'] . '-' . (int) $form_id,
					'post_author' => 1,
					'post_status' => 'publish',
					'post_parent' => $form_id,
					'post_type' => 'ccf_field',
				);

				$field_id = wp_insert_post( $args );
			} else {
				$field_id = $field['ID'];
			}

			if ( ! is_wp_error( $field_id ) ) {
				foreach ( $this->field_attribute_keys as $key => $functions ) {
					if ( isset( $field[$key] ) ) {
						update_post_meta( $field_id, 'ccf_field_' . $key, call_user_func( $functions['sanitize'], $field[$key] ) );
					}
				}

				if ( isset( $field['choices'] ) ) {
					$choices = ( empty( $field['choices'] ) ) ? array() : $field['choices'];
					$this->create_and_map_choices( $choices, $field_id );
				}

				$new_fields[] = $field_id;
			}
		}

		$current_fields = get_post_meta( $form_id, 'ccf_attached_fields', true );
		$new_fields = array_map( 'absint', $new_fields );

		if ( ! empty( $current_fields ) ) {
			$deleted_fields = array_diff( $current_fields, $new_fields );
			foreach ( $deleted_fields as $field_id ) {
				wp_delete_post( $field_id, true );
			}
		}

		update_post_meta( $form_id, 'ccf_attached_fields', $new_fields );
	}

	/**
	 * Setup custom routes
	 *
	 * @since 6.0
	 */
	public function register_filters() {
		add_filter( 'json_endpoints', array( $this, 'register_routes' ) );
	}

	/**
	 * Return forms. This is an API endpoint.
	 *
	 * @param array $filter
	 * @param string $context
	 * @param string $type
	 * @param int $page
	 * @since 6.0
	 * @return object|WP_Error
	 */
	public function get_forms( $filter = array(), $context = 'edit', $type = null, $page = 1 ) {
		$post_type = get_post_type_object( 'ccf_form' );
		if ( ! current_user_can( $post_type->cap->edit_posts ) ) {
			return new WP_Error( 'json_cannot_view_ccf_forms', esc_html__( 'Sorry, you cannot view forms.', 'custom-contact-forms' ), array( 'status' => 403 ) );
		}

		return parent::get_posts( $filter, $context, 'ccf_form', $page );
	}

	/**
	 * Return submissions. This is an API endpoint.
	 *
	 * @since 6.0
	 */
	public function get_submissions( $id, $filter = array(), $context = 'edit', $type = null, $page = 1 ) {
		$id = (int) $id;

		if ( empty( $id ) ) {
			return new WP_Error( 'json_invalid_id_ccf_form', esc_html__( 'Invalid form ID.', 'custom-contact-forms' ), array( 'status' => 404 ) );
		}

		$post_type = get_post_type_object( 'ccf_form' );
		if ( ! current_user_can( $post_type->cap->edit_posts, $id ) ) {
			return new WP_Error( 'json_cannot_view_ccf_forms', esc_html__( 'Sorry, you cannot view forms.', 'custom-contact-forms' ), array( 'status' => 403 ) );
		}

		$filter['post_parent'] = $id;

		return parent::get_posts( $filter, $context, 'ccf_submission', $page );
	}

	/**
	 * Return a form given an ID. This is an API endpoint.
	 *
	 * @param int $id
	 * @param string $context
	 * @since 6.0
	 * @return array|WP_Error
	 */
	public function get_form( $id, $context = 'view' ) {
		$id = (int) $id;

		if ( empty( $id ) ) {
			return new WP_Error( 'json_invalid_id_ccf_form', esc_html__( 'Invalid form ID.', 'custom-contact-forms' ), array( 'status' => 404 ) );
		}

		$form = get_post( $id, ARRAY_A );

		if ( empty( $form ) ) {
			return new WP_Error( 'json_invalid_ccf_form', esc_html__( 'Invalid form.', 'custom-contact-forms' ), array( 'status' => 404 ) );
		}

		if ( ! json_check_post_permission( $form, 'read' ) ) {
			return new WP_Error( 'json_cannot_view_ccf_form', esc_html__( 'Sorry, you cannot view this form.', 'custom-contact-forms' ), array( 'status' => 403 ) );
		}

		return parent::get_post( $id, $context );
	}

	/**
	 * Edit a form given an ID. This is an API endpoint.
	 *
	 * @param int $id
	 * @param array $data
	 * @param array $_headers
	 * @since 6.0
	 * @return int|WP_Error|WP_JSON_ResponseInterface
	 */
	function edit_form( $id, $data, $_headers = array() ) {
		$id = (int) $id;

		if ( empty( $id ) ) {
			return new WP_Error( 'json_invalid_id_ccf_form', esc_html__( 'Invalid form ID.', 'custom-contact-forms' ), array( 'status' => 404 ) );
		}

		$form = get_post( $id, ARRAY_A );

		if ( empty( $form['ID'] ) ) {
			return new WP_Error( 'json_invalid_ccf_form', esc_html__( 'Invalid form.', 'custom-contact-forms' ), array( 'status' => 404 ) );
		}

		$result = $this->insert_post( $data );
		if ( $result instanceof WP_Error ) {
			return $result;
		}

		if ( ! empty( $data['fields'] ) ) {
			$this->create_and_map_fields( $data['fields'], $result );
		}

		if ( isset( $data['buttonText'] ) ) {
			update_post_meta( $result, 'ccf_form_buttonText', sanitize_text_field( $data['buttonText'] ) );
		}

		if ( isset( $data['description'] ) ) {
			update_post_meta( $result, 'ccf_form_description', sanitize_text_field( $data['description'] ) );
		}

		if ( isset( $data['completionActionType'] ) ) {
			update_post_meta( $result, 'ccf_form_completion_action_type', sanitize_text_field( $data['completionActionType'] ) );
		}

		if ( isset( $data['completionMessage'] ) ) {
			update_post_meta( $result, 'ccf_form_completion_message', sanitize_text_field( $data['completionMessage'] ) );
		}

		if ( isset( $data['completionRedirectUrl'] ) ) {
			update_post_meta( $result, 'ccf_form_completion_redirect_url', esc_url_raw( $data['completionRedirectUrl'] ) );
		}

		if ( isset( $data['sendEmailNotifications'] ) ) {
			update_post_meta( $result, 'ccf_form_send_email_notifications', (bool) $data['sendEmailNotifications'] );
		}

		if ( isset( $data['emailNotificationAddresses'] ) ) {
			update_post_meta( $result, 'ccf_form_email_notification_addresses', sanitize_text_field( $data['emailNotificationAddresses'] ) );
		}

		$response = json_ensure_response( $this->get_post( $result ) );

		$response->set_status( 201 );
		$response->header( 'Location', json_url( '/ccf/forms/' . $result ) );

		return $response;
	}

	/**
	 * Delete a form given an ID. This is an API endpoint.
	 *
	 * @param int $id
	 * @param bool $force
	 * @since 6.0
	 * @return true|WP_Error
	 */
	public function delete_form( $id, $force = false ) {
		$id = (int) $id;

		if ( empty( $id ) ) {
			return new WP_Error( 'json_invalid_id_ccf_form', esc_html__( 'Invalid form ID.', 'custom-contact-forms' ), array( 'status' => 404 ) );
		}

		if ( $force ) {
			$this->delete_fields( $id );
			$this->delete_submissions( $id );
		}

		$result = wp_trash_post( $id );

		if ( ! $result ) {
			return new WP_Error( 'json_cannot_delete', esc_html__( 'The form cannot be deleted.', 'custom-contact-forms' ), array( 'status' => 500 ) );
		}

		if ( $force ) {
			return array( 'message' => esc_html__( 'Permanently deleted form', 'custom-contact-forms' ) );
		} else {
			// TODO: return a HTTP 202 here instead
			return array( 'message' => esc_html__( 'Deleted post', 'custom-contact-forms' ) );
		}
	}

	/**
	 * Prepare a form for output
	 *
	 * @param array $post
	 * @param string $context
	 * @since 6.0
	 * @return array
	 */
	/*protected function prepare_form( $post, $context = 'edit' ) {
		$_post = parent::prepare_post( $post, $context );

		// Override entity meta keys with the correct links
		$_post['meta'] = array(
			'links' => array(
				'self' => json_url( '/ccf/forms/' . $post['ID'] ),
				'author' => json_url( '/users/' . $post['post_author'] ),
				'collection' => json_url( '/ccf/forms' ),
			),
		);

		if ( ! empty( $post['post_parent'] ) ) {
			$_post['meta']['links']['up'] = json_url(  '/ccf/forms/' . $post['ID'] );
		}

		return apply_filters( "json_prepare_ccf_form", $_post, $post, $context );
	}*/
}