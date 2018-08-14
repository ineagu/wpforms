;(function($) {

	var WPFormsBuilderLite = {

		/**
		 * Start the engine.
		 *
		 * @since 1.0.0
		 */
		init: function() {

			// Document ready
			$(document).ready(function() {
				WPFormsBuilderLite.ready();
			});

			WPFormsBuilderLite.bindUIActions();
		},

		/**
		 * Document ready.
		 *
		 * @since 1.0.0
		 */
		ready: function() {
		},

		/**
		 * Element bindings.
		 *
		 * @since 1.0.0
		 */
		bindUIActions: function() {

			// WPForms upgrade panels modal
			$(document).on('click', '#wpforms-panels-toggle button', function(e) {
				if ($(this).hasClass('upgrade-modal')){
					e.preventDefault();
					e.stopImmediatePropagation();
					WPFormsBuilderLite.upgradeModal($(this).text()+ ' panel');
				}
			});
			
			// WPForms upgrade field modal
			$(document).on('click', '.wpforms-add-fields-button', function(e) {
				if ($(this).hasClass('upgrade-modal')){
					e.preventDefault();
					e.stopImmediatePropagation();
					WPFormsBuilderLite.upgradeModal($(this).text()+ ' field');
				}
			});

			// WPForms upgrade template modal
			$(document).on('click', '.wpforms-template-select', function(e) {
				if ($(this).closest('.wpforms-template').hasClass('upgrade-modal')){
					e.preventDefault();
					e.stopImmediatePropagation();
					WPFormsBuilderLite.upgradeModal($(this).data('template-name'));
				}
			});		
		},

		/**
		 * Trigger modal for upgrade.
		 * 
		 * @since 1.0.0
		 */
		upgradeModal: function(feature) {

			var message = wpforms_builder_lite.upgrade_message.replace(/%name%/g,feature)
			$.alert({
				title: feature+' '+wpforms_builder_lite.upgrade_title,
				icon: 'fa fa-lock',
				content: message,
				confirmButton: wpforms_builder_lite.upgrade_button,
				columnClass: 'modal-wide',
				confirm: function () {
					window.open(wpforms_builder_lite.upgrade_url,'_blank');
				},
			});	
		},
	};

	WPFormsBuilderLite.init();

})(jQuery);