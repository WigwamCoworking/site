<?php
/**
 * Functions to register client-side assets (scripts and stylesheets) for the
 * Gutenberg block.
 *
 * @package coworking-group
 */

/**
 * Registers all block assets so that they can be enqueued through Gutenberg in
 * the corresponding context.
 *
 * @see https://wordpress.org/gutenberg/handbook/blocks/writing-your-first-block-type/#enqueuing-block-scripts
 */
function group_photos_init_block()
{
    // Skip block registration if Gutenberg is not enabled/merged.
    if (! function_exists('register_block_type')) {
        return;
    }
    $dir = dirname(__FILE__);

    $index_js = 'group/index.js';
    wp_register_script(
        'group-photos-block-editor',
        plugins_url($index_js, __FILE__),
        array(
            'wp-blocks',
            'wp-i18n',
            'wp-element',
            'wp-components',
        ),
        filemtime("$dir/$index_js")
    );

    $editor_css = 'group/editor.css';
    wp_register_style(
        'group-photos-block-editor',
        plugins_url($editor_css, __FILE__),
        array(),
        filemtime("$dir/$editor_css")
    );

    $style_css = 'group/style.css';
    wp_register_style(
        'group-photos-block',
        plugins_url($style_css, __FILE__),
        array(),
        filemtime("$dir/$style_css")
    );

    register_block_type('coworking-group/group', array(
        'editor_script'   => 'group-photos-block-editor',
        'editor_style'    => 'group-photos-block-editor',
        'style'           => 'group-photos-block',
        'render_callback' => 'group_photos_render_block',
    ));
}

/**
 * Enqueue block editor JavaScript and CSS
 */
function group_photos_enqueue_block_script()
{
    $front_js = 'group/front.js';

    // Enqueue frontend JS.
    if (! is_admin()) {
        wp_enqueue_script(
            'jsforwp-blocks-frontend-js',
            plugins_url($front_js, __FILE__),
            [ 'wp-blocks', 'wp-element', 'wp-components', 'wp-i18n' ],
            filemtime(plugin_dir_path(__FILE__))
        );
    }
}
add_action('enqueue_block_assets', 'group_photos_enqueue_block_script');

/**
 * Render the block on the frontend.
 *
 * @since  1.0
 */
function group_photos_render_block()
{
    global $this_year;

    $output = '';

    // Get current product adhesion.
    $adhesions = wc_get_products([
        'return'   => 'objects',
        'orderby'  => 'menu_order',
        'order'    => 'ASC',
        'exclude'  => array( 315 ), // Exclude grouped product itself.
        'category' => array( 'adhesion-' . $this_year ), // Get months from current year category, ex: "plan-2019".
        'limit'    => 12,
    ]);

    // show the users list who purchased current adhesion
    if (!empty($adhesions[0])) {
        $adhesion = $adhesions[0];
        $output .= '<h3>Les membres du WigWam pour l\'année courante :</h3><br>';

        $users = get_users([
            'orderby'  => 'nicename',
            'role__in' => array( 'administrator', 'customer' ),
        ]);

        $output .= '<div class="row">';

        foreach ($users as $user) {

            // checks if user purchased adhesion.
            if (wc_customer_bought_product($user->user_email, $user->ID, $adhesion->get_id())) {

            	$output .= '<div class="col-lg-3 col-md-4 col-sm-6 text-center mg-md">';
                $output .= get_avatar($user->ID, 200);
            	$output .= '<p class="mg-md"><b>' . ucfirst(strtolower(esc_html($user->first_name))) . ' ' . ucfirst(strtolower(esc_html($user->last_name))) . '</b><p>';
        		$output .= '</div>';
            }
        }
        $output .= '</div>';

    } else {
        $output .= '<p>Le produit correspondant à l\'adhésion de l\'année actuelle n\'a pas été trouvée</p>';
    }

    return $output;
}
add_action('init', 'group_photos_init_block');
