<?php

//ini_set('display_errors', "On");

require_once "functions/const.php";//定数
require_once "functions/thumbnail.php";//サムネイル
require_once "functions/security.php";//セキュリティ
require_once "functions/menu.php";//メニュー（グローバル・サイド）クラス
require_once "functions/template.php";//テンプレート（twig）クラス
require_once "functions/controller.php";//コントローラー


//テンプレート振り分け
add_action( 'template_redirect', 'my_template_redirect' );
function my_template_redirect() {

	//メインクエリ
	global $wp_query;
	//テンプレート本体
	global $twig;
    //パンくずリスト初期化
	$controller = new Controller();
	//テンプレート関数：記事データ取得
	$function = new Twig_SimpleFunction( 'getPost', array( $controller, 'getPost' ) );
	$twig->addFunction($function);
	//テンプレート関数：ページネーション
	$function = new Twig_SimpleFunction( 'pagination', array( $controller, 'pagination' ) );
	$twig->addFunction($function);
	//各ページのクエリオブジェクト取得
	$queried = $controller->queried;
	//テンプレートに渡すデータ配列
	$args = array();
	$args['query'] = $wp_query; //メインクエリ（主にページネーション用）
	$args['posts'] = $wp_query->posts; //メインクエリで取得した記事
	//基本情報
	$args['info'] = array(
		'tel'       => get_option('info_tel'),//電話番号
		'time'      => get_option('info_time'),//受付時間
		'company'   => get_option('info_company'),//運営
		'copyright' => get_option('info_copyright'),//コピーライト
		'logo'      => get_option('info_logo')//ロゴ
	);
	//グローバルメニュー
	if( wp_get_nav_menu_items('グローバルメニュー') ){
		ob_start();
		wp_nav_menu( array(
			'theme_location' => 'global',
			'container'      => '',
			'items_wrap'     => '%3$s',
			'walker'         => new SimpleMenuWalker
		));
		$args['globalmenu'] = ob_get_clean();
	}

	//トップページの時
	if ( is_home() ) {

		$template = 'index'; //テンプレート名

	//下層ページの時
	}else{

		//サイドバーメニュー
		ob_start();
		wp_nav_menu( array(
			'theme_location' => 'sidebar',
			'container'      => '',
			'items_wrap'     => '%3$s',
			'walker'         => new SimpleMenuWalker
		));
		$args['sidebar'] = ob_get_clean();

		//カテゴリー一覧ページ
		if ( is_category() ) {

			$template = 'archive';
			$args['breadcrumb'] = $controller->getTaxPageBreadCrumb();

		//タグ一覧ページ
		} else if ( is_tag() ){

			$template = 'archive';
			$args['breadcrumb'] = $controller->getTagPageBreadCrumb();

		//カスタムカテゴリー・タグ一覧ページ
		} else if( is_tax() ){

			$template = 'archive';
			$args['breadcrumb'] = $controller->getTaxPageBreadCrumb();

		//カスタム投稿一覧ページ
		} else if ( is_post_type_archive() ){

			$template = 'archive';
			$args['breadcrumb'] = $controller->getPostTypePageBreadCrumb();

		//シングルページ
		} else if ( is_single() ) {

			$template = 'single';
			$taxonomy = 'category';
			$args['breadcrumb'] = $controller->getSinglePageBreadCrumb( $taxonomy );
			$args['post'] = $controller->getPost( $queried->ID );
			$args['terms'] = $controller->getPostTerms( $queried->ID );
			$args['related_posts'] = $controller->getRelatedPosts( $queried->ID, array('category') );

		//固定ページ
		} else if ( is_page() ) {

			$template = 'page';
			$args['breadcrumb'] = $controller->getPageBreadCrumb();
			$args['post'] = $controller->getPost( $queried->ID );
			$args['related_posts'] = $controller->getRelatedPages( $queried->ID );

		//検索結果一覧ページ
		} else if ( is_search() ) {

			$template = 'archive';
			$args['breadcrumb'] = $controller->getSearchPageBreadCrumb();

		//４０４ページ
		} else if ( is_404() ) {

			$template = '404';
			$args['breadcrumb'] = $controller->getNotFoundPageBreadCrumb( "このページは存在しません" );

		//それ以外（著者や日付アーカイブなど）は使用しない
		} else {

			exit( "不正なアクセスです！" );

		}

	}

	$args['title']       = $controller->header_title;
	$args['description'] = $controller->header_description;

	echo $twig->render( $template.'.html', $args );

	exit();

}

//ファイル読み込み
add_action('wp_enqueue_scripts','my_wp_enqueue_scripts');
function my_wp_enqueue_scripts(){
    //CSS
	wp_enqueue_style('normalize.css', THEME_URL.'/css/normalize.css',array(),filemtime(THEME_DIR.'/css/normalize.css'));
    wp_enqueue_style('style.css', THEME_URL.'/style.css',array('normalize.css'),filemtime(THEME_DIR.'/style.css'));
    wp_enqueue_style('archive.css', THEME_URL.'/css/archive.css',array('style.css'),filemtime(THEME_DIR.'/css/archive.css'));
    wp_enqueue_style('single.css', THEME_URL.'/css/single.css',array('style.css'),filemtime(THEME_DIR.'/css/single.css'));
	wp_enqueue_style('relatedposts.css', THEME_URL.'/css/relatedposts.css',array('style.css'),filemtime(THEME_DIR.'/css/relatedposts.css'));
	wp_enqueue_style('breadcrumb.css', THEME_URL.'/css/breadcrumb.css',array('style.css'),filemtime(THEME_DIR.'/css/breadcrumb.css'));
	wp_enqueue_style('pagination.css', THEME_URL.'/css/pagination.css',array('style.css'),filemtime(THEME_DIR.'/css/pagination.css'));
    //JAVASCRIPT
    global $wp_scripts;
    $jquery = $wp_scripts->registered['jquery-core'];
    $jq_ver = $jquery->ver;
    $jq_src = $jquery->src;
    wp_deregister_script( 'jquery' );
    wp_deregister_script( 'jquery-core' );
    wp_register_script( 'jquery', false, array('jquery-core'), $jq_ver, true );
    wp_register_script( 'jquery-core', $jq_src, array(), $jq_ver, true );
    wp_enqueue_script( 'menu.js',THEME_URL.'/js/menu.js', array('jquery'), filemtime(THEME_DIR.'/js/menu.js'), true );
	wp_enqueue_script( 'intersection-observer.js',THEME_URL.'/js/intersection-observer.js', array(), filemtime(THEME_DIR.'/js/intersection-observer.js'), true );
	wp_enqueue_script( 'lazyload.js',THEME_URL.'/js/lazyload.js', array('intersection-observer.js'), filemtime(THEME_DIR.'/js/lazyload.js'), true );

}

//カスタムメニュー登録
register_nav_menus( array(
    'global'  => 'グローバルメニュー',
    'sidebar' => 'サイドバー',
));

//ファビコンは使わない
remove_action( 'wp_head', 'wp_site_icon', 99 );
