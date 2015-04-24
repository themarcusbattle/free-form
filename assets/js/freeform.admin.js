jQuery(document).ready(function($){

	$(document).on('click','.add-option', function( click_event ){
		
		click_event.preventDefault();

		var table_row = $(this).closest('tr').clone();
		var table = $(this).closest('tbody');

		$(table).append( table_row );
		
		console.log( table_row );
	});

});