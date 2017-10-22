(function( $ ) {
	'use strict';

	/**
	 * All of the code for your admin-facing JavaScript source
	 * should reside in this file.
	 *
	 * Note: It has been assumed you will write jQuery code here, so the
	 * $ function reference has been prepared for usage within the scope
	 * of this function.
	 *
	 * This enables you to define handlers, for when the DOM is ready:
	 *
	 * $(function() {
	 *
	 * });
	 *
	 * When the window is loaded:
	 *
	 * $( window ).load(function() {
	 *
	 * });
	 *
	 * ...and/or other possibilities.
	 *
	 * Ideally, it is not considered best practise to attach more than a
	 * single DOM-ready or window-load handler for a particular page.
	 * Although scripts in the WordPress core, Plugins and Themes may be
	 * practising this, we should strive to set a better example in our own work.
	 */

	

})( jQuery );

/* jshint asi: true */
	var $deactivateButton

	jQuery(document).ready(function($){
		
		$deactivateButton = $('#the-list tr.active').filter( function() { return $(this).data('plugin') === 'analyticator-googe-analytics/googe-analytics-analyticator.php' } ).find('.deactivate a')
			
		$deactivateButton.click(function(e){
			e.preventDefault()
			$deactivateButton.unbind('click')
			$('body').append(fca_ga.html)
			fca_ga_uninstall_button_handlers()
			
		})
	}) 

	function fca_ga_uninstall_button_handlers() {
		var $ = jQuery
		$('#fca-deactivate-skip').click(function(){
			window.location.href = $deactivateButton.attr('href')
		})
		$('#fca-deactivate-send').click(function(){
			$(this).html('...')
			$('#fca-deactivate-skip').hide()
			$.ajax({
				url: ajaxurl,
				type: 'POST',
				data: {
					"action": "fca_ga_uninstall",
					"nonce": fca_ga.nonce,
					"msg": $('#fca-deactivate-textarea').val()
				}
			}).done( function( response ) {
				console.log ( response )
				window.location.href = $deactivateButton.attr('href')			
			})	
		})
		
	}