<?php
if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class BionicRecruitment_List_Table extends WP_List_Table {

    public function get_columns() {
        return [
            'id' => 'ID',
            'name' => 'Name',
            'email' => 'Email',
            'phone' => 'Phone',
            'nid_number' => 'NID Number',
            'certificate_number' => 'Certificate Number',
            'cv_path' => 'CV',
            'date_created' => 'Date Created',
        ];
    }

    public function prepare_items() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'bionic_recruitment';

        $per_page = 10;
        $current_page = $this->get_pagenum();
        $offset = ($current_page - 1) * $per_page;

        $this->items = $wpdb->get_results("SELECT * FROM $table_name LIMIT $offset, $per_page", ARRAY_A);
        $total_items = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");

        $this->set_pagination_args([
            'total_items' => $total_items,
            'per_page' => $per_page,
        ]);
    }

    public function column_default($item, $column_name) {
        return $item[$column_name];
    }
}
