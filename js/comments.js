// https://stackoverflow.com/a/17989803/10044786

( function( doc, proto ) {
	try { // check if browser supports :scope natively
		doc.querySelector( ':scope body' );
	} catch ( err ) { // polyfill native methods if it doesn't
		[ 'querySelector', 'querySelectorAll' ].forEach( function( method ) {
			var nativ = proto[ method ];
			proto[ method ] = function(selectors) {
				if ( /(^|,)\s*:scope/.test( selectors ) ) { // only if selectors contains :scope
					var id = this.id; // remember current element id
					this.id = 'ID_' + Date.now(); // assign new unique id
					selectors = selectors.replace( /((^|,)\s*):scope/g, '$1#' + this.id ); // replace :scope with #ID
					var result = doc[ method ]( selectors );
					this.id = id; // restore previous id
					return result;
				} else {
					return nativ.call( this, selectors ); // use native code for other selectors
				}
			}
		} );
	}
} )( window.document, Element.prototype );

/**
 * Emulating Reddit's nested comments.
 */

( function() {

	const commentList = document.body.querySelector( '#comments .comment-list' )
	if ( !commentList )
		return

	const selectors = {
		expandButton: ':scope > .threadline-column > .expand-button',
		commentAuthor: ':scope > .threadline-column > .comment-author',
		commentWeight: ':scope > .threadline-column > .comment-weight-container',
		threadlineDiv: ':scope > .threadline-column > .threadline-div',
		commentColumn: ':scope > .comment-column',
		children: ':scope > .comment-column > .children',
		commentBody: ':scope > .comment-column > .comment-body',
		collapseMessage: ':scope > .comment-column > .collapse-message'
	}

	function collapseCommentsThread ( comment ) {
		// If expand button is not hidden (meaning comments are already collapsed), do nothing.
		const expandButton = comment.querySelector( selectors.expandButton )
		if ( !expandButton.classList.contains( 'is-hidden' ) )
			return

		// Close reply form if exist within thread.
		const cancelReply = comment.querySelector( '#cancel-comment-reply-link' )
		if ( cancelReply ) cancelReply.click()

		const message = document.createElement( 'div' )
		message.className = 'collapse-message'

		// Find amount of children.
		const children = comment.querySelector( selectors.children )
		if ( children ) {
			const amount = children.querySelectorAll( '.comment' ).length
			message.innerHTML = amount + ' child' + ( amount === 1 ? '' : 'ren' )
			// Hide children.
			children.classList.add( 'is-hidden' )
		} else {
			message.innerHTML = ''
		}

		// Attempt to copy author name and comment date.
		const commentBody = comment.querySelector( selectors.commentBody )
		const author = commentBody.querySelector( '.comment-author .fn' )
		const metadata = commentBody.querySelector( '.comment-metadata > a' )
		if (author || metadata) {
			message.innerHTML = ( author ? author.innerHTML : '' ) +
				( metadata ? metadata.outerHTML : '' ) +
				message.innerHTML
		}

		// Attach collapse message before comment body then hide comment body.
		commentBody.insertAdjacentElement( 'beforebegin', message )
		commentBody.classList.add( 'is-hidden' )

		// Hide other extra elements.
		const extras = [
			selectors.commentAuthor,
			selectors.commentWeight,
			selectors.threadlineDiv
		]
		for ( let i = 0; i < extras.length; i++ ) {
			const element = comment.querySelector( extras[ i ] )
			if ( element ) element.classList.add( 'is-hidden' )
		}

		// Show expand button.
		expandButton.classList.remove( 'is-hidden' )
		comment.classList.add( 'is-collapsed' )
	}

	function expandCommentsThread ( comment ) {
		// If expand button is hidden (meaning comments are already expanded), do nothing
		const expandButton = comment.querySelector( selectors.expandButton )
		if ( expandButton.classList.contains( 'is-hidden' ) )
			return

		// Remove collapse message
		const collapseMessage = comment.querySelector( selectors.collapseMessage )
		if ( collapseMessage )
			collapseMessage.parentNode.removeChild( collapseMessage )

		// Unhide these elements.
		const unhide = [
			selectors.children,
			selectors.commentBody,
			selectors.commentAuthor,
			selectors.commentWeight,
			selectors.threadlineDiv
		]
		for ( let i = 0; i < unhide.length; i++ ) {
			const element = comment.querySelector( unhide[ i ] )
			if ( element ) element.classList.remove( 'is-hidden' )
		}

		// Hide expand button.
		expandButton.classList.add( 'is-hidden' )
		comment.classList.remove( 'is-collapsed' )
	}

	// Use single click event listener on comments list.
	commentList.addEventListener( 'click', function ( event ) {
		if ( !event.target ) return

		let expandButton
		let threadline

		// Logic for belongings.
		if ( event.target.classList.contains( 'threadline-div' ) )
			threadline = event.target
		else if ( event.target.classList.contains( 'threadline' ) )
			threadline = event.target.parentNode
		else if ( event.target.classList.contains( 'expand-button' ) )
			expandButton = event.target
		else if ( event.target.classList.contains( 'icon-expand-button' ) )
			expandButton = event.target.parentNode

		const target = threadline || expandButton
		if ( target ) {
			const comment = target.parentNode && target.parentNode.parentNode
			// Proceed only if the expected parent/grandparent node is a comment.
			if ( comment && comment.classList.contains( 'comment' ) ) {
				if ( threadline )
					collapseCommentsThread( comment )
				else if ( expandButton )
					expandCommentsThread( comment )
			}
		}
	}, true )

	// On first page load, collapse all level 4 comments.
	// TODO: Copy Reddit's design for pre-collapsed threads.
	/*
	const targetLevel = 6
	const targets = commentList.querySelectorAll( '.comment.depth-' + targetLevel )
	if ( targets.length ) {
		for ( let i = 0; i < targets.length; i++ )
			collapseCommentsThread( targets[ i ] )
	}
	*/

	function relativeDate ( datetime ) {
		// Source: https://stackoverflow.com/a/7641812/10044786
		const date = new Date( datetime )
		const delta = Math.round( ( +new Date - date ) / 1000 )

		const minute = 60
		const hour = minute * 60
		const day = hour * 24

		let result
		if ( delta < 30 )
			result = 'just now'
		else if ( delta < minute )
			result = delta + ' seconds ago'
		else if ( delta < 2 * minute )
			result = 'a minute ago'
		else if ( delta < hour )
			result = Math.floor( delta / minute ) + ' minutes ago'
		else if ( Math.floor( delta / hour ) === 1 )
			result = '1 hour ago'
		else if ( delta < day )
			result = Math.floor( delta / hour ) + ' hours ago'
		else if ( delta < day * 2 )
			result = 'yesterday'
		else
			result = Math.floor( delta / day ) + ' days ago'

		return result
	}

	// Activate Moment.js
	const dates = commentList.getElementsByTagName( 'time' )
	if ( dates.length ) {
		for ( let i = 0; i < dates.length; i++ ) {
			const datetime = dates[ i ].getAttribute( 'datetime' )
			if ( datetime ) {
				const container = dates[ i ].parentNode
				if ( container ) {
					container.setAttribute( 'title', dates[ i ].innerHTML.trim() )
					container.setAttribute( 'aria-label', dates[ i ].innerHTML.trim() )
				} else {
					dates[ i ].setAttribute( 'title', dates[ i ].innerHTML.trim() )
					dates[ i ].setAttribute( 'aria-label', dates[ i ].innerHTML.trim() )
				}
				dates[ i ].innerHTML = relativeDate( datetime ) || dates[ i ].innerHTML
			}
		}
	}
} )();
