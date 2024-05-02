<?php
/*
Plugin Name: Zeroday Logger
Description: Ziyaretçi aktivitelerini kaydeden özel bir loglama eklentisi.
Version: 1.0
Author: Zeroday Software
*/

// Wordpress admin paneline bir menü ekleyelim
add_action('admin_menu', 'ozel_loglama_menu');

function ozel_loglama_menu() {
    add_menu_page('Özel Loglar', 'Özel Loglar', 'manage_options', 'ozel-loglar', 'ozel_loglar_goster');
    add_submenu_page('ozel-loglar', 'Giriş Logları', 'Giriş Logları', 'manage_options', 'giris-loglari', 'giris_loglari_goster');
    add_submenu_page('ozel-loglar', 'Mail Ayarları', 'Mail Ayarları', 'manage_options', 'mail-ayarları', 'mail_ayarlari_goster');
}

// Logları görüntüleyen fonksiyon
function ozel_loglar_goster() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'ozel_loglar'; // Tablo ismi

    // Logları alalım
    $logs = $wpdb->get_results("SELECT * FROM $table_name ORDER BY log_id DESC");

    // Logları tablo olarak gösterelim
    echo '<div class="wrap">';
    echo '<h2>Özel Loglar</h2>';
    echo '<table class="wp-list-table widefat fixed">';
    echo '<thead><tr><th>ID</th><th>Zaman</th><th>IP Adresi</th><th>Cihaz Bilgisi</th><th>İşlem Yapan</th></tr></thead>';
    echo '<tbody>';
    foreach ($logs as $log) {
        echo '<tr>';
        echo '<td>' . $log->log_id . '</td>';
        echo '<td>' . $log->zaman . '</td>';
        echo '<td>' . $log->ip_adresi . '</td>';
        echo '<td>' . $log->cihaz_bilgisi . '</td>';
        echo '<td>' . $log->islem_yapan . '</td>';
        echo '</tr>';
    }
    echo '</tbody>';
    echo '</table>';
    echo '</div>';
}


// Mail ayarlarını görüntüleyen fonksiyon
function mail_ayarlari_goster() {
    // Mail gönderme ayarları formu
    echo '<div class="wrap">';
    echo '<h2>Mail Ayarları</h2>';
    echo '<form method="post" action="">';
    echo '<label for="mail_alicisi">Mail Alıcısı:</label>';
    echo '<input type="email" id="mail_alicisi" name="mail_alicisi" value="' . get_option('mail_alicisi') . '"><br>';
    echo '<input type="submit" name="submit_mail_ayarlari" class="button-primary" value="Kaydet">';
    echo '</form>';
    echo '</div>';

    // Mail ayarlarını kaydet
    if (isset($_POST['submit_mail_ayarlari'])) {
        if (!empty($_POST['mail_alicisi'])) {
            update_option('mail_alicisi', $_POST['mail_alicisi']);
            echo '<div class="updated"><p>Mail ayarları başarıyla kaydedildi.</p></div>';
        } else {
            echo '<div class="error"><p>Lütfen mail alıcısını girin.</p></div>';
        }
    }
}

// Eklenti aktifleştirildiğinde tabloyu oluşturalım
register_activation_hook(__FILE__, 'ozel_loglama_eklenti_aktiflestir');

function ozel_loglama_eklenti_aktiflestir() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'ozel_loglar'; // Özel loglar tablo ismi
    $giris_table_name = $wpdb->prefix . 'giris_loglari'; // Giriş logları tablo ismi

    // Özel loglar tablosunu oluşturalım
    $charset_collate = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE $table_name (
        log_id mediumint(9) NOT NULL AUTO_INCREMENT,
        zaman datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        ip_adresi varchar(100) NOT NULL,
        cihaz_bilgisi text,
        islem_yapan varchar(100) NOT NULL,
        PRIMARY KEY  (log_id)
    ) $charset_collate;";
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);

    // Giriş logları tablosunu oluşturalım
    $sql_giris = "CREATE TABLE $giris_table_name (
        log_id mediumint(9) NOT NULL AUTO_INCREMENT,
        zaman datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        kullanici_adi varchar(100) NOT NULL,
        PRIMARY KEY  (log_id)
    ) $charset_collate;";
    dbDelta($sql_giris);
}

// Her sayfa yüklendiğinde loglama yapalım
add_action('wp_loaded', 'ozel_loglama_yap');

function ozel_loglama_yap() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'ozel_loglar'; // Tablo ismi
    $giris_table_name = $wpdb->prefix . 'giris_loglari'; // Giriş logları tablo ismi

    // Ziyaretçi bilgilerini alalım
    $zaman = current_time('mysql');
    $ip_adresi = $_SERVER['REMOTE_ADDR'];
    $cihaz_bilgisi = $_SERVER['HTTP_USER_AGENT'];

    // İşlemi yapan kullanıcının adını alalım (sadece giriş logları için)
    $islem_yapan = wp_get_current_user()->user_login;

    // Giriş loglarını ekleyelim
    if (!is_user_logged_in()) {
        $wpdb->insert(
            $giris_table_name,
            array(
                'zaman' => $zaman,
                'kullanici_adi' => $islem_yapan
            )
        );
    }

    // Bilgileri log tablosuna ekleyelim (her türlü log için)
    $wpdb->insert(
        $table_name,
        array(
            'zaman' => $zaman,
            'ip_adresi' => $ip_adresi,
            'cihaz_bilgisi' => $cihaz_bilgisi,
            'islem_yapan' => $islem_yapan
        )
    );

    // İlk 30 kayıt için oturum başlatma zamanı ekle (sadece giriş logları için)
    if (is_user_logged_in()) {
        $current_user = wp_get_current_user();
        $wpdb->update(
            $giris_table_name,
            array('zaman' => $zaman),
            array('kullanici_adi' => $current_user->user_login),
            array('%s'),
            array('%s')
        );
    }

    // Mail gönderme ayarları
    $mail_alicisi = get_option('mail_alicisi');

    // Giriş loglarını al
    $giris_logs = $wpdb->get_results("SELECT * FROM $giris_table_name WHERE zaman < NOW() - INTERVAL 3 DAY");

    // Mail ayarlarına uygunsa maili gönder
    if (!empty($mail_alicisi) && !empty($giris_logs)) {
        // PDF olarak logları hazırla
        $pdf = '<h1>Giriş Logları</h1>';
        $pdf .= '<table border="1"><tr><th>ID</th><th>Zaman</th><th>Kullanıcı Adı</th></tr>';
        foreach ($giris_logs as $log) {
            $pdf .= '<tr><td>' . $log->log_id . '</td><td>' . $log->zaman . '</td><td>' . $log->kullanici_adi . '</td></tr>';
        }
        $pdf .= '</table>';

        // PDF dosyasını mail olarak gönder
        $to = $mail_alicisi;
        $subject = 'Giriş Logları';
        $message = 'Merhaba, ekte giriş loglarınız bulunmaktadır.';
        $headers = 'From: Wordpress <wordpress@example.com>' . "\r\n";
        $attachments = array(
            'pdf' => $pdf
        );
        wp_mail($to, $subject, $message, $headers, $attachments);

        // Gönderilen logları sil
        $wpdb->query("DELETE FROM $giris_table_name WHERE log_id IN (SELECT log_id FROM (SELECT log_id FROM $giris_table_name ORDER BY log_id DESC LIMIT 30) AS temp) AND zaman < NOW() - INTERVAL 3 DAY");
    }
}
?>
