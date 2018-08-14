<?php
/**
 * Suggestion form template.
 *
 * @package    WPForms
 * @author     WPForms
 * @since      1.1.3.2
 * @license    GPL-2.0+
 * @copyright  Copyright (c) 2016, WPForms LLC
*/
class WPForms_Template_Suggestion extends WPForms_Template {

	/**
	 * Primary class constructor.
	 *
	 * @since 1.0.0
	 */
	public function init() {

		$this->name        = __( 'Suggestion Form', 'wpforms' );
		$this->slug        = 'suggestion';
		$this->description = __( 'Ask your users for suggestions with this simple form template. You can add and remove fields as needed.', 'wpforms' );
		$this->includes    = '';
		$this->icon        = '';
		$this->modal       = '';
		$this->data        = array(
			'field_id' => '5',
			'fields'   => array(
				'0'  => array(
					'id'          => '0',
					'type'        => 'name',
					'label'       => __( 'Name', 'wpforms' ),
					'required'    => '1',
					'size'        => 'medium',
				),
				'1'  => array(
					'id'          => '1',
					'type'        => 'email',
					'label'       => __( 'E-mail', 'wpforms' ),
					'description' => __( 'Please enter your email, so we can follow up with you.', 'wpforms' ),
					'required'    => '1',
					'size'        => 'medium',
				),
				'2'  => array(
					'id'          => '2',
					'type'        => 'radio',
					'label'       => __( 'Which department do you have a suggestion for?', 'wpforms' ),
					'choices'     => array(
						'1' => array(
							'label' => __( 'Sales', 'wpforms' ),
						),
						'2' => array(
							'label' => __( 'Customer Support', 'wpforms' ),
						),
						'3' => array(
							'label' => __( 'Product Development', 'wpforms' ),
						),
						'4' => array(
							'label' => __( 'Other', 'wpforms' ),
						),
					),
					'required'    => '1',
				),
				'3'  => array(
					'id'          => '3',
					'type'        => 'text',
					'label'       => __( 'Subject', 'wpforms' ),
					'required'    => '1',
					'size'        => 'medium',
				),
				'4'  => array(
					'id'          => '4',
					'type'        => 'textarea',
					'label'       => __( 'Message', 'wpforms' ),
					'required'    => '1',
					'size'        => 'medium',
				),
			),
			'settings' => array(
				'notifications' => array(
					'1' => array(
						'sender_name'    => '{field_id="0"}',
						'sender_address' => '{field_id="1"}',
					),	
				),
				'honeypot'                    => '1',
				'confirmation_message_scroll' => '1',
				'submit_text_processing'      => __( 'Sending...', 'wpforms' ),
			),
			'meta'     => array(
				'template' => $this->slug,
			),
		);
	}
}
new WPForms_Template_Suggestion;