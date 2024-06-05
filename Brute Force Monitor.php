<?php
/*
Plugin Name: Brute Force Monitor
Description: Yanlış kullanıcı adı ve şifre denemelerini kaydeder ve admin panelinde gösterir.
Version: 1.1
Author: Berat Şimşek
web Site : https://zerodaysoftware.com.tr
*/

if (!defined('ABSPATH')) {
    exit; // Doğrudan erişimi engelle
}

function log_failed_login($username) {
    $ip = $_SERVER['REMOTE_ADDR'];
    global $wpdb;
    $table_name = $wpdb->prefix . 'failed_logins';
    $time = current_time('mysql');
    
    $wpdb->insert(
        $table_name,
        array(
            'time' => $time,
            'username' => $username,
            'ip' => $ip,
            'password' => $_POST['pwd']
        )
    );
}

add_action('wp_login_failed', 'log_failed_login');

function create_failed_logins_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'failed_logins';
    $charset_collate = $wpdb->get_charset_collate();
    
    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        username tinytext NOT NULL,
        ip tinytext NOT NULL,
        password text NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

register_activation_hook(__FILE__, 'create_failed_logins_table');

function bf_logger_menu() {
    add_menu_page(
        'Failed Logins',
        'Failed Logins',
        'manage_options',
        'bf-logger',
        'bf_logger_page'
    );
}

add_action('admin_menu', 'bf_logger_menu');

function bf_logger_page() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'failed_logins';
    $results = $wpdb->get_results("SELECT * FROM $table_name");
    ?>
    <div class="wrap">
        <h1>Failed Login Attempts</h1>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Time</th>
                    <th>Username</th>
                    <th>IP Address</th>
                    <th>Password</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($results as $row) : ?>
                <tr>
                    <td><?php echo esc_html($row->id); ?></td>
                    <td><?php echo esc_html($row->time); ?></td>
                    <td><?php echo esc_html($row->username); ?></td>
                    <td><?php echo esc_html($row->ip); ?></td>
                    <td><?php echo esc_html($row->password); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
}
?>
