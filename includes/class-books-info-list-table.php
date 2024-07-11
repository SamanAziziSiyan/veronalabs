<?php

namespace ExamplePlugin\Admin;

if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class BooksInfoListTable extends \WP_List_Table
{
    public function __construct()
    {
        parent::__construct([
            'singular' => 'book_info',
            'plural'   => 'books_info',
            'ajax'     => false,
        ]);
    }

    public function get_columns()
    {
        return [
            'cb'      => '<input type="checkbox" />',
            'ID'      => __('ID', 'example-plugin'),
            'post_id' => __('Post ID', 'example-plugin'),
            'isbn'    => __('ISBN', 'example-plugin'),
        ];
    }

    public function prepare_items()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'books_info';
        $query = "SELECT * FROM $table_name";

        $total_items = $wpdb->query($query); // get the total number of items
        $per_page = 10; // number of items per page

        $current_page = $this->get_pagenum();
        $offset = ($current_page - 1) * $per_page;

        $query .= " LIMIT $offset, $per_page";

        $this->items = $wpdb->get_results($query, ARRAY_A);

        $this->set_pagination_args([
            'total_items' => $total_items,
            'per_page'    => $per_page,
            'total_pages' => ceil($total_items / $per_page),
        ]);
    }

    public function column_default($item, $column_name)
    {
        return $item[$column_name];
    }

    public function column_cb($item)
    {
        return sprintf('<input type="checkbox" name="book[]" value="%s" />', $item['ID']);
    }
}
