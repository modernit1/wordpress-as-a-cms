/* jshint asi: true */
	var $deactivateButton

	jQuery(document).ready(function($){
		
		$deactivateButton = $('#the-list tr.active').filter( function() { return $(this).data('plugin') === 'analyticator-google-analytics/google-analytics-analyticator.php' } ).find('.deactivate a')
			
		$deactivateButton.click(function(e){
			e.preventDefault()
			$deactivateButton.unbind('click')
			$('body').append(analyticator_ga_survey.html)
			analyticator_ga_uninstall_button_handlers()
			
		})
	}) 

	function analyticator_ga_uninstall_button_handlers() {
		var $ = jQuery
		$('#analyticator-survey-skip').click(function(){
			window.location.href = $deactivateButton.attr('href')
		})
		$('#analyticator-survey-send').click(function(){
			$(this).html('Deactivating ...')
			$('#analyticator-survey-skip').hide()
			$.ajax({
				url: analyticator_ga_survey.ajaxurl,
				type: 'POST',
				data: {
					"action": "analyticator_ga_deactivate_survey",
					"nonce": analyticator_ga_survey.nonce,
					"msg": $('#analyticator-survey-textarea').val()
				}
			}).done( function( response ) {
				console.log ( response )
				window.location.href = $deactivateButton.attr('href')			
			})	
		})
		
	}