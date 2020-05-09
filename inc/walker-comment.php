
<?php
/**
 * Custom comment walker for the purpose of emulating Reddit's nested comments.
 * by Bobby Wibowo
 *
 * @users Walker_Comment
 */

class TISE_Walker_Comment extends Walker_Comment
{

	public function start_el( &$output, $comment, $depth = 0, $args = array(), $id = 0 )
	{
		if ( 'tise' === $args['format'] )
		{
			$depth++;
			$GLOBALS['comment_depth'] = $depth;
			$GLOBALS['comment'] = $comment;

			ob_start();
			$this->html5_comment( $comment, $depth, $args );
			$output .= ob_get_clean();
		}
		else
		{
			parent::start_el( $output, $comment, $depth, $args, $id );
		}
	}

	public function end_el( &$output, $comment, $depth = 0, $args = array() ) {
		if ( 'tise' === $args['format'] )
		{
			if ( ! empty( $args['end-callback'] ) ) {
				ob_start();
				call_user_func( $args['end-callback'], $comment, $args, $depth );
				$output .= ob_get_clean();
				return;
			}
			if ( 'div' == $args['style'] ) {
				$output .= "</div><!-- .comment-column --></div><!-- #comment-## -->\n";
			} else {
				$output .= "</div><!-- .comment-column --></li><!-- #comment-## -->\n";
			}
		}
		else
		{
			parent::end_el( $output, $comment, $depth, $args );
		}
	}

	protected function get_comment_author_link_mobile ( $comment, $avatar_size ) {
		$comment = get_comment( $comment_ID );
		$url     = get_comment_author_url( $comment );
		$author  = get_comment_author( $comment );

		// TODO: What to do when avatar_size is 0?
		$avatar  = 0 != $avatar_size ? get_avatar( $comment, $avatar_size ) : '';

		if ( empty( $url ) || 'http://' == $url ) {
			$return = "<a title='$author'>$avatar</a>";
		} else {
			$return = "<a href='$url' rel='external nofollow ugc' title='$author'>$avatar</a>";
		}

		return apply_filters( 'get_comment_author_link', $return, $author, $comment->comment_ID );
	}

	protected function html5_comment( $comment, $depth, $args ) {
		if ( 'tise' === $args['format'] )
		{
			$tag = ( 'div' === $args['style'] ) ? 'div' : 'li';

			$commenter = wp_get_current_commenter();
			if ( $commenter['comment_author_email'] ) {
				$moderation_note = __( 'Your comment is awaiting moderation.' );
			} else {
				$moderation_note = __( 'Your comment is awaiting moderation. This is a preview, your comment will be visible after it has been approved.' );
			}

			?>
			<<?php echo $tag; ?> id="comment-<?php comment_ID(); ?>" <?php comment_class( $this->has_children ? 'parent' : '', $comment ); ?>>

				<div class="threadline-column">
					<div class="expand-button is-hidden"><i class="icon-expand-button"></i></div>
					<div class="comment-author vcard">
						<?php
						echo $this->get_comment_author_link_mobile( $comment, $args['avatar_size'] );
						?>
					</div>
					<?php hmn_cp_the_comment_upvote_form(); ?>
					<div class="threadline-div"><i class="threadline"></i></div>
				</div><!-- .threadlines -->

				<div class="comment-column">
					<article id="div-comment-<?php comment_ID(); ?>" class="comment-body">
						<footer class="comment-meta">
							<div class="comment-author vcard">
								<?php
								if ( 0 != $args['avatar_size'] ) {
									echo get_avatar( $comment, $args['avatar_size'] );
								}
								?>
								<?php
									printf(
										/* translators: %s: Comment author link. */
										__( '%s <span class="says">says:</span>' ),
										sprintf( '<b class="fn">%s</b>', get_comment_author_link( $comment ) )
									);
								?>
							</div><!-- .comment-author -->

							<!--
							<div class="comment-metadata">
								<a href="<?php echo esc_url( get_comment_link( $comment, $args ) ); ?>">
									<time datetime="<?php comment_time( 'c' ); ?>">
										<?php
											/* translators: 1: Comment date, 2: Comment time. */
											printf( __( '%1$s at %2$s' ), get_comment_date( '', $comment ), get_comment_time() );
										?>
									</time>
								</a>
								<?php edit_comment_link( __( 'Edit' ), '<span class="edit-link">', '</span>' ); ?>
							</div>--><!-- .comment-metadata -->

							<?php if ( '0' == $comment->comment_approved ) : ?>
							<em class="comment-awaiting-moderation"><?php echo $moderation_note; ?></em>
							<?php endif; ?>
						</footer><!-- .comment-meta -->

						<div class="comment-content">
							<?php comment_text(); ?>
						</div><!-- .comment-content -->

						<div class="comment-controls">
							<?php
							comment_reply_link(
								array_merge(
									$args,
									array(
										'add_below'  => 'div-comment',
										'depth'      => $depth,
										'max_depth'  => $args['max_depth'],
										'before'     => '<div class="reply">',
										'reply_text' => '<i class="icon-chat-empty"></i>' . __( 'Reply' ),
										'after'      => '</div>',
									)
								)
							);
							?>
							<?php
							edit_comment_link(
								'<i class="icon-edit"></i>' . __( 'Edit' ),
								'<div class="edit-link">',
								'</div>'
							);
							?>
							<div class="date">
								<a class="date" href="<?php echo esc_url( get_comment_link( $comment, $args ) ); ?>">
									<i class="icon-clock"></i>
									<time datetime="<?php comment_time( 'c' ); ?>">
										<?php
											/* translators: 1: Comment date, 2: Comment time. */
											printf( __( '%1$s at %2$s' ), get_comment_date( '', $comment ), get_comment_time() );
										?>
									</time>
								</a>
							</div>
						</div>
					</article><!-- .comment-body -->
				<?php /* $this->start_el() will close <div class="comment-column"> */
		}
		else
		{
			parent::html5_comment( $comment, $depth, $args );
		}
	}
}
?>
