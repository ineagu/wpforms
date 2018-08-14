<?php
/**
 * Blank form template.
 *
 * @package    WPForms
 * @author     WPForms
 * @since      1.0.0
 * @license    GPL-2.0+
 * @copyright  Copyright (c) 2016, WPForms LLC
*/
class WPForms_Template_Blank extends WPForms_Template {

	/**
	 * Primary class constructor.
	 *
	 * @since 1.0.0
	 */
	public function init() {

		$this->name        = __( 'Blank Form', 'wpforms' );
		$this->slug        = 'blank';
		$this->description = __( 'The blank form allows you to create any type of form using our drag & drop builder.', 'wpforms' );
		$this->includes    = '';
		$this->icon        = '';
		$this->modal       = '';
		$this->data        = array(
			'field_id' => '1',
			'fields'   => array(),
			'settings' => array(
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
new WPForms_Template_Blank;