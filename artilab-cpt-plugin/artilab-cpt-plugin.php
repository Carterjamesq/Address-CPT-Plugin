<?php
/**
 * Plugin Name: Artilab CPT Plugin
 */

if (!defined('ABSPATH')) {
    exit;
}

function register_address_cpt() {
    register_post_type('address', [
        'label'         => 'Addresses',
        'public'        => true,
        'show_in_menu'  => true,
        'supports'      => ['title', 'editor'],
        'rewrite'       => ['slug' => 'address/%category%', 'with_front' => false],
        'has_archive'   => 'address',
    ]);
}
add_action('init', 'register_address_cpt');

function register_address_category() {
    register_taxonomy('address_category', 'address', [
        'label'         => 'Categories',
        'hierarchical'  => true,
        'public'        => true,
        'rewrite'       => ['slug' => 'address'],
    ]);
}
add_action('init', 'register_address_category');

function custom_address_permalinks($post_link, $post) {
    if ($post->post_type === 'address') {
        $terms = get_the_terms($post->ID, 'address_category');
        if (!empty($terms) && !is_wp_error($terms)) {
            return str_replace('%category%', $terms[0]->slug, $post_link);
        }
    }
    return $post_link;
}
add_filter('post_type_link', 'custom_address_permalinks', 10, 2);

function enable_gutenberg_on_archive() {
    add_post_type_support('page', 'editor');
}
add_action('init', 'enable_gutenberg_on_archive');

function add_disable_permalink_metabox() {
    add_meta_box(
        'disable_permalink',
        'Disable Permalink',
        'disable_permalink_callback',
        'address',
        'side',
        'default'
    );
}
add_action('add_meta_boxes', 'add_disable_permalink_metabox');

function disable_permalink_callback($post) {
    $value = get_post_meta($post->ID, '_disable_permalink', true);
    echo '<label><input type="checkbox" name="disable_permalink" value="1" ' . checked(1, $value, false) . '> Disable Permalink for address</label>';
}

function save_disable_permalink_meta($post_id) {
    if (isset($_POST['disable_permalink'])) {
        update_post_meta($post_id, '_disable_permalink', 1);
    } else {
        delete_post_meta($post_id, '_disable_permalink');
    }
}
add_action('save_post', 'save_disable_permalink_meta');

function check_disabled_permalink() {
    if (is_singular('address')) {
        global $post;
        if (get_post_meta($post->ID, '_disable_permalink', true)) {
            global $wp_query;
            $wp_query->set_404();
            status_header(404);
            nocache_headers();
            include get_404_template();
            exit;
        }
    }
}
add_action('template_redirect', 'check_disabled_permalink');

function address_list_shortcode($atts) {
    ob_start();
    
    $query = new WP_Query([
        'post_type'      => 'address',
        'posts_per_page' => -1,
        'orderby'        => 'date',
        'order'          => 'DESC',
    ]);

    if ($query->have_posts()) {
        echo '<div class="address-list">';
        while ($query->have_posts()) {
            $query->the_post();
            echo '<div class="address-item">';
            echo '<h2><a href="' . get_permalink() . '">' . get_the_title() . '</a></h2>';
            echo '<div class="address-content">' . get_the_excerpt() . '</div>';
            echo '</div>';
        }
        echo '</div>';
    } else {
        echo '<p style="color: red;">No addresses found. Check if CPT posts exist.</p>';
    }
    
    wp_reset_postdata();
    return ob_get_clean();
}
add_shortcode('address_list', 'address_list_shortcode');
