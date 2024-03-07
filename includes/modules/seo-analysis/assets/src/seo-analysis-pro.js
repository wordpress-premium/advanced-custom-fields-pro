const initCompetitorAnalysis = function() {
	jQuery( '#competitor_url' ).on( 'keyup', function( event ) {
		if ( 'Enter' === event.key ) {
			// If the input is empty, don't do anything.
			if ( '' === event.target.value ) {
				return
			}

			event.preventDefault()
			document.querySelector( '.rank-math-recheck' ).click()
			document.querySelector( '#competitor_url' ).blur()
		}
	} ).on( 'input', function( event ) {
		if ( '' === event.target.value ) {
			document.querySelector( '.rank-math-recheck' ).disabled = true
		} else {
			document.querySelector( '.rank-math-recheck' ).disabled = false
		}
	} ).trigger( 'input' )
}

jQuery( function() {
	if ( jQuery( '#competitor_url' ).length ) {
		initCompetitorAnalysis()
	}

	jQuery( '#rank-math-print-results' ).on( 'click', function( event ) {
		let uri = window.location.href
		uri = uri.split( '#' )[ 0 ]
		if ( -1 === uri.indexOf( '?' ) ) {
			uri += '?'
		} else {
			uri += '&'
		}

		const classes = this.classList
		classes.add( 'print-loading', 'disabled' )

		const iframe = document.createElement( 'iframe' )
		iframe.style.display = 'none'
		iframe.setAttribute( 'src', uri + 'print=1' )
		document.body.appendChild( iframe )
		iframe.addEventListener( 'load', function() {
			setTimeout( function() {
				iframe.contentWindow.print()
				classes.remove( 'print-loading', 'disabled' )
			}, 1000 )
		} )
		event.preventDefault()
	} )
} )

