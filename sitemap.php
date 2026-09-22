<?php
/*
 * Plugin Name: Footer Sitemap with Toggle
 * Description: Sitemap with hide/show button inside Astra theme footer.
 * Version: 1.0.0
 * Plugin URI: https://www.youtube.com/watch?v=xvFZjo5PgG0
 * Author: Matt Petersen
 * Author URI: https://github.com/petersem
 * GitHub Plugin URI: https://github.com/petersem/wp-plugins
 * GitHub Branch: main
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// Button inside Astra footer
add_action( 'astra_footer', 'footer_sitemap_button', 0 );

function footer_sitemap_button() {
    ?>
    <div id="footer-sitemap-button-wrapper" style="padding-left:20px; margin-top:10px;">
        <button id="footer-sitemap-toggle"
                style="padding:6px 12px; 
                       border-radius:8px 8px 0 0;
                       background:#ccc;
                       color:#000;
                       border:1px solid #999;">
            Show Sitemap
        </button>
    </div>
    <?php
}

// Sitemap printed BELOW footer (so it opens under it)
add_action( 'wp_footer', 'footer_sitemap_output', 5 );

function footer_sitemap_output() {

    // Fetch pages
    $pages = get_pages(['sort_column' => 'post_title', 'sort_order' => 'ASC']);
    $posts = get_posts(['numberposts' => -1, 'orderby' => 'title', 'order' => 'ASC']);
    $categories = get_categories(['orderby' => 'name', 'order' => 'ASC']);

    ?>
    <div id="footer-sitemap-content"
         style="display:none; padding:15px; border:1px solid #ccc; background:#f5f5f5; margin-top:20px;">

        <h4>Site Map</h4>

        <div style="display:flex; gap:40px; flex-wrap:wrap;">

            <div>
                <b>Pages</b>
                <ul>
                    <?php foreach ( $pages as $page ) : ?>
                        <li><a href="<?php echo get_page_link( $page->ID ); ?>">
                            <?php echo esc_html( $page->post_title ); ?>
                        </a></li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <div>
                <b>Posts</b>
                <ul>
                    <?php foreach ( $posts as $post ) : ?>
                        <li><a href="<?php echo get_permalink( $post->ID ); ?>">
                            <?php echo esc_html( $post->post_title ); ?>
                        </a></li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <div>
                <b>Categories</b>
                <ul>
                    <?php foreach ( $categories as $cat ) : ?>
                        <li><a href="<?php echo get_category_link( $cat->term_id ); ?>">
                            <?php echo esc_html( $cat->name ); ?>
                        </a></li>
                    <?php endforeach; ?>
                </ul>
            </div>

        </div>
    </div>

    <!-- Toggle + scroll script -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const btn = document.getElementById('footer-sitemap-toggle');
            const box = document.getElementById('footer-sitemap-content');

            btn.addEventListener('click', function() {
                if (box.style.display === 'none') {

                    // Show sitemap
                    box.style.display = 'block';
                    btn.textContent = 'Hide Sitemap';

                    // Change button to Astra blue
                    btn.style.background = '#1e73be';
                    btn.style.color = '#fff';
                    btn.style.borderColor = '#1e73be';

                    // Scroll to bottom smoothly
                    window.scrollTo({
                        top: document.body.scrollHeight,
                        behavior: 'smooth'
                    });

                } else {

                    // Hide sitemap
                    box.style.display = 'none';
                    btn.textContent = 'Show Sitemap';

                    // Change button back to grey
                    btn.style.background = '#ccc';
                    btn.style.color = '#000';
                    btn.style.borderColor = '#999';
                }
            });
        });
    </script>
    <?php
}