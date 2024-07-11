<?php

namespace ExamplePlugin;

use ExamplePlugin\Admin\BooksInfoListTable;

class BooksInfo {
    private static $instance = null;
    private $plugin_path;
    private $plugin_url;
    private $text_domain;

    private function __construct() {
        $this->plugin_path = plugin_dir_path(__FILE__);
        $this->plugin_url = plugin_dir_url(__FILE__);
        $this->text_domain = 'example-plugin';

        add_action('init', [$this, 'load_textdomain']);
        add_action('init', [$this, 'register_post_type']);
        add_action('init', [$this, 'register_taxonomies']);
        add_action('add_meta_boxes', [$this, 'add_meta_boxes']);
        add_action('save_post', [$this, 'save_meta_box_data']);
        add_action('admin_menu', [$this, 'add_admin_menu']);
    }

    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function load_textdomain() {
        load_plugin_textdomain($this->text_domain, false, dirname(plugin_basename(__FILE__)) . '/../languages/');
    }

    public function register_post_type() {
        $labels = [
            'name' => __('Books', $this->text_domain),
            'singular_name' => __('Book', $this->text_domain),
            // Other labels...
        ];

        $args = [
            'labels' => $labels,
            'public' => true,
            'has_archive' => true,
            'supports' => ['title', 'editor', 'thumbnail'],
            'taxonomies' => ['publisher', 'authors'],
            'show_in_rest' => true,
        ];

        register_post_type('book', $args);
    }

    public function register_taxonomies() {
        $labels = [
            'name' => __('Publishers', $this->text_domain),
            'singular_name' => __('Publisher', $this->text_domain),
        ];

        $args = [
            'hierarchical' => true,
            'labels' => $labels,
            'show_ui' => true,
            'show_admin_column' => true,
            'query_var' => true,
            'rewrite' => ['slug' => 'publisher'],
        ];

        register_taxonomy('publisher', ['book'], $args);

        $labels = [
            'name' => __('Authors', $this->text_domain),
            'singular_name' => __('Author', $this->text_domain),
        ];

        $args = [
            'hierarchical' => true,
            'labels' => $labels,
            'show_ui' => true,
            'show_admin_column' => true,
            'query_var' => true,
            'rewrite' => ['slug' => 'authors'],
        ];

        register_taxonomy('authors', ['book'], $args);
    }

    public function add_meta_boxes() {
        add_meta_box(
            'isbn_meta_box',
            __('ISBN Number', $this->text_domain),
            [$this, 'render_meta_box'],
            'book',
            'side',
            'default'
        );
    }

    public function render_meta_box($post) {
        wp_nonce_field('save_isbn_meta_box_data', 'isbn_meta_box_nonce');
        $value = get_post_meta($post->ID, '_isbn', true);
        echo '<label for="isbn_field">' . __('ISBN:', $this->text_domain) . '</label>';
        echo '<input type="text" id="isbn_field" name="isbn_field" value="' . esc_attr($value) . '" size="25" />';
    }

    public function save_meta_box_data($post_id) {
        if (!isset($_POST['isbn_meta_box_nonce']) ||
            !wp_verify_nonce($_POST['isbn_meta_box_nonce'], 'save_isbn_meta_box_data') ||
            (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) ||
            !current_user_can('edit_post', $post_id) ||
            !isset($_POST['isbn_field'])) {
            return;
        }

        $isbn = sanitize_text_field($_POST['isbn_field']);
        update_post_meta($post_id, '_isbn', $isbn);

        global $wpdb;
        $table_name = $wpdb->prefix . 'books_info';
        $existing_entry = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE post_id = %d", $post_id));

        if ($existing_entry) {
            $wpdb->update($table_name, ['isbn' => $isbn], ['post_id' => $post_id], ['%s'], ['%d']);
        } else {
            $wpdb->insert($table_name, ['post_id' => $post_id, 'isbn' => $isbn], ['%d', '%s']);
        }
    }

    public function add_admin_menu() {
        add_menu_page(
            __('Books Info', $this->text_domain),
            __('Books Info', $this->text_domain),
            'manage_options',
            'books-info',
            [$this, 'render_admin_page'],
            'dashicons-book',
            6
        );
    }

    public function render_admin_page() {
        if (!class_exists('WP_List_Table')) {
            require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
        }

        $booksInfoTable = new Admin\BooksInfoListTable();
        $booksInfoTable->prepare_items();

        echo '<div class="wrap"><h1 class="wp-heading-inline">' . __('Books Info', $this->text_domain) . '</h1>';
        echo '<form method="post">';
        $booksInfoTable->search_box(__('Search', $this->text_domain), 'search_id');
        $booksInfoTable->display();
        echo '</form></div>';
    }

    public static function activate() {
        global $wpdb;

        $table_name = $wpdb->prefix . 'books_info';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            ID bigint(20) NOT NULL AUTO_INCREMENT,
            post_id bigint(20) NOT NULL,
            isbn varchar(20) NOT NULL,
            PRIMARY KEY  (ID),
            KEY post_id (post_id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public static function deactivate() {
        // Clear events, cache or something else
    }

    public static function uninstall() {
        global $wpdb;

        $table_name = $wpdb->prefix . 'books_info';
        $wpdb->query("DROP TABLE IF EXISTS $table_name");
    }
}
