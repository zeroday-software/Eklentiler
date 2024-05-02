<?php
/*
Plugin Name: Zeroday Bcrypt Encode
Description: Bu eklenti  Kullanıcıların kullanıcı adı ve şifresini Bcrypt Algoritmasına göre şifreler
Version: 1.0
Author: Zeroday Software
*/

// Kullanıcı kaydedildiğinde veya güncellendiğinde şifreyi Bcrypt ile şifrele
function bcrypt_menu() {
    add_menu_page(
        'Bcrypt Passwords Settings',
        'Bcrypt Passwords',
        'manage_options',
        'bcrypt-passwords-settings',
        'bcrypt_settings_page'
    );
}
add_action('admin_menu', 'bcrypt_menu');

// Ayarlar sayfasını oluştur
function bcrypt_settings_page() {
    ?>
    <div class="wrap">
        <h1>Bcrypt Passwords Settings</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('bcrypt_passwords_settings');
            do_settings_sections('bcrypt_passwords_settings');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

// Ayarları kaydedilebilir hale getir
function bcrypt_register_settings() {
    register_setting('bcrypt_passwords_settings', 'bcrypt_enable');
    add_settings_section(
        'bcrypt_passwords_section',
        'Bcrypt Passwords Settings',
        'bcrypt_section_callback',
        'bcrypt_passwords_settings'
    );
    add_settings_field(
        'bcrypt_enable',
        'Enable Bcrypt Passwords',
        'bcrypt_enable_callback',
        'bcrypt_passwords_settings',
        'bcrypt_passwords_section'
    );
}
add_action('admin_init', 'bcrypt_register_settings');

// Ayarlar bölümünün açıklamasını göster
function bcrypt_section_callback() {
    echo '<p>Enable or disable Bcrypt Passwords</p>';
}

// Aktifleştirme/durdurma seçeneğini göster
function bcrypt_enable_callback() {
    $bcrypt_enable = get_option('bcrypt_enable');
    echo '<input type="checkbox" name="bcrypt_enable" value="1" ' . checked(1, $bcrypt_enable, false) . ' />';
}

// Kullanıcı kaydedildiğinde veya güncellendiğinde şifreyi Bcrypt ile şifrele
function bcrypt_save_password($user_id) {
    if (!empty($_POST['password'])) {
        $bcrypt_enable = get_option('bcrypt_enable');
        if ($bcrypt_enable) {
            $hashed_password = password_hash($_POST['password'], PASSWORD_BCRYPT);
            wp_set_password($hashed_password, $user_id);
        }
    }
}
add_action('user_register', 'bcrypt_save_password');
add_action('profile_update', 'bcrypt_save_password');

// Kullanıcı giriş yaptığında şifreyi kontrol et
function bcrypt_check_password($user, $password) {
    $stored_hash = $user->user_pass;
    if (password_verify($password, $stored_hash)) {
        return $user;
    }
    return new WP_Error('bcrypt_password_error', __('Incorrect password'));
}
add_filter('authenticate', 'bcrypt_check_password', 20, 3);
