<?php
function erfan_pro_setup(){
 add_theme_support('title-tag');
 add_theme_support('post-thumbnails');
 register_nav_menus(array('primary'=>'منوی اصلی'));
}
add_action('after_setup_theme','erfan_pro_setup');
function erfan_pro_assets(){wp_enqueue_style('erfan-pro',get_stylesheet_uri(),array(),'1.0.0');}
add_action('wp_enqueue_scripts','erfan_pro_assets');
