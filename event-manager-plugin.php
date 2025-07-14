<?php
/**
 * Plugin Name: Custom Event Manager
 * Description: A simple plugin to manage and display custom events.
 * Version: 1.0
 * Author: Your Name
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Register Custom Post Type: Event
 */
function cem_register_event_post_type() {
    $labels = array(
        'name' => 'Events',
        'singular_name' => 'Event',
        'menu_name' => 'Events',
        'add_new' => 'Add New',
        'add_new_item' => 'Add New Event',
        'edit_item' => 'Edit Event',
        'new_item' => 'New Event',
        'view_item' => 'View Event',
        'search_items' => 'Search Events',
        'not_found' => 'No events found',
    );

    $args = array(
        'label' => 'Event',
        'labels' => $labels,
        'public' => true,
        'has_archive' => true,
        'show_in_rest' => true,
        'menu_position' => 5,
        'supports' => array( 'title', 'editor', 'thumbnail' ),
        'rewrite' => array( 'slug' => 'events' ),
    );

    register_post_type( 'event', $args );
}
add_action( 'init', 'cem_register_event_post_type' );

/**
 * Add Meta Boxes
 */
function cem_add_event_meta_boxes() {
    add_meta_box(
        'cem_event_details',
        'Event Details',
        'cem_event_meta_box_callback',
        'event',
        'normal',
        'default'
    );
}
add_action( 'add_meta_boxes', 'cem_add_event_meta_boxes' );

/**
 * Callback: Meta Box Content
 */
function cem_event_meta_box_callback( $post ) {
    wp_nonce_field( basename( __FILE__ ), 'cem_event_nonce' );

    $event_date = get_post_meta( $post->ID, '_cem_event_date', true );
    $event_location = get_post_meta( $post->ID, '_cem_event_location', true );
    ?>
    <p>
        <label for="cem_event_date">Event Date:</label><br />
        <input type="date" name="cem_event_date" id="cem_event_date" value="<?php echo esc_attr( $event_date ); ?>" />
    </p>
    <p>
        <label for="cem_event_location">Location:</label><br />
        <input type="text" name="cem_event_location" id="cem_event_location" value="<?php echo esc_attr( $event_location ); ?>" size="40" />
    </p>
    <?php
}

/**
 * Save Meta Box Data
 */
function cem_save_event_meta( $post_id ) {
    if ( ! isset( $_POST['cem_event_nonce'] ) || ! wp_verify_nonce( $_POST['cem_event_nonce'], basename( __FILE__ ) ) ) {
        return $post_id;
    }

    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return $post_id;
    if ( 'event' != $_POST['post_type'] || ! current_user_can( 'edit_post', $post_id ) ) return $post_id;

    if ( isset( $_POST['cem_event_date'] ) ) {
        update_post_meta( $post_id, '_cem_event_date', sanitize_text_field( $_POST['cem_event_date'] ) );
    }
    if ( isset( $_POST['cem_event_location'] ) ) {
        update_post_meta( $post_id, '_cem_event_location', sanitize_text_field( $_POST['cem_event_location'] ) );
    }
}
add_action( 'save_post', 'cem_save_event_meta' );

/**
 * Shortcode: [custom_events]
 */
function cem_display_events_shortcode( $atts ) {
    $args = array(
        'post_type'      => 'event',
        'posts_per_page' => 5,
        'orderby'        => 'meta_value',
        'meta_key'       => '_cem_event_date',
        'order'          => 'ASC',
    );

    $query = new WP_Query( $args );
    if ( ! $query->have_posts() ) {
        return '<p>No upcoming events found.</p>';
    }

    $output = '<div class="custom-events">';
    while ( $query->have_posts() ) {
        $query->the_post();
        $date     = get_post_meta( get_the_ID(), '_cem_event_date', true );
        $location = get_post_meta( get_the_ID(), '_cem_event_location', true );
        $output .= '<div class="event-item">';
        $output .= '<h3>' . get_the_title() . '</h3>';
        $output .= '<p><strong>Date:</strong> ' . esc_html( $date ) . '</p>';
        $output .= '<p><strong>Location:</strong> ' . esc_html( $location ) . '</p>';
        $output .= '<div>' . get_the_excerpt() . '</div>';
        $output .= '</div><hr>';
    }
    $output .= '</div>';
    wp_reset_postdata();

    return $output;
}
add_shortcode( 'custom_events', 'cem_display_events_shortcode' );
