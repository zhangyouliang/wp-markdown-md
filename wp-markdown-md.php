<?php
/**
 * Plugin Name: WP Markdown md
 * Plugin URI: https://github.com/z1577121881/wp-markdown-md
 * Description: WP Markdown md replaces the default editor with a WYSIWYG Markdown Editor for your posts and pages.
 * Version: 1.0
 * Author: Zhang youliang
 * Website: http://www.whatdy.com
 * License:
 */
// Make sure we don't expose any info if called directly
if (!function_exists('add_action')) {
    echo 'Hi there!  I\'m just a plugin, not much I can do when called directly.';
    exit;
}

if (!function_exists('jetpack_require_lib')) {
    include_once dirname(__FILE__) . '/jetpack/require-lib.php';
}

if (!class_exists('WPCom_Markdown')) {
    include_once dirname(__FILE__) . '/jetpack/markdown/easy-markdown.php';
}

define('PLUGIN_VERSION', '1.0');

class WpMarkdownEditor
{

    private static $instance;

    public function __construct()
    {
        register_activation_hook(__FILE__, array($this, 'plugin_activation'));
        register_deactivation_hook(__FILE__, array($this, 'plugin_deactivation'));

        // Load markdown editor
        add_action('admin_enqueue_scripts', array($this, 'enqueue_stuffs'));
        add_action('admin_footer', array($this, 'init_editor'));

        // Remove quicktags buttons
        add_filter('quicktags_settings', array($this, 'quicktags_settings'), $editorId = 'content');

        //edit default insert images
        add_filter('image_send_to_editor', 'new_image_send_to_editor', 21, 8);

        // Load Jetpack Markdown module
        $this->load_jetpack_markdown_module();

    }

    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            $c = __CLASS__;
            self::$instance = new $c;
        }
        return self::$instance;
    }

    public function __clone()
    {
        trigger_error('Clone is not allowed.', E_USER_ERROR);
    }

    function plugin_activation()
    {
        global $wpdb;
        $wpdb->query("UPDATE `" . $wpdb->prefix . "usermeta` SET `meta_value` = 'false' WHERE `meta_key` = 'rich_editing'");
    }

    /*markdown */
    function new_image_send_to_editor($html, $id, $caption, $title, $align, $url, $size, $alt)
    {
        $pattern = '<img.*?src="(.*?)">';
        preg_match($pattern, $html, $matches);
        return "![$alt]($matches[1])";
    }

    function plugin_deactivation()
    {
        global $wpdb;
        $wpdb->query("UPDATE `" . $wpdb->prefix . "usermeta` SET `meta_value` = 'true' WHERE `meta_key` = 'rich_editing'");
    }

    function enqueue_stuffs()
    {
        // only enqueue stuff on the post editor page
        if (get_current_screen()->base !== 'post') {
            return;
        }
        wp_enqueue_script('editormd-js', $this->plugin_url('/_inc/js/editormd.min.js'));
        wp_enqueue_script('jquery-js', $this->plugin_url('/_inc/js/jquery.min.js'));
        wp_enqueue_style('editormd-css', $this->plugin_url('/_inc/css/editormd.min.css'));
    }

    function plugin_url($path)
    {
        return plugins_url('wp-markdown-md/' . $path);
    }

    function init_editor()
    {
        if (get_current_screen()->base !== 'post') {
            return;
        }
        echo '<script type="text/javascript">
                var testEditor;
                $(function() {
                    testEditor = editormd("wp-content-editor-container", {
                        width   : "100%",
                        height  : 888,
                        syncScrolling : "single",
                        path    : "' . $this->plugin_url('/_inc/lib/') . '"
                    });
            
                });
        </script>';
    }

    function quicktags_settings($qtInit)
    {
        $qtInit['buttons'] = ' ';
        return $qtInit;
    }

    private function load_jetpack_markdown_module()
    {
        // If the module is active, let's make this active for posting, period.
        // Comments will still be optional.
        add_filter('pre_option_' . WPCom_Markdown::POST_OPTION, '__return_true');
        add_action('admin_init', array($this, 'jetpack_markdown_posting_always_on'), 11);
        add_action('plugins_loaded', array($this, 'jetpack_markdown_load_textdomain'));
        add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'jetpack_markdown_settings_link'));
    }

    function jetpack_markdown_posting_always_on()
    {
        global $wp_settings_fields;
        if (isset($wp_settings_fields['writing']['default'][WPCom_Markdown::POST_OPTION])) {
            unset($wp_settings_fields['writing']['default'][WPCom_Markdown::POST_OPTION]);
        }
    }

    function jetpack_markdown_load_textdomain()
    {
        load_plugin_textdomain('jetpack', false, dirname(plugin_basename(__FILE__)) . '/jetpack/languages/');
    }

    function jetpack_markdown_settings_link($actions)
    {
        return array_merge(
            array('settings' => sprintf('<a href="%s">%s</a>', 'options-discussion.php#' . WPCom_Markdown::COMMENT_OPTION, __('Settings', 'jetpack'))),
            $actions
        );
        return $actions;
    }

}

WpMarkdownEditor::getInstance();
