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
define('PLUGIN_VERSION', '1.0');

class WpMarkdownEditor{

    private static $instance;

    public function __construct()
    {
        register_activation_hook(__FILE__,array($this,'plugin_activation'));
        register_deactivation_hook(__FILE__, array($this, 'plugin_deactivation'));

        // Load markdown editor
        add_action('admin_enqueue_scripts', array($this, 'enqueue_stuffs'));
        add_action('admin_footer', array($this, 'init_editor'));

        // Remove quicktags buttons
        add_filter('quicktags_settings', array($this, 'quicktags_settings'), $editorId = 'content');

        // Load Jetpack Markdown module
        $this->load_jetpack_markdown_module();

    }
    public static function getInstance(){
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
    function plugin_deactivation()
    {
        global $wpdb;
        $wpdb->query("UPDATE `" . $wpdb->prefix . "usermeta` SET `meta_value` = 'true' WHERE `meta_key` = 'rich_editing'");
    }
    function enqueue_stuffs(){
        // only enqueue stuff on the post editor page
        if (get_current_screen()->base !== 'post') {
            return;
        }
        wp_enqueue_script('editormd-js',$this->plugin_url('/_inc/js/editormd.min.js'));
        wp_enqueue_script('jquery-js',$this->plugin_url('/_inc/js/jquery.min.js'));
        wp_enqueue_style('editormd-css', $this->plugin_url('/_inc/css/editormd.min.css'));
    }

    function plugin_url($path)
    {
        return plugins_url('wp-markdown-md/' . $path);
    }

    function init_editor(){
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
                        path    : "'.$this->plugin_url('/_inc/lib/').'"
                    });
            
                });
        </script>';
    }
    function quicktags_settings($qtInit){
        $qtInit['buttons'] = ' ';
        return $qtInit;
    }

    private function load_jetpack_markdown_module()
    {
    }

}
WpMarkdownEditor::getInstance();
