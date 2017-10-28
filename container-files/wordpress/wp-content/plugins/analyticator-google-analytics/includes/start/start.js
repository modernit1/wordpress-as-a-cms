/* jshint asi: true */
jQuery(document).ready(function($){
	
	var footerMarginTop = parseInt ( $('#analyticator-start-footer').css('margin-top') )

	$('#analyticator-start-permissions-toggle').click(function(event){
		event.preventDefault()
		$('#analyticator-start-permissions-dropdown').toggle()

		if ( $('#analyticator-start-permissions-dropdown:visible').length == 1 ) {
			$('#analyticator-start-footer').css('margin-top', footerMarginTop - 208 + 'px' )
		} else {
			$('#analyticator-start-footer').css('margin-top', footerMarginTop + 'px' )
		}
		
	})
}) 