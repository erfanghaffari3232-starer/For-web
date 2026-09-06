<?php
/**
 * Plugin Name: Erfan Student System
 * Description: سیستم ورود، ثبت‌نام و پنل دانش‌آموز برای دوره آموزشی عرفان.
 * Version: 1.0.0
 * Author: Erfan
 * License: GPL-2.0-or-later
 * Text Domain: erfan-student-system
 */
if (!defined('ABSPATH')) exit;

final class Erfan_Student_System {
    public static function init() {
        add_shortcode('erfan_login', [__CLASS__, 'login']);
        add_shortcode('erfan_register', [__CLASS__, 'register']);
        add_shortcode('erfan_panel', [__CLASS__, 'panel']);
        add_action('wp_enqueue_scripts', [__CLASS__, 'assets']);
        add_action('template_redirect', [__CLASS__, 'protect_panel']);
        add_action('init', [__CLASS__, 'handle_forms']);
    }

    public static function assets() {
        wp_enqueue_style('erfan-student-system', plugins_url('assets/style.css', __FILE__), [], '1.0.0');
    }

    private static function page_url($slug) {
        $page = get_page_by_path($slug);
        return $page ? get_permalink($page) : home_url('/' . trim($slug, '/') . '/');
    }

    public static function activate() {
        foreach ([
            'erfan-login' => ['ورود دانش‌آموز', '[erfan_login]'],
            'erfan-register' => ['ثبت‌نام دانش‌آموز', '[erfan_register]'],
            'erfan-panel' => ['پنل دانش‌آموز', '[erfan_panel]'],
        ] as $slug => $data) {
            if (!get_page_by_path($slug)) {
                wp_insert_post(['post_title'=>$data[0], 'post_name'=>$slug, 'post_content'=>$data[1], 'post_status'=>'publish', 'post_type'=>'page']);
            }
        }
        flush_rewrite_rules();
    }

    public static function protect_panel() {
        if (is_page('erfan-panel') && !is_user_logged_in()) {
            wp_safe_redirect(self::page_url('erfan-login'));
            exit;
        }
    }

    public static function handle_forms() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['erfan_action'])) return;
        if (empty($_POST['erfan_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['erfan_nonce'])), 'erfan_forms')) return;

        $action = sanitize_key(wp_unslash($_POST['erfan_action']));
        if ($action === 'login') {
            $creds = [
                'user_login' => sanitize_text_field(wp_unslash($_POST['username'] ?? '')),
                'user_password' => (string) ($_POST['password'] ?? ''),
                'remember' => !empty($_POST['remember']),
            ];
            $user = wp_signon($creds, is_ssl());
            if (!is_wp_error($user)) {
                wp_safe_redirect(self::page_url('erfan-panel'));
                exit;
            }
            set_transient('erfan_login_error_' . wp_get_session_token(), $user->get_error_message(), 60);
        }

        if ($action === 'register') {
            $username = sanitize_user(wp_unslash($_POST['username'] ?? ''));
            $email = sanitize_email(wp_unslash($_POST['email'] ?? ''));
            $password = (string) ($_POST['password'] ?? '');
            $confirm = (string) ($_POST['password_confirm'] ?? '');
            if (!$username || !$email || !$password || $password !== $confirm || username_exists($username) || email_exists($email)) return;
            $id = wp_create_user($username, $password, $email);
            if (!is_wp_error($id)) {
                wp_update_user(['ID'=>$id, 'role'=>'subscriber']);
                wp_set_auth_cookie($id, true);
                wp_safe_redirect(self::page_url('erfan-panel'));
                exit;
            }
        }
    }

    public static function login() {
        if (is_user_logged_in()) return '<div class="erfan-box">شما وارد شده‌اید. <a href="'.esc_url(self::page_url('erfan-panel')).'">ورود به پنل</a></div>';
        ob_start(); ?>
        <div class="erfan-auth"><div class="erfan-auth-card"><div class="erfan-logo">🎓</div><h1>دوره آموزشی عرفان</h1><p class="muted">ورود به حساب دانش‌آموز</p>
        <form method="post"><input type="hidden" name="erfan_action" value="login"><input type="hidden" name="erfan_nonce" value="<?php echo esc_attr(wp_create_nonce('erfan_forms')); ?>">
        <label>ایمیل / نام کاربری</label><input name="username" autocomplete="username" required>
        <label>رمز عبور</label><input type="password" name="password" autocomplete="current-password" required>
        <label class="check"><input type="checkbox" name="remember" value="1"> مرا به خاطر بسپار</label>
        <button type="submit">ورود به پنل من</button></form>
        <p class="switch">حساب ندارید؟ <a href="<?php echo esc_url(self::page_url('erfan-register')); ?>">ثبت‌نام کنید</a></p></div></div><?php return ob_get_clean();
    }

    public static function register() {
        if (is_user_logged_in()) return '<div class="erfan-box">شما وارد شده‌اید. <a href="'.esc_url(self::page_url('erfan-panel')).'">پنل من</a></div>';
        ob_start(); ?><div class="erfan-auth"><div class="erfan-auth-card"><div class="erfan-logo">✨</div><h1>ثبت‌نام دانش‌آموز</h1><p class="muted">حساب آموزشی خودت را بساز</p>
        <form method="post"><input type="hidden" name="erfan_action" value="register"><input type="hidden" name="erfan_nonce" value="<?php echo esc_attr(wp_create_nonce('erfan_forms')); ?>">
        <label>نام کاربری</label><input name="username" required><label>ایمیل</label><input type="email" name="email" required><label>رمز عبور</label><input type="password" name="password" required><label>تکرار رمز عبور</label><input type="password" name="password_confirm" required><button type="submit">ساخت حساب</button></form>
        <p class="switch">حساب دارید؟ <a href="<?php echo esc_url(self::page_url('erfan-login')); ?>">ورود</a></p></div></div><?php return ob_get_clean();
    }

    public static function panel() {
        if (!is_user_logged_in()) return '';
        $u = wp_get_current_user();
        ob_start(); ?><div class="erfan-panel"><div class="erfan-panel-hero"><span>🎓</span><div><p class="muted">خوش آمدی</p><h1><?php echo esc_html($u->display_name ?: $u->user_login); ?> 👋</h1><p>از اینجا دوره‌ها و کلاس‌های خودت را مدیریت کن.</p></div></div>
        <div class="erfan-grid"><div class="erfan-card"><b>📚 دوره‌های من</b><p>فعلاً دوره‌ای اختصاص داده نشده است.</p></div><div class="erfan-card"><b>🎥 کلاس آنلاین</b><p>پس از اختصاص کلاس، لینک BigBlueButton اینجا نمایش داده می‌شود.</p><a class="disabled" href="#">ورود به کلاس</a></div><div class="erfan-card"><b>🏆 مسابقات KidCode</b><p>چالش‌ها و مسابقات آینده اینجا قرار می‌گیرند.</p></div></div>
        <p><a class="logout" href="<?php echo esc_url(wp_logout_url(home_url('/'))); ?>">خروج از حساب</a></p></div><?php return ob_get_clean();
    }
}
Erfan_Student_System::init();
register_activation_hook(__FILE__, ['Erfan_Student_System','activate']);
