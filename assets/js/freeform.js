jQuery(document).ready(function($){
	
	$('.freeform-form button[type="submit"]').on( 'click', function( form_submit ) {

		form_submit.preventDefault();

		var form = $(this).closest('.freeform-form');
		var form_is_valid = validate_form();
		var submit_button = $(this);

		if ( form_is_valid ) {

			$( submit_button ).attr( 'disabled', true );

			request = $.ajax({
				url: freeform.ajax_url,
				type: "POST",
				data: $( form ).serialize(),
				dataType: "JSON"
			});

			request.done( function( response ) {
				
				$( submit_button ).removeAttr( 'disabled' );
				
				alert( response.message );
				
			});

		}

	});

	function validate_form() {

		var form_is_valid = true;

		$('.freeform-field.required').removeClass('error');

		$('.freeform-field.required').each(function(){
			
			if ( $(this).find('input,select,textarea').val() == '' ) {
				
				form_is_valid = false;
				$(this).addClass('error');
				
			}

		});

		return form_is_valid;

	}

	$('input.phone').mask("(999) 999-9999");

});