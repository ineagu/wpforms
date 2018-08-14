;(function($){
	$(function(){

	// Confirm entry deletion
	$(document).on('click', '#wpforms-overview .wp-list-table .delete a', function(e) {
		if ( confirm( wpforms_overview.delete_confirm ) ) {
			return true;
		}
		return false;
	});

	// Confirm form duplication
	$(document).on('click', '#wpforms-overview .wp-list-table .duplicate a', function(e) {
		if ( confirm( wpforms_overview.duplicate_confirm ) ) {
			return true;
		}
		return false;
	});

	});
}(jQuery));