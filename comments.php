<?php
/**
 * The template for displaying comments
 *
 * The area of the page that contains both current comments
 * and the comment form.
 *
 * @package WordPress
 * @subpackage Twenty_Sixteen
 * @since Twenty Sixteen 1.0
 */

/*
 * If the current post is protected by a password and
 * the visitor has not yet entered the password we will
 * return early without loading the comments.
 */
if ( post_password_required() ) {
	return;
}

$commentFormArray = array(
	'comment_field' => sprintf(
		'<p class="comment-form-comment">%s %s</p>',
		sprintf(
			'<label for="comment">%s</label>',
			_x( 'Comment', 'noun' )
		),
		'<textarea id="comment" name="comment" cols="45" rows="4" maxlength="65525" required="required"></textarea>'
	),
	'title_reply_before' => '<h2 id="reply-title" class="comment-reply-title">',
	'title_reply_after' => '</h2>',
	'label_submit' => __( 'Comment', 'twentysixteen-iteba' )
);

$useCustomWalker = true;
?>

<div id="comments" class="comments-area comments-main">

	<?php if ( have_comments() ) : ?>
		<h2 class="comments-title">
			<?php
				$comments_number = get_comments_number();
			if ( '1' === $comments_number ) {
				/* translators: %s: Post title. */
				printf( _x( 'One thought on &ldquo;%s&rdquo;', 'comments title', 'twentysixteen' ), get_the_title() );
			} else {
				printf(
					/* translators: 1: Number of comments, 2: Post title. */
					_nx(
						'%1$s thought on &ldquo;%2$s&rdquo;',
						'%1$s thoughts on &ldquo;%2$s&rdquo;',
						$comments_number,
						'comments title',
						'twentysixteen'
					),
					number_format_i18n( $comments_number ),
					get_the_title()
				);
			}
			?>
		</h2>

		<?php
		// If comments are closed and there are comments, let's leave a little note, shall we?
		// if ( ! comments_open() && get_comments_number() && post_type_supports( get_post_type(), 'comments' ) ) :
		if ( ! comments_open() ) : ?>
		<p class="no-comments"><?php _e( 'Comments are closed.', 'twentysixteen' ); ?></?>
		<?php endif; ?>

		<?php the_comments_navigation(); ?>

		<?php comment_form( $commentFormArray ); ?>

		<div class="comment-list<?php echo $useCustomWalker ? ' tise' : '' ?>">
			<?php
				if ( $useCustomWalker ) {
					$args = array(
						'style'       => 'ul',
						'short_ping'  => true,
						'avatar_size' => 42,
					);

					// Use our custom walker if it's available.
					if( class_exists( 'TISE_Walker_Comment' ) )
					{
						$args['format'] = 'tise';
						$args['walker'] = new TISE_Walker_Comment;
					}

					// Hook onto Comments Popularity plugin if exist.
					if ( function_exists( 'hmn_cp_the_sorted_comments' ) ) {
						hmn_cp_the_sorted_comments( $args );
					}
					else
					{
						wp_list_comments( $args );
					}
				} else {
					wp_list_comments();
				}
			?>
		</div><!-- .comment-list -->

		<?php the_comments_navigation(); ?>

	<?php else: ?>

		<?php comment_form( $commentFormArray ); ?>

	<?php endif; // Check for have_comments(). ?>

</div><!-- .comments-area -->
