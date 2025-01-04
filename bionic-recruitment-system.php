<?php
/**
 * Plugin Name: Bionic Recruitment System
 * Description: A recruitment management plugin for Bionic.
 * Version: 1.0
 * Author: Mohibbulla Munshi
 * Text Domain: bionic-recruitment-system
 */

class BionicRecruitmentSystem {
    private $table_name;

    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'bionic_recruitment';

        register_activation_hook(__FILE__, [$this, 'activate']);
        add_action('admin_menu', [$this, 'add_admin_menu']);
    }

    public function activate() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$this->table_name} (
            id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL,
            phone VARCHAR(20) NOT NULL,
            about TEXT NOT NULL,
            nid_number VARCHAR(50) NOT NULL,
            certificate_number VARCHAR(50) NOT NULL,
            cv_path VARCHAR(255) NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function add_admin_menu() {
        add_menu_page(
            'Recruitment Management',
            'Recruitment',
            'manage_options',
            'bionic-recruitment',
            [$this, 'render_admin_page'],
            'dashicons-groups',
            26
        );
    }

    public function render_admin_page() {
        global $wpdb;

        // Handle form submission for add or edit
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bionic_admin_form_nonce']) && wp_verify_nonce($_POST['bionic_admin_form_nonce'], 'bionic_admin_form_action')) {
            $name = sanitize_text_field($_POST['name']);
            $email = sanitize_email($_POST['email']);
            $phone = sanitize_text_field($_POST['phone']);
            $about = sanitize_textarea_field($_POST['about']);
            $nid_number = sanitize_text_field($_POST['nid_number']);
            $certificate_number = sanitize_text_field($_POST['certificate_number']);
            $cv_path = '';

            // Handle file upload
            if (!empty($_FILES['cv']['name'])) {
                $upload = wp_handle_upload($_FILES['cv'], ['test_form' => false]);
                if ($upload && !isset($upload['error'])) {
                    $cv_path = $upload['url'];
                } else {
                    echo '<div class="notice notice-error"><p>File upload error: ' . $upload['error'] . '</p></div>';
                }
            }

            if (isset($_POST['entry_id']) && !empty($_POST['entry_id'])) {  // Edit functionality
                $entry_id = intval($_POST['entry_id']);
                if ($name && $email && $phone && $nid_number && $certificate_number && $cv_path) {
                    $wpdb->update($this->table_name, [
                        'name' => $name,
                        'email' => $email,
                        'phone' => $phone,
                        'about' => $about,
                        'nid_number' => $nid_number,
                        'certificate_number' => $certificate_number,
                        'cv_path' => $cv_path,
                    ], ['id' => $entry_id]);

                    echo '<div class="notice notice-success"><p>Entry updated successfully!</p></div>';
                }
            } else {  // Add functionality
                if ($name && $email && $phone && $nid_number && $certificate_number && $cv_path) {
                    $wpdb->insert($this->table_name, [
                        'name' => $name,
                        'email' => $email,
                        'phone' => $phone,
                        'about' => $about,
                        'nid_number' => $nid_number,
                        'certificate_number' => $certificate_number,
                        'cv_path' => $cv_path,
                    ]);

                    echo '<div class="notice notice-success"><p>Entry added successfully!</p></div>';
                } else {
                    echo '<div class="notice notice-error"><p>Please fill in all required fields.</p></div>';
                }
            }
        }

        // Delete entry
        if (isset($_GET['delete_id'])) {
            $delete_id = intval($_GET['delete_id']);
            $wpdb->delete($this->table_name, ['id' => $delete_id]);
            echo '<div class="notice notice-success"><p>Entry deleted successfully!</p></div>';
        }

        // Fetch existing entries
        $entries = $wpdb->get_results("SELECT * FROM {$this->table_name}");

        // Render admin page
        echo '<div class="wrap">';
        echo '<h1>Recruitment Management</h1>';

        // Render the form
        $this->render_admin_form();

        // Display the entries
        echo '<h2>Existing Entries</h2>';
        echo '<table class="widefat fixed striped">';
        echo '<thead><tr>';
        echo '<th>ID</th><th>Name</th><th>Email</th><th>Phone</th><th>NID</th><th>Certificate</th><th>CV</th><th>Created At</th><th>Actions</th>';
        echo '</tr></thead><tbody>';

        if (!empty($entries)) {
            foreach ($entries as $entry) {
                echo '<tr>';
                echo '<td>' . esc_html($entry->id) . '</td>';
                echo '<td>' . esc_html($entry->name) . '</td>';
                echo '<td>' . esc_html($entry->email) . '</td>';
                echo '<td>' . esc_html($entry->phone) . '</td>';
                echo '<td>' . esc_html($entry->nid_number) . '</td>';
                echo '<td>' . esc_html($entry->certificate_number) . '</td>';
                echo '<td><a href="' . esc_url($entry->cv_path) . '" target="_blank">Download</a></td>';
                echo '<td>' . esc_html($entry->created_at) . '</td>';
                echo '<td>';
                echo '<a href="' . admin_url('admin.php?page=bionic-recruitment&edit_id=' . $entry->id) . '">Edit</a> | ';
                echo '<a href="' . admin_url('admin.php?page=bionic-recruitment&delete_id=' . $entry->id) . '" onclick="return confirm(\'Are you sure you want to delete this entry?\')">Delete</a>';
                echo '</td>';
                echo '</tr>';
            }
        } else {
            echo '<tr><td colspan="9">No entries found.</td></tr>';
        }

        echo '</tbody></table>';
        echo '</div>';
    }

    private function render_admin_form() {
        // Check if we're editing an entry
        $entry = null;
        if (isset($_GET['edit_id'])) {
            global $wpdb;
            $edit_id = intval($_GET['edit_id']);
            $entry = $wpdb->get_row("SELECT * FROM {$this->table_name} WHERE id = $edit_id");
        }
        ?>
        <form method="POST" enctype="multipart/form-data" style="max-width: 600px; margin-bottom: 20px;">
            <?php wp_nonce_field('bionic_admin_form_action', 'bionic_admin_form_nonce'); ?>
            <input type="hidden" name="entry_id" value="<?php echo isset($entry) ? $entry->id : ''; ?>">
            <table class="form-table">
                <tr>
                    <th><label for="name">Name</label></th>
                    <td><input type="text" name="name" id="name" class="regular-text" value="<?php echo isset($entry) ? esc_attr($entry->name) : ''; ?>" required></td>
                </tr>
                <tr>
                    <th><label for="email">Email</label></th>
                    <td><input type="email" name="email" id="email" class="regular-text" value="<?php echo isset($entry) ? esc_attr($entry->email) : ''; ?>" required></td>
                </tr>
                <tr>
                    <th><label for="phone">Phone</label></th>
                    <td><input type="text" name="phone" id="phone" class="regular-text" value="<?php echo isset($entry) ? esc_attr($entry->phone) : ''; ?>" required></td>
                </tr>
                <tr>
                    <th><label for="about">About</label></th>
                    <td><textarea name="about" id="about" class="large-text" rows="5" required><?php echo isset($entry) ? esc_textarea($entry->about) : ''; ?></textarea></td>
                </tr>
                <tr>
                    <th><label for="nid_number">NID Number</label></th>
                    <td><input type="text" name="nid_number" id="nid_number" class="regular-text" value="<?php echo isset($entry) ? esc_attr($entry->nid_number) : ''; ?>" required></td>
                </tr>
                <tr>
                    <th><label for="certificate_number">Certificate Number</label></th>
                    <td><input type="text" name="certificate_number" id="certificate_number" class="regular-text" value="<?php echo isset($entry) ? esc_attr($entry->certificate_number) : ''; ?>" required></td>
                </tr>
                <tr>
                    <th><label for="cv">Upload CV</label></th>
                    <td><input type="file" name="cv" id="cv"></td>
                </tr>
            </table>
            <p class="submit">
                <input type="submit" name="submit" id="submit" class="button-primary" value="<?php echo isset($entry) ? 'Update Entry' : 'Add Entry'; ?>">
            </p>
        </form>
        <?php
    }
}

new BionicRecruitmentSystem();
