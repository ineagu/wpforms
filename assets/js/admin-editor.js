;(function($){
	$(function(){
		// Close modal
		var wpformsModalClose = function() {
			if ( $('#wpforms-modal-select-form').length ) {
				$('#wpforms-modal-select-form').get(0).selectedIndex = 0;
				$('#wpforms-modal-checkbox-title, #wpforms-modal-checkbox-description').prop('checked', false);
			}
			$('#wpforms-modal-backdrop, #wpforms-modal-wrap').css('display','none');
			$( document.body ).removeClass( 'modal-open' );
		};
		// Open modal when media button is clicked
		$('.wpforms-insert-form-button').click(function(event) {
			event.preventDefault();
			$('#wpforms-modal-backdrop, #wpforms-modal-wrap').css('display','block');
			$( document.body ).addClass( 'modal-open' );
		});
		// Close modal on close or cancel links
		$('#wpforms-modal-close, #wpforms-modal-cancel a').click(function(event) {
			wpformsModalClose();
		});
		// Insert shortcode into TinyMCE
		$('#wpforms-modal-submit').click(function(event) {
			event.preventDefault();
			var shortcode;
			shortcode = '[wpforms id="' + $('#wpforms-modal-select-form').val() + '"';
			if ( $('#wpforms-modal-checkbox-title').is(':checked') ) {
				shortcode = shortcode+' title="true"';
			}
			if ( $('#wpforms-modal-checkbox-description').is(':checked') ) {
				shortcode = shortcode+' description="true"';
			}
			shortcode = shortcode+']';
			wp.media.editor.insert(shortcode);
			wpformsModalClose();
		});
	});
}(jQuery));