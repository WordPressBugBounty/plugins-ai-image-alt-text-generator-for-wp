<?php
// Include the necessary WordPress files
if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

class Books_List_Table extends WP_List_Table {
    public function __construct() {
        parent::__construct(array(
            'singular' => 'Data',
            'plural'   => 'Data',
            'ajax'     => false
        ));
    }

    public function get_columns() {
        return array(
            'title'  => 'Title',
            'author' => 'Author',
            'genre'  => 'Genre',
            'year'   => 'Year'
        );
    }

    public function get_sortable_columns() {
        return array(
            'title'  => array('title', false),
            'author' => array('author', false),
            'year'   => array('year', false)
        );
    }

    public function prepare_items() {
        // Include the WordPress filesystem API
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        WP_Filesystem();
        global $wp_filesystem;
    
        // Get the path to the JSON file
        $file_path = plugin_dir_path(dirname(__FILE__)) . '/includes/content.json';
    
        // Get the contents of the JSON file
        $json = $wp_filesystem->get_contents($file_path);
    
        // Decode the JSON data into an array
        $data = json_decode($json, true);
    
        // Sort the data
        usort($data, array($this, 'usort_reorder'));
    
        $this->_column_headers = array($this->get_columns(), array(), $this->get_sortable_columns());
        $this->items = $data;
    }

    public function column_default($item, $column_name) {
        return $item[$column_name];
    }

    private function usort_reorder($a, $b) {
        $orderby = (!empty($_GET['orderby'])) ? $_GET['orderby'] : 'title'; // If no sort, default to title
        $order = (!empty($_GET['order'])) ? $_GET['order'] : 'asc'; // If no order, default to asc
        $result = strcmp($a[$orderby], $b[$orderby]); // Determine sort order
        return ($order === 'asc') ? $result : -$result; // Send final sort direction to usort
    }
}