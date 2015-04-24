<?php
/**
 * Plugin Name: Free Form
 * Plugin URI: http://marcusbattle.com/plugins/free-form
 * Description: A flexible form builder built on CMB2
 * Version: 0.0.1
 * Author: Marcus Battle
 * Author URI: http://marcusbattle.com
 * Text Domain: freeform
 * Network: Optional. Whether the plugin can only be activated network wide. Example: true
 * License: GPL2
 */

class Free_Form {

	public function __construct() { 
		
		require_once plugin_dir_path( __FILE__ ) . "includes/CMB2/init.php";

		// Custom Post Types for Forms & Entries
		add_action( 'init', array( $this, 'create_form_post_type' ) );
		add_action( 'init', array( $this, 'create_entry_post_type' ) );

		// CMB2 Metabox support for Credit Cards
		add_filter( 'cmb2_meta_boxes', array( $this, 'cmb2_form_fields' ), 10 );
		add_filter( 'cmb2_meta_boxes', array( $this, 'cmb2_entry' ), 10 );
		add_action( 'cmb2_render_primary_email', array( $this, 'cmb2_render_primary_email' ), 10, 5 );
		add_action( 'cmb2_render_field_type_select', array( $this, 'cmb2_render_field_type_select' ), 10, 5 );
		add_filter( 'cmb2_sanitize_field_type_select', array( $this, 'cmb2_sanitize_field_type_select' ), 10, 2 );

		add_filter( 'the_content', array( $this, 'display_form' ), 10 );
		add_filter( 'freeform_fields', array( $this, 'get_form_fields' ), 10, 2 );
		add_filter( 'freeform_form', array( $this, 'render_form' ), 10, 1 );
		add_shortcode( 'freeform', array( $this, 'freeform_shortcode' ) );

		add_action( 'wp_enqueue_scripts', array( $this, 'load_styles_and_scripts' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_load_styles_and_scripts' ) );
		add_action( 'init', array( $this, 'submit_freeform' ) );

		// Entries
		add_filter( 'manage_entry_posts_columns', array( $this, 'entry_columns' ) );
		add_action( 'manage_entry_posts_custom_column' , array( $this, 'entry_column_data' ), 10, 2 );

		// Ajax Support
		add_action( 'wp_ajax_submit_freeform', array( $this, 'submit_freeform' ) );
		
		// WP Actions
		add_action( 'after_submit_freeform_create_user', array( $this, 'create_user' ), 10, 2 );
		add_action( 'after_submit_freeform_login_user', array( $this, 'login_user' ), 10, 2 );

	}

	public function load_styles_and_scripts() {
		
		// CSS Styles
		wp_enqueue_style( 'font-awesome', '//maxcdn.bootstrapcdn.com/font-awesome/4.3.0/css/font-awesome.min.css' );
		wp_enqueue_style( 'freeform', plugin_dir_url( __FILE__ ) . 'assets/css/freeform.css' );

		// Javascript
		wp_enqueue_script( 'maskedinput', plugin_dir_url( __FILE__ ) . '/assets/js/jquery.maskedinput.min.js' , array('jquery'), '1.4.0', true );
		wp_enqueue_script( 'freeform', plugin_dir_url( __FILE__ ) . '/assets/js/freeform.js' , array('jquery'), '0.1.0', true );

		wp_localize_script( 'freeform', 'freeform',
            array( 
            	'ajax_url' => admin_url( 'admin-ajax.php' )
            )
        );

	}

	public function admin_load_styles_and_scripts() {

		wp_enqueue_style( 'font-awesome', '//maxcdn.bootstrapcdn.com/font-awesome/4.3.0/css/font-awesome.min.css' );
		wp_enqueue_script( 'freeform', plugin_dir_url( __FILE__ ) . '/assets/js/freeform.admin.js' , array('jquery'), '0.1.0', true );

	}

	public function create_form_post_type() {
		
		$labels = array(
			'name'               => _x( 'Forms', 'post type general name', 'freeform' ),
			'singular_name'      => _x( 'Form', 'post type singular name', 'freeform' ),
			'menu_name'          => _x( 'Forms', 'admin menu', 'freeform' ),
			'name_admin_bar'     => _x( 'Form', 'add new on admin bar', 'freeform' ),
			'add_new'            => _x( 'Add New', 'form', 'freeform' ),
			'add_new_item'       => __( 'Add New Form', 'freeform' ),
			'new_item'           => __( 'New Form', 'freeform' ),
			'edit_item'          => __( 'Edit Form', 'freeform' ),
			'view_item'          => __( 'View Form', 'freeform' ),
			'all_items'          => __( 'All Forms', 'freeform' ),
			'search_items'       => __( 'Search Forms', 'freeform' ),
			'parent_item_colon'  => __( 'Parent Forms:', 'freeform' ),
			'not_found'          => __( 'No forms found.', 'freeform' ),
			'not_found_in_trash' => __( 'No forms found in Trash.', 'freeform' )
		);

		$args = array(
			'labels'             => $labels,
			'public'             => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'query_var'          => true,
			'rewrite'            => array( 'slug' => 'forms' ),
			'capability_type'    => 'page',
			'has_archive'        => true,
			'hierarchical'       => false,
			'menu_position'      => null,
			'supports'           => array( 'title', 'thumbnail' )
		);

		register_post_type( 'form', $args );

	}

	public function create_entry_post_type() {

		$labels = array(
			'name'               => _x( 'Entries', 'post type general name', 'freeform' ),
			'singular_name'      => _x( 'Entry', 'post type singular name', 'freeform' ),
			'menu_name'          => _x( 'Entries', 'admin menu', 'freeform' ),
			'name_admin_bar'     => _x( 'Entry', 'add new on admin bar', 'freeform' ),
			'add_new'            => _x( 'Add New', 'entry', 'freeform' ),
			'add_new_item'       => __( 'Add New Entry', 'freeform' ),
			'new_item'           => __( 'New Entry', 'freeform' ),
			'edit_item'          => __( 'Edit Entry', 'freeform' ),
			'view_item'          => __( 'View Entry', 'freeform' ),
			'all_items'          => __( 'All Entries', 'freeform' ),
			'search_items'       => __( 'Search Entry', 'freeform' ),
			'parent_item_colon'  => __( 'Parent Entries:', 'freeform' ),
			'not_found'          => __( 'No entries found.', 'freeform' ),
			'not_found_in_trash' => __( 'No entries found in Trash.', 'freeform' )
		);

		$args = array(
			'labels'             => $labels,
			'public'             => false,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'query_var'          => true,
			'rewrite'            => array( 'slug' => 'entries' ),
			'capability_type'    => 'post',
			'has_archive'        => true,
			'hierarchical'       => false,
			'menu_position'      => null,
			'supports'           => array( 'comments' )
		);

		register_post_type( 'entry', $args );

	}

	public function cmb2_form_fields( $meta_boxes = array() ) {
		
		$prefix = 'freeform_';
		
		$meta_boxes['settings'] = array(
			'id'            => 'settings',
			'title'         => __( 'Form Settings', 'freeform' ),
			'object_types'  => array( 'form', ), // Post type
			'context'       => 'normal',
			'priority'      => 'high',
			'fields'        => array(
				array(
					'name' => __( 'Redirect Behavior', 'freeform' ),
					'id'   => $prefix . 'redirect_action',
					'type' => 'select',
					'options' => array(
						'' => 'None',
						'redirect_to_page' => __( 'Redirect to Page/Post', 'freeform' ),
						'redirect_to_url' => __( 'Redirect to URL', 'freeform' ),
						'refresh' => __( 'Refresh', 'freeform' )
					)
				),
				array(
					'name' => __( 'Confirmation Message', 'freeform' ),
					'id'   => $prefix . 'confirmation_message',
					'type' => 'textarea_small',
					'desc' => __( 'The message the the user sees when they submit the form', 'freeform' ),
				),
				array(
					'name' => __( 'Confirmation Email', 'freeform' ),
					'id'   => $prefix . 'confirmation_email',
					'type'    => 'wysiwyg',
					'options' => array( 'textarea_rows' => 4, )
				),
				/* s
				array(
					'name' => __( 'Who can see this form?', 'freeform' ),
					'id'   => $prefix . 'visibility',
					'type' => 'select',
					'options' => array(
						'everyone' => __( 'Everyone', 'freeform' )
					)
				), */
				array(
					'name' => __( 'Send Copy To', 'freeform' ),
					'id'   => $prefix . 'email_copy_to',
					'type' => 'primary_email',
					'required' => true,
					'options' => array(
						'' => __( '-- Please create and select an email field --', 'freeform' )
					),
					'desc' => __( 'Create and select an email field so the user can get a copy of their submission', 'freeform' ),
				),
				array(
					'name' => __( 'WP Action', 'freeform' ),
					'desc' => __( 'Your form can perform a WordPress based action on submit', 'freeform' ),
					'id'   => $prefix . 'wp_action',
					'type' => 'select',
					'options' => array(
						'' => '--',
						'create_user' => __( 'Create User', 'freeform' ),
						'login_user' => __( 'Login User', 'freeform' )
					)
				),
				array(
					'name' => __( 'Custom Action', 'freeform' ),
					'desc' => __( 'Your form can perform a custom action based action on submit', 'freeform' ),
					'id'   => $prefix . 'custom_action',
					'type' => 'select',
					'options' => apply_filters('freeform_custom_actions', array( '' => '--' ) )
				),
			)
		); 
		
		$form_fields = apply_filters( 'freeform_form_fields', array( 
			array(
				'name' => 'Label',
				'id'   => 'label',
				'type' => 'text',
				'required' => true
				// 'repeatable' => true, // Repeatable fields are supported w/in repeatable groups (for most types)
			),
			array(
				'name' => __( 'Required?', 'freeform' ),
				'id'   => 'required',
				'type' => 'checkbox',
			),
			array(
				'name' => 'Field Type',
				'id'   => 'field',
				'type'    => 'select',
				'options' => apply_filters( 'freeform_field_types', array(
					''				=> '--',
					'date' 			=> __( 'Date', 'freeform' ),
					'select' 		=> __( 'Dropdown', 'freeform' ),
					'email' 		=> __( 'Email', 'freeform' ),
					'name'   		=> __( 'Name', 'freeform' ),
					'textarea'  	=> __( 'Paragraph', 'freeform' ),
					'password'   	=> __( 'Password', 'freeform' ),
					'phone_number'  => __( 'Phone Number', 'freeform' ),
					'text' 			=> __( 'Text', 'freeform' ),
					'text_time'   	=> __( 'Time', 'freeform' ),

				)),
			),
		) );

		$meta_boxes['form_fields'] = array(
			'id'           => 'form_fields',
			'title'        => __( 'Form Fields', 'freeform' ),
			'object_types' => array( 'form' ),
			'fields'       => array(
				array(
					'id'          => $prefix . 'form_fields',
					'type'        => 'group',
					'options'     => array(
						'group_title'   => __( 'Question {#}', 'freeform' ), // {#} gets replaced by row number
						'add_button'    => __( 'Add Another Field', 'freeform' ),
						'remove_button' => __( 'Remove Field', 'freeform' ),
						'sortable'      => true, // beta
					),
					// Fields array works the same, except id's only need to be unique for this group. Prefix is not needed.
					'fields'      => $form_fields,
				),
			),
		);
	
		return $meta_boxes;

	}

	public function cmb2_entry( $meta_boxes = array() ) {
		
		$prefix = 'freeform_entry';
		
		$meta_boxes['entry'] = array(
			'id'            => 'entry',
			'title'         => __( 'Entry', 'freeform' ),
			'object_types'  => array( 'entry' ), // Post type
			'context'       => 'normal',
			'priority'      => 'high',
			'fields'        => array(
				array(
					'name' => __( 'Form ID', 'freeform' ),
					'id'   => 'form_id',
					'type' => 'text'
				),
			)
		); 

		return $meta_boxes;

	}

	public function display_form( $content ) {

		if ( get_post_type() !== 'form' ) {
			return $content;
		}

		$form = apply_filters( 'freeform_form', get_the_ID() );

		return $content . $form;

	}

	public function get_form_fields( $form_id, $form_fields ) {
		
		$form_fields = get_post_meta( $form_id, 'freeform_form_fields', true );

		return $form_fields;

	}

	public function render_form( $form_id = 0 ) {
		
		if ( ! $form_id ) {
			return "Please specify which form you want to learn";
		}

		$form_fields = apply_filters( 'freeform_fields', $form_id, array() );
		
		if ( $form_fields ) {

			$form  = '<p>Fields marked with (<span class="asterisk">*</span>) are mandatory.</p>';
			$form .= '<form class="freeform-form" method="POST">';
			$form .= '<input type="hidden" name="action" value="submit_freeform" />';
			$form .= '<input type="hidden" name="form_id" value="' . $form_id . '" />';
			$form .= '<input type="hidden" name="use_ajax" value="true" />';

			foreach ( $form_fields as $field ) {

				$label = isset( $field['label'] ) ? $field['label'] : '';
				$field = isset( $field['field'] ) ? $field['field'] : '';
				$is_required = isset( $field['required'] ) && ! empty( $field['required'] ) ? 'required' : '';
				
				$label = $this->render_label( $label, $is_required );
				$input = $this->render_input( $field, $label, $form_id );
				
				$form .= "<div class=\"freeform-field $is_required\">{$label}{$input}</div>";

			} 

			$form .= '<button type="submit">Submit</button>';
			$form .= '</form>';
		
		}

		return $form;

	}

	public function render_label( $label, $is_required = false ) {

		$asterisk = ( $is_required ) ? ' <span class="asterisk">*</span>' : '';

		return '<label>' . $label . $asterisk . '</label>';

	}

	public function render_input( $input, $label, $form_id ) {

		$input_name = str_replace( '-', '_', sanitize_title_with_dashes( $label ) );

		switch ( $input ) {
			
			case 'email':
				$input = "<input type=\"email\" name=\"$input_name\" />";
				break;

			case 'text':
				$input = "<input type=\"text\" name=\"$input_name\" />";
				break;

			case 'textarea':
				$input = "<textarea name=\"$input_name\"></textarea>";
				break;

			case 'date':
				$input = "<input type=\"text\" name=\"$input_name\" placeholder=\"MM/DD/YYYY\" />";
				break;

			case 'phone_number':
				$input = "<input type=\"text\" class=\"phone\" name=\"$input_name\" />";
				break;

			case 'password':
				$input = "<input type=\"password\" name=\"$input_name\" />";
				break;

			case 'select':
				$input = "
					<select>
						<option>One</option>
					</select>";
				break;

			case 'name':
				$input = "
					<div class=\"field-row\">
						<div class=\"field-col\">
							<input type=\"text\" name=\"{$input_name}_first_name\" />
							<span class=\"sub-label\">First Name</span>
						</div>
						<div class=\"field-col\">
							<input type=\"text\" name=\"{$input_name}_last_name\" />
							<span class=\"sub-label\">Last Name</span>
						</div>
					</div>
				";
				break;

			default:
				$input = apply_filters( "freeform_render_{$input_name}_field", $input, $input_name, $label, $form_id );
				break;
		}
		

		return $input;

	}

	public function render_field( $label_html, $input_html ) {

		return '<div class="freeform-field">' . $label_html . $input_html . '</div>';

	}

	public function submit_freeform() {

		if ( ! empty( $_POST ) && isset( $_POST['action'] ) && ( $_POST['action'] == 'submit_freeform' ) ) {
			
			$form_id = isset( $_POST['form_id'] ) ? $_POST['form_id'] : 0;
			$form = $this->get_form_by_id( $form_id );
			$use_ajax = isset( $_POST['use_ajax'] ) ? $_POST['use_ajax'] : false;

			if ( ! $form_id ) {
				// redirect with error message
			}

			$confirmation_message = get_post_meta( $form_id, 'freeform_confirmation_message', true ); 
			$confirmation_message = ! empty( $confirmation_message ) ? $confirmation_message : 'Your form has been submitted!';

			$primary_email_field = get_post_meta( $form_id, 'freeform_primary_email', true ); 
			$primary_email = isset( $_POST[ $primary_email_field ] ) ? $_POST[ $primary_email_field ] : '';

			$wp_action = get_post_meta( $form_id, 'freeform_wp_action', true );
			$custom_action = get_post_meta( $form_id, 'freeform_custom_action', true );
			$redirect_action = get_post_meta( $form_id, 'freeform_redirect_action', true );

			if ( $primary_email ) {
				mail( $primary_email, $form->post_title, $confirmation_message );
			}

			unset( $_POST['form_id'] );
			unset( $_POST['action'] );

			// Create post object
			$entry = array(
				'post_title'    => '',
				'post_content'  => '',
				'post_status'   => 'publish',
				'post_type'	  => 'entry',
			);

			// Insert the post into the database
			$entry_id = wp_insert_post( $entry );

			if ( ! $entry_id ) {
				// redirect with error message
			} else {

				do_action( "after_submit_freeform_{$wp_action}", $form_id, $_POST );
				do_action( "after_submit_freeform_{$custom_action}", $form_id, $_POST );

				update_post_meta( $entry_id, 'form_id', $form_id );
				update_post_meta( $entry_id, 'form_data', $_POST );
				
				// redirect with success message
				if ( $use_ajax ) {
				
					echo json_encode( array( 
						'success' => true, 
						'message' => $confirmation_message,
						'redirect_action' => $redirect_action
					) );

					exit;

				}

			}

			exit;

		}

	}

	public function entry_columns( $entry_columns ) {

		unset( $entry_columns['title'] );
		unset( $entry_columns['comments'] );
		unset( $entry_columns['date'] );

		$entry_columns = array(
			'cb' => '<input type="checkbox" />',
			'form_id' => __('Form'),
			'date' => __('Date')
		);

		return $entry_columns;

	}

	public function entry_column_data( $column, $entry_id ) {

		switch ( $column ) {
			case 'form_id':
				$form = $this->get_form_by_entry( $entry_id );
				echo $form->post_title;
				break;
			
			default:
				# code...
				break;
		}
	}

	public function cmb2_render_primary_email( $field, $escaped_value, $object_id, $object_type, $field_type_object ) {

		$fields = get_post_meta( $object_id, 'freeform_form_fields', true );

		if ( $fields ) {

			$field->args['options'] = array(
				'' => '-- Please select an email field --'
			);

			foreach ( $fields as $key => $value ) {
				
				if ( $value['field']['type'] == 'email' ) {

					$input_name = str_replace( '-', '_', sanitize_title_with_dashes( $value['label'] ) );
					$field->args['options'][ $input_name ] = $value['label'];

				}

			}

		}

		echo $field_type_object->select();

	}

	public function cmb2_render_field_type_select( $field, $escaped_value, $object_id, $object_type, $field_type_object ) {

		// make sure we specify each part of the value we need.
	    $value = wp_parse_args( $escaped_value, array(
	        'type' => '',
	        'settings' => '',
	    ) );
	    
	    $type_options = '';

	    // Select the proper field type and prepare select options
	    foreach( $field->args['options'] as $field_type => $field_type_label ) {
	    	$type_options .= '<option value="'. $field_type .'" '. selected( $value['type'], $field_type, false ) .'>'. $field_type_label .'</option>';
	    }

	    // Output the field type object
		echo $field_type_object->select( array(
			'name'  => $field_type_object->_name('[type]'),
			'id'    => $field_type_object->_id( '_type' ),
			'options' => $type_options
		) );

		switch ( $value['type'] ) {

			case 'select':
				
				echo '<div><p><label for="' . $field_type_object->_id('_values') . '">Dropdown Options</label></p>';

				echo $field_type_object->textarea( array(
					'class' => 'cmb2-textarea-small', 
					'rows'  => 4,
					'name'  => $field_type_object->_name('[values]'),
					'id'    => $field_type_object->_id( '_values' ),
					'value' => $value['values'],
					'desc'  => '<p class=\"cmb2-metabox-description\">Enter your choices one per line</p>'
				) );

				echo '</div>';

				break;
			
			default:
				
				echo apply_filters( 'freeform_field_type_select_values', $field_type_object, $value );

				break;

		}

	}

	/**
	 * Prevents the field type values from being formatted when certain types are selected
	 */
	public function cmb2_sanitize_field_type_select( $override_value, $value ) {


	}	

	public function get_form_by_entry( $entry_id ) {

		$form_id = get_post_meta( $entry_id, 'form_id', true );
		
		if ( $form_id ) {

			return $this->get_form_by_id( $form_id );

		}

		return false;

	}

	public function get_form_by_id( $form_id ) {
		return get_post( $form_id, OBJECT );
	}

	public function freeform_shortcode( $atts ) {
		
		$options = shortcode_atts( array(
		    'form' => '',
		    'id' => 0,
		), $atts );

		if ( $options['form'] ) {
			$form = get_page_by_title( $options['form'], OBJECT, 'form' );
		}

		if ( $form ) {
			return $this->render_form( $form->ID );
		}

	}

	public function create_user( $form_id, $form_data ) {
		echo "created that user!"; exit;
	}

	public function login_user( $form_id, $form_data ) {
		
		$username = '';
		$password = isset( $form_data['password'] ) ? $form_data['password'] : '';

		if ( isset( $form_data['email'] ) ) {
			
			$wp_user = get_user_by( 'email', $form_data['email'] );
			$username = isset( $wp_user->user_login ) ? $wp_user->user_login : '';

		}

		if ( $username ) {

			$creds = array();
			$creds['user_login'] = $username;
		    $creds['user_password'] = $password;
		    $creds['remember'] = true;

		    $user = wp_signon( $creds, false );

		}

	}

}

$FreeForm = new Free_Form();
