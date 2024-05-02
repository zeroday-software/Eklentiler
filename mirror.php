<?php
/*
Plugin Name: Zeroday Mirror 
Description: Bu eklenti, Mevcut web sayfanızın canlı yedeğine yönlendirilmesini sağlar.
Version: 1.1
Author: Berat Şimşek 
*/

function redirect_settings_menu() {
    add_menu_page(
        'Zeroday Mirror', 
        'Zeroday Mirror', 
        'manage_options', 
        'redirect-settings',
        'redirect_settings_page' 
    );
}
add_action('admin_menu', 'redirect_settings_menu');


function redirect_settings_page() {
    if (isset($_POST['redirect_url'])) {
        update_option('redirect_url', esc_url_raw($_POST['redirect_url'])); 
        echo '<div class="updated"><p>Yönlendirme ayarları kaydedildi.</p></div>';
    }

    if (isset($_POST['disable_redirect'])) {
        update_option('disable_redirect', 1); 
        echo '<div class="updated"><p>Yönlendirme devre dışı bırakıldı.</p></div>';
    }

    if (isset($_POST['enable_redirect'])) {
        delete_option('disable_redirect'); 
        echo '<div class="updated"><p>Yönlendirme etkinleştirildi.</p></div>';
    }

    $redirect_url = get_option('redirect_url', ''); 
    $disable_redirect = get_option('disable_redirect');
    ?>
    <div class="wrap">
        <h2>Web Sitesi Yönlendirme Ayarları</h2>
        <form method="post">
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="redirect_url">Yönlendirme URL'si:</label></th>
                    <td><input type="url" name="redirect_url" id="redirect_url" class="regular-text" value="<?php echo esc_attr($redirect_url); ?>"></td>
                </tr>
            </table>
            <?php submit_button('Ayarları Kaydet'); ?>
        </form>
        <form method="post">
            <?php if ($disable_redirect) { ?>
                <input type="submit" name="enable_redirect" value="Yönlendirmeyi Etkinleştir" class="button">
            <?php } else { ?>
                <input type="submit" name="disable_redirect" value="Yönlendirmeyi Devre Dışı Bırak" class="button">
            <?php } ?>
        </form>
    </div>
    <?php
}

// Yönlendirme işlemini gerçekleştiren fonksiyon
function perform_redirect() {
    if (get_option('disable_redirect')) {
        return; // Yönlendirme devre dışıysa, işlemi sonlandır
    }

    $redirect_url = get_option('redirect_url', ''); // Yönlendirme URL'sini al
    if (!empty($redirect_url)) {
        wp_redirect($redirect_url); // Ziyaretçiyi yönlendir
        exit;
    }
}

// Yönlendirme işlemini gerçekleştirme eylemini başlat
add_action('template_redirect', 'perform_redirect');
