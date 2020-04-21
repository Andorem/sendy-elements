<?php
/**
 * Class Sendy_Action_After_Submit
 * @see https://developers.elementor.com/custom-form-action/
 * Custom elementor form action after submit to add a subsciber to
 * Sendy list via API
 */
class Sendy_Action_After_Submit extends \ElementorPro\Modules\Forms\Classes\Action_Base {
	/**
	 * Get Name
	 *
	 * Return the action name
	 *
	 * @access public
	 * @return string
	 */
	public function get_name() {
		return 'sendy';
	}

	/**
	 * Get Label
	 *
	 * Returns the action label
	 *
	 * @access public
	 * @return string
	 */
	public function get_label() {
		return __( 'Sendy', 'text-domain' );
	}

	/**
	 * Run
	 *
	 * Runs the action after submit
	 *
	 * @access public
	 * @param \ElementorPro\Modules\Forms\Classes\Form_Record $record
	 * @param \ElementorPro\Modules\Forms\Classes\Ajax_Handler $ajax_handler
	 */
	public function run( $record, $ajax_handler ) {
		$settings = $record->get( 'form_settings' );

		//  Make sure that there is a Sendy installation url
		if ( empty( $settings['sendy_url'] ) ) {
			return;
		}

		//  Make sure that there is a Sendy API Key
		if ( empty( $settings['sendy_api_field'] ) ) {
			return;
		}

		//  Make sure that there is a Sendy list ID
		if ( empty( $settings['sendy_list'] ) ) {
			return;
		}

		// Make sure that there is a Sendy Email field ID
		// which is required by Sendy's API to subsribe a user
		if ( empty( $settings['sendy_email_field'] ) ) {
			return;
		}

		// Get sumitetd Form data
		$raw_fields = $record->get( 'fields' );

		// Normalize the Form Data
		$fields = [];
		foreach ( $raw_fields as $id => $field ) {
			$fields[ $id ] = $field['value'];
		}

		// Make sure that the user emtered an email
		// which is required by Sendy's API to subsribe a user
		if ( empty( $fields[ $settings['sendy_email_field'] ] ) ) {
			return;
		}

		// Prepare custom fields for request
		$custom_fields = array();
		foreach (  $settings['sendy_custom_field_list'] as $field_item ) {
			$field_name = $field_item['sendy_custom_field_name'];
			$field_id = $field_item['sendy_custom_field_id'];
			if ($field_name && $field_id) {
				$field_value = $fields[$field_id];
				$custom_fields[$field_name] = $field_value;
			}
		}

		// If we got this far we can start building our request data
		// Based on the param list at https://sendy.co/api
		$sendy_data = [
      'name' => $fields[ $settings['sendy_name_field'] ],
			'email' => $fields[ $settings['sendy_email_field'] ],
			'list' => $settings['sendy_list'],
			'api_key' => $settings['sendy_api_field'],
			'ipaddress' => \ElementorPro\Core\Utils::get_client_ip(),
			'referrer' => isset( $_POST['referrer'] ) ? $_POST['referrer'] : '',
		];

		// Add custom fields to request data
		if (!empty($custom_fields)) {
			foreach ($custom_fields as $key => $value) {
				$sendy_data[$key] = $value;
			}
		}

		// add name if field is mapped
		if ( empty( $fields[ $settings['sendy_name_field'] ] ) ) {
			$sendy_data['name'] = $fields[ $settings['sendy_name_field'] ];
		}

		// Send the request
		wp_remote_post( $settings['sendy_url'] . 'subscribe', [
			'body' => $sendy_data,
		] );
	}

	/**
	 * Register Settings Section
	 *
	 * Registers the Action controls
	 *
	 * @access public
	 * @param \Elementor\Widget_Base $widget
	 */
	public function register_settings_section( $widget ) {
		$widget->start_controls_section(
			'section_sendy',
			[
				'label' => __( 'Sendy', 'text-domain' ),
				'condition' => [
					'submit_actions' => $this->get_name(),
				],
			]
		);

		$widget->add_control(
			'sendy_url',
			[
				'label' => __( 'Sendy URL', 'text-domain' ),
				'type' => \Elementor\Controls_Manager::TEXT,
				'placeholder' => 'http://your_sendy_installation/',
				'label_block' => true,
				'separator' => 'before',
				'description' => __( 'Enter the URL where you have Sendy installed', 'text-domain' ),
			]
		);

		$widget->add_control(
			'sendy_api_field',
			[
				'label' => __( 'API KEY', 'text-domain' ),
				'type' => \Elementor\Controls_Manager::TEXT,
				'description' => __( 'To find it go to Settings (top right corner) -> Your API Key.', 'text-domain' ),
			]
		);

		$widget->add_control(
			'sendy_list',
			[
				'label' => __( 'Sendy List ID', 'text-domain' ),
				'type' => \Elementor\Controls_Manager::TEXT,
				'separator' => 'before',
				'description' => __( 'the list id you want to subscribe a user to. This encrypted & hashed id can be found under View all lists section named ID.', 'text-domain' ),
			]
		);

		$widget->add_control(
			'sendy_email_field',
			[
				'label' => __( 'Email Field ID', 'text-domain' ),
				'type' => \Elementor\Controls_Manager::TEXT,
			]
		);

		$widget->add_control(
			'sendy_name_field',
			[
				'label' => __( 'Name Field ID', 'text-domain' ),
				'type' => \Elementor\Controls_Manager::TEXT,
			]
		);
		$widget->add_control(
			'sendy_custom_field_label',
			[
				'label' => __( 'Custom Fields', 'text-domain' ),
				'type' => \Elementor\Controls_Manager::RAW_HTML,
				'raw' => __( '', 'text-domain' ),
				'separator' => 'before'
			]
		);

		$repeater = new \Elementor\Repeater();

		$repeater->add_control(
			'sendy_custom_field_id',
			[
				'label' => __( 'Elementor Form Field ID', 'text-domain' ),
				'label_block' => true,
				'type' => \Elementor\Controls_Manager::TEXT,
			]
		);

		$repeater->add_control(
			'sendy_custom_field_name',
			[
				'label' => __( 'Sendy Field Name', 'text-domain' ),
				'type' => \Elementor\Controls_Manager::TEXT,
				'label_block' => true,
				'description' => 'You can find this on the Custom Fields page of your list.',
			]
		);

		$widget->add_control(
			'sendy_custom_field_list',
			array(
				'type'    => Elementor\Controls_Manager::REPEATER,
				'fields'  => array_values( $repeater->get_controls() ),
				'title_field' => '{{{ sendy_custom_field_id }}}',
			)
		);

		$widget->end_controls_section();

	}

	/**
	 * On Export
	 *
	 * Clears form settings on export
	 * @access Public
	 * @param array $element
	 */
	public function on_export( $element ) {
		unset(
			$element['sendy_url'],
			$element['sendy_list'],
			$element['sendy_name_field'],
			$element['sendy_email_field'],
			$element['sendy_api_field'],
			$element['sendy_custom_field_id'],
			$element['sendy_custom_field_name'],
			$element['sendy_custom_field_list']
		);
	}
}
add_action( 'elementor_pro/init', function() {
// Here its safe to include our action class file
include_once( 'sendy-elements.php' );

// Instantiate the action class
$sendy_action = new Sendy_Action_After_Submit();

// Register the action with form widget
\ElementorPro\Plugin::instance()->modules_manager->get_modules( 'forms' )->add_form_action( $sendy_action->get_name(), $sendy_action );
});
