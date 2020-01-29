<?php

/*
 *
 * テンプレート設定
 *
 */

class Template{

    static $twig;

    static function register(){

        if( !self::$twig ) require_once THEME_DIR.'/vendor/autoload.php';

        $loader = new Twig_Loader_Filesystem( THEME_DIR.'/template' );

        self::$twig = new Twig_Environment($loader);

        //定数アクセス無効化
        $function = new Twig_SimpleFunction( 'constant', function(){ return false; });
        self::$twig->addFunction( $function );

        //テンプレート内関数
        $function = new Twig_SimpleFunction( 'wp_head', 'wp_head' );
        self::$twig->addFunction( $function );

        $function = new Twig_SimpleFunction( 'wp_footer', 'wp_footer' );
        self::$twig->addFunction( $function );

        $function = new Twig_SimpleFunction( 'settings_fields', 'settings_fields' );
        self::$twig->addFunction( $function );

        $function = new Twig_SimpleFunction( 'do_settings_sections', 'do_settings_sections' );
        self::$twig->addFunction( $function );

        $function = new Twig_SimpleFunction( 'submit_button', 'submit_button' );
        self::$twig->addFunction( $function );

        $function = new Twig_SimpleFunction( 'themeurl', 'get_template_directory_uri' );
        self::$twig->addFunction( $function );

        $function = new Twig_SimpleFunction( 'homeurl', 'home_url' );
        self::$twig->addFunction( $function );

        $function = new Twig_SimpleFunction( 'is_home', 'is_home' );
        self::$twig->addFunction( $function );

        $function = new Twig_SimpleFunction( 'is_archive', 'is_archive' );
        self::$twig->addFunction( $function );

        $function = new Twig_SimpleFunction( 'is_search', 'is_search' );
        self::$twig->addFunction( $function );

        $function = new Twig_SimpleFunction( 'body_class', 'body_class' );
        self::$twig->addFunction( $function );

        $function = new Twig_SimpleFunction( 'get_bloginfo', 'get_bloginfo' );
        self::$twig->addFunction( $function );

        $function = new Twig_SimpleFunction( 'var_dump', 'var_dump' );
        self::$twig->addFunction( $function );

        return self::$twig;

    }

}

$twig = Template::register();

?>
