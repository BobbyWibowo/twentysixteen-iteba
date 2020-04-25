<?php /* Template Name: TableOfContent */ ?>
<?php get_header(); ?>
 
<div id="primary" class="content-area">
    <main id="main" class="site-main" role="main">
        <?php
        // Start the loop.
        while ( have_posts() ) : the_post();
 
            // Include the page content template.
            get_template_part( 'template-parts/content', 'page' );
			
            //get category number
            $catnum = get_post_meta($post->ID, 'category_id', TRUE);

            //retrieve posts
            $args = array(
                'numberposts' => -1,
                'category' => $catnum,
                'order' => 'ASC',
                'post_type' => 'post'
            );
			$catPost = get_posts($args); 
            ?>

            <!--//output the titles of each category-->
             <h3>Chapter:</h3>
             <div><p>
             <?php
			   foreach ($catPost as $post) : setup_postdata($post); ?>
					<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a> </br>
				   
			<?php  endforeach;?>
            </p></div>
            <?php
			
            // If comments are open or we have at least one comment, load up the comment template.
            if ( comments_open() || get_comments_number() ) {
                comments_template();
            }
 
            // End of the loop.
        endwhile;
        ?>
 
    </main><!-- .site-main -->
 
    <?php get_sidebar( 'content-bottom' ); ?>
 
</div><!-- .content-area -->
 
<?php get_sidebar(); ?>
<?php get_footer(); ?>