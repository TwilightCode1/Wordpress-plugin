<?php
/*
Plugin Name: USM Notes
Description: Plugin pentru gestionarea notițelor în WordPress.
Version: 1.0
Author: Sorin
*/


/**
 * Înregistrare Custom Post Type: Notițe
 */
function usm_register_notes_cpt() {

    $labels = array(
        'name'               => 'Notițe',
        'singular_name'      => 'Notiță',
        'menu_name'          => 'Notițe',
        'name_admin_bar'     => 'Notiță',
        'add_new'            => 'Adaugă Notiță',
        'add_new_item'       => 'Adaugă Notiță Nouă',
        'new_item'           => 'Notiță Nouă',
        'edit_item'          => 'Editează Notiță',
        'view_item'          => 'Vezi Notiță',
        'all_items'          => 'Toate Notițele',
        'search_items'       => 'Caută Notițe',
        'not_found'          => 'Nu au fost găsite notițe',
    );

    $args = array(
        'labels'             => $labels,
        'public'             => true,
        'has_archive'        => true,
        'menu_icon'          => 'dashicons-welcome-write-blog',
        'supports'           => array('title', 'editor', 'author', 'thumbnail'),
        'show_in_rest'       => true, 
    );

    register_post_type('note', $args);
}

add_action('init', 'usm_register_notes_cpt');


/**
 * Înregistrare Taxonomie: Prioritate
 */
function usm_register_priority_taxonomy() {

    $labels = array(
        'name'              => 'Priorități',
        'singular_name'     => 'Prioritate',
        'search_items'      => 'Caută Priorități',
        'all_items'         => 'Toate Prioritățile',
        'edit_item'         => 'Editează Prioritate',
        'update_item'       => 'Actualizează Prioritate',
        'add_new_item'      => 'Adaugă Prioritate Nouă',
        'new_item_name'     => 'Nume Prioritate Nouă',
        'menu_name'         => 'Prioritate',
    );

    $args = array(
        'labels'            => $labels,
        'hierarchical'      => true, // ca și categoriile
        'public'            => true,
        'show_in_rest'      => true,
    );

    register_taxonomy('priority', array('note'), $args);
}

add_action('init', 'usm_register_priority_taxonomy');

/**
 * Adaugă metabox pentru data de reamintire
 */
function usm_add_reminder_metabox() {
    add_meta_box(
        'usm_reminder_date',
        'Data de reamintire',
        'usm_render_reminder_metabox',
        'note',
        'side',
        'default'
    );
}
add_action('add_meta_boxes', 'usm_add_reminder_metabox');

function usm_render_reminder_metabox($post) {
    // nonce pentru securitate
    wp_nonce_field('usm_save_reminder_date', 'usm_reminder_nonce');

    $value = get_post_meta($post->ID, '_usm_reminder_date', true);

    echo '<label for="usm_reminder_date">Selectează data:</label>';
    echo '<input type="date" id="usm_reminder_date" name="usm_reminder_date" value="' . esc_attr($value) . '" required />';
}

function usm_save_reminder_date($post_id) {
    // verificare nonce
    if (!isset($_POST['usm_reminder_nonce']) || 
        !wp_verify_nonce($_POST['usm_reminder_nonce'], 'usm_save_reminder_date')) {
        return;
    }
    // evită autosave
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    // verificare tip post
    if (get_post_type($post_id) !== 'note') {
        return;
    }
    // verificare permisiuni
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }
    // verificare existență câmp
    if (!isset($_POST['usm_reminder_date']) || empty($_POST['usm_reminder_date'])) {
        add_filter('redirect_post_location', function($location) {
            return add_query_arg('usm_error', 'empty_date', $location);
        });
        return;
    }

    $date = sanitize_text_field($_POST['usm_reminder_date']);
    $today = date('Y-m-d');
    // validare: nu în trecut
    if ($date < $today) {
        add_filter('redirect_post_location', function($location) {
            return add_query_arg('usm_error', 'past_date', $location);
        });
        return;
    }
    // salvare
    update_post_meta($post_id, '_usm_reminder_date', $date);
}
add_action('save_post', 'usm_save_reminder_date');

function usm_admin_notices() {
    if (!isset($_GET['usm_error'])) return;

    if ($_GET['usm_error'] === 'empty_date') {
        echo '<div class="notice notice-error"><p>Data de reamintire este obligatorie!</p></div>';
    }

    if ($_GET['usm_error'] === 'past_date') {
        echo '<div class="notice notice-error"><p>Data nu poate fi în trecut!</p></div>';
    }
}
add_action('admin_notices', 'usm_admin_notices');

// adaugă coloană
function usm_add_reminder_column($columns) {
    $columns['reminder_date'] = 'Data reamintire';
    return $columns;
}
add_filter('manage_note_posts_columns', 'usm_add_reminder_column');

// afișează valoarea
function usm_show_reminder_column($column, $post_id) {
    if ($column === 'reminder_date') {
        $date = get_post_meta($post_id, '_usm_reminder_date', true);
        echo $date ? esc_html($date) : '—';
    }
}
add_action('manage_note_posts_custom_column', 'usm_show_reminder_column', 10, 2);

/**
 * Shortcode: [usm_notes priority="X" before_date="YYYY-MM-DD"]
 */
function usm_notes_shortcode($atts) {

    $atts = shortcode_atts(array(
        'priority'     => '',
        'before_date'  => '',
    ), $atts, 'usm_notes');

    // Query args
    $args = array(
        'post_type'      => 'note',
        'posts_per_page' => -1,
    );

    // Filtru după taxonomie (priority)
    if (!empty($atts['priority'])) {
        $args['tax_query'] = array(
            array(
                'taxonomy' => 'priority',
                'field'    => 'slug',
                'terms'    => sanitize_title($atts['priority']),
            ),
        );
    }

    // Filtru după dată (meta query)
    if (!empty($atts['before_date'])) {
        $args['meta_query'] = array(
            array(
                'key'     => '_usm_reminder_date',
                'value'   => sanitize_text_field($atts['before_date']),
                'compare' => '<=',
                'type'    => 'DATE',
            ),
        );
    }

    $query = new WP_Query($args);

    ob_start();

    echo '<div class="usm-notes-list">';

    if ($query->have_posts()) {

        while ($query->have_posts()) {
            $query->the_post();

            $date = get_post_meta(get_the_ID(), '_usm_reminder_date', true);

            echo '<div class="usm-note-item">';
            echo '<h3>' . get_the_title() . '</h3>';
            echo '<div class="usm-content">' . get_the_excerpt() . '</div>';

            if ($date) {
                echo '<div class="usm-date">📅 ' . esc_html($date) . '</div>';
            }

            echo '</div>';
        }

    } else {
        echo '<p>Nu există notițe cu parametrii specificați</p>';
    }

    echo '</div>';

    wp_reset_postdata();

    return ob_get_clean();
}

add_shortcode('usm_notes', 'usm_notes_shortcode');

function usm_notes_styles() {
    echo '
    <style>
        .usm-notes-list {
            display: grid;
            gap: 15px;
            margin: 20px 0;
        }

        .usm-note-item {
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            background: #f9f9f9;
        }

        .usm-note-item h3 {
            margin: 0 0 10px;
        }

        .usm-content {
            margin-bottom: 10px;
        }

        .usm-date {
            font-size: 14px;
            color: #555;
        }
    </style>
    ';
}
add_action('wp_head', 'usm_notes_styles');