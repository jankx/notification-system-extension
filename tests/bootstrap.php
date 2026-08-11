<?php

use Brain\Monkey;

if (!defined('JANKX_NOTIFICATION_TEST_DIR')) {
    define('JANKX_NOTIFICATION_TEST_DIR', __DIR__);
}

// 1. Composer autoloader
$composerAutoload = __DIR__ . '/../libs/autoload.php';
if (file_exists($composerAutoload)) {
    require_once $composerAutoload;
}

// 2. PSR-4 fallback autoloader
spl_autoload_register(function ($class) {
    $prefixes = [
        'Jankx\\Extensions\\NotificationSystem\\' => __DIR__ . '/../src/',
    ];

    foreach ($prefixes as $prefix => $baseDir) {
        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) !== 0) {
            continue;
        }

        $relativeClass = substr($class, $len);
        $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

// 3. Framework classes
$frameworkDir = __DIR__ . '/../../../../jankx/includes/framework';
if (file_exists($frameworkDir . '/Contracts/Extension/ExtensionInterface.php')) {
    require_once $frameworkDir . '/Contracts/Extension/ExtensionInterface.php';
}
if (file_exists($frameworkDir . '/Extensions/AbstractExtension.php')) {
    require_once $frameworkDir . '/Extensions/AbstractExtension.php';
}

// 3b. WordPress stubs
if (!defined('OBJECT')) {
    define('OBJECT', 'OBJECT');
}
if (!defined('ARRAY_A')) {
    define('ARRAY_A', 'ARRAY_A');
}
if (!class_exists('wpdb')) {
    class wpdb
    {
        public $prefix = 'wp_';
        public $insert_id = 0;
        public $num_rows = 0;
        public $last_error = '';
        public $rows_affected = 0;

        public function prepare($query, ...$args) { return $query; }
        public function get_row($query, $output = OBJECT, $offset = 0) { return null; }
        public function get_results($query, $output = ARRAY_A) { return []; }
        public function get_var($query = null, $x = 0, $y = 0) { return null; }
        public function insert($table, $data, $format = null) { return true; }
        public function update($table, $data, $where = null, $format = null, $where_format = null) { return 1; }
        public function delete($table, $where = null, $format = null) { return 1; }
        public function esc_like($text) { return addcslashes($text, '%_'); }
        public function get_charset_collate() { return 'utf8mb4_unicode_ci'; }
    }
}

// 4. WordPress function stubs
function stub_wp_notification_functions()
{
    Monkey\Functions\when('__')->returnArg();
    Monkey\Functions\when('add_action')->justReturn(true);
    Monkey\Functions\when('add_filter')->justReturn(true);
    Monkey\Functions\when('apply_filters')->alias(function ($tag, $value) {
        return $value;
    });
    Monkey\Functions\when('do_action')->justReturn(null);
    Monkey\Functions\when('get_option')->alias(function ($key, $default = false) {
        return $GLOBALS['__wp_options'][$key] ?? $default;
    });
    Monkey\Functions\when('update_option')->alias(function ($key, $value) {
        $GLOBALS['__wp_options'][$key] = $value;
        return true;
    });
    Monkey\Functions\when('current_time')->justReturn('2026-08-11 12:00:00');
    Monkey\Functions\when('get_current_user_id')->justReturn(1);
    Monkey\Functions\when('is_user_logged_in')->justReturn(true);
    Monkey\Functions\when('wp_get_current_user')->alias(function () {
        return (object) ['ID' => get_current_user_id(), 'user_email' => 'test@example.com', 'display_name' => 'Test User'];
    });
    Monkey\Functions\when('get_userdata')->alias(function ($userId) {
        return (object) ['ID' => $userId, 'display_name' => "User {$userId}", 'user_email' => "user{$userId}@example.com"];
    });
    Monkey\Functions\when('sanitize_key')->alias(function ($key) {
        return strtolower(preg_replace('/[^a-z0-9_]/', '', $key));
    });
    Monkey\Functions\when('absint')->alias(function ($val) {
        return abs((int) $val);
    });
    Monkey\Functions\when('wp_json_encode')->alias(function ($data, $options = 0) {
        return json_encode($data, $options);
    });
    Monkey\Functions\when('esc_html')->returnArg();
    Monkey\Functions\when('esc_html__')->returnArg();
    Monkey\Functions\when('esc_attr')->returnArg();
    Monkey\Functions\when('esc_url')->returnArg();
    Monkey\Functions\when('home_url')->alias(function ($path = '') {
        return 'http://example.com' . $path;
    });
    Monkey\Functions\when('get_bloginfo')->alias(function ($key = 'name') {
        return $key === 'name' ? 'Test Site' : 'http://example.com';
    });
    Monkey\Functions\when('wp_mail')->justReturn(true);
    Monkey\Functions\when('wp_create_nonce')->justReturn('nonce-token');

    $GLOBALS['__wp_options'] = [];
}
