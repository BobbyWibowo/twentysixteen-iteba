
<?php
/**
 * Custom comment walker for the purpose of emulating Reddit's nested comments.
 * by Bobby Wibowo
 *
 * @users Walker_Comment
 */

/**
 * Custom comment format: 'tise'
 *
 * Inspired by https://wordpress.stackexchange.com/a/216686.
 */

class TISE_Walker_Comment extends Walker_Comment
{

	public function start_lvl( &$output, $depth = 0, $args = array() ) {
		if ( $args['format'] === 'tise' )
		{
			$GLOBALS['comment_depth'] = $depth + 1;

			switch ( $args['style'] ) {
				case 'div':
					break;
				case 'ol':
					$output .= '<ol class="children">' . "\n";
					break;
				case 'ul':
				default:
					$output .= '<ul class="children">' . "\n";
					break;
			}
		}
		else
		{
			parent::start_lvl( $output, $depth, $args );
		}
	}

	public function end_lvl( &$output, $depth = 0, $args = array() ) {
		$GLOBALS['comment_depth'] = $depth + 1;

		switch ( $args['style'] ) {
			case 'div':
				break;
			case 'ol':
				$output .= "</ol><!-- .children -->\n";
				break;
			case 'ul':
			default:
				$output .= "</ul><!-- .children -->\n";
				break;
		}
	}

	public function start_el( &$output, $comment, $depth = 0, $args = array(), $id = 0 )
	{
		if ( $args['format'] === 'tise' )
		{
			$depth++;
			$GLOBALS['comment_depth'] = $depth;
			$GLOBALS['comment'] = $comment;

			// Start output buffering
			ob_start();

			// Let's use the native html5 comment template
			$this->html5_comment( $comment, $depth, $args );

			// Our modifications (wrap <time> with <span>)
			$output .= str_replace(
				[ '<time ', '</time>' ],
				['<span><time ', '</time></span>' ],
				ob_get_clean()
			);
		}
		else
		{
			// Fallback for the native comment formats
			parent::start_el( $output, $comment, $depth, $args, $id );
		}
	}
} // end class
?>
