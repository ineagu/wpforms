<?php
/**
 * Contact form template.
 *
 * @package    WPForms
 * @author     WPForms
 * @since      1.0.0
 * @license    GPL-2.0+
 * @copyright  Copyright (c) 2016, WPForms LLC
*/
class WPForms_Template_Contact extends WPForms_Template {

	/**
	 * Primary class constructor.
	 *
	 * @since 1.0.0
	 */
	public function init() {

		$this->name        = __( 'Simple Contact Form', 'wpforms' );
		$this->slug        = 'contact';
		$this->description = __( 'Allow your users to contact you with this simple contact form. You can add and remove fields as needed.', 'wpforms' );
		$this->includes    = '';
		$this->icon        = '';
		$this->modal       = '';
		$this->data        = array(
			'field_id' => '3',
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
					'required'    => '1',
					'size'        => 'medium',
				),
				'2'  => array(
					'id'          => '2',
					'type'        => 'textarea',
					'label'       => __( 'Comment or Message', 'wpforms' ),
					'description' => '',
					'required'    => '1',
					'size'        => 'medium',
					'placeholder' => '',
					'css'         => '',
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
new WPForms_Template_Contact;