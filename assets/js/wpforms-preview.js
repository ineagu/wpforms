;(function($){
	$(function(){

		// Print page
		$(document).on('click', '.print', function(e) {
			e.preventDefault();
			window.print();
		});

		// Close page
		$(document).on('click', '.close-window', function(e) {
			e.preventDefault();
			window.close();
		});

	});
}(jQuery));