<?php

/*
*
* コントローラー
*
*/

class Controller {


    /*

    変数

    */
    public $queried; //メインクエリのオブジェク
    public $title;//ヘッダータイトル
    public $description;//ヘッダーディスクリプション
    public $breadcrumb = array();//パンくずリスト
    private $default_archive_after_text = " 記事一覧";//パンくずリスト用。アーカイブページのデフォルト文字
    private $default_search_after_text = "の検索結果";//パンくずリスト用。検索結果ページのデフォルト文字
    private $default_notfound_text = "このページは見つかりませんでした";//パンくずリスト用。404ページのデフォルト文字


    /*

    初期化

    */
    function __construct( $home_name = null ){
        //メインクエリのオブジェクト取得
        $this->queried = get_queried_object();
        //ヘッダータイトル設定
        $this->title       = get_bloginfo('name');
        //ヘッダーディスクリプション設定
        $this->description = get_bloginfo('description');
        //トップページをパンくずリストの先頭に設定
        $this->addBreadCrumb( $this->title, home_url('/') );
    }



/*



パンくずリスト関連



*/

    /*

    カスタム投稿一覧ページのパンくずリストを生成
    ・・・引数：カスタム投稿名の後テキスト

    */
    public function getPostTypePageBreadCrumb( $after_text = null ){
        //カスタム投稿名に後テキストを付けてパンくずリストに追加
        if( !$after_text ) $after_text = $this->default_archive_after_text;
        $this->addBreadCrumb( $this->queried->label.$after_text );
        //カスタム投稿名をヘッダータイトルに設定
        $this->title       = $this->queried->label;
        //カスタム投稿名と後テキストとサイト説明文をヘッダーディスクリプションに設定
        $this->description = $this->queried->label.$after_text.$this->description;

        return $this->breadcrumb;
    }

    /*

    シングルページのパンくずリストを生成
    ・・・引数：パンくずリストの親階層にしたいタクソノミー

    */
    public function getSinglePageBreadCrumb( $taxonomy_name = null ){
        //記事ID
        $post_id   = $this->queried->ID;
        //投稿タイプ
        $post_type = $this->queried->post_type;
        //パンくずリストにカスタム投稿ページ一覧を追加
        $this->addBreadCrumbForPostType( $post_type );
        //パンくずリストの親にしたいカテゴリー（なければ自動的にcategoryに設定）
        $taxonomy_name = $taxonomy_name ? $taxonomy_name : 'category';
        $taxonomy = get_taxonomy( $taxonomy_name );
        $is_category = $taxonomy->hierarchical ? true : false ;
        //階層を持つタクソノミーの場合のみパンくずリストの親階層になれる
        if( $is_category ){
            //記事のタームを取得
            if( $terms = get_the_terms( $post_id, $taxonomy_name ) ){
                //子を持つターム配列
                $parent_terms = array();
                foreach( $terms as $term ){
                    if( $term->parent ){
                        $parent_terms[] = $term->parent;
                    }
                }
                //子を持たない（＝一番下の階層の）ターム配列
                $child_terms = array();
                foreach( $terms as $term ){
                    $is_child = true;
                    $term_id = $term->term_id;
                    foreach( $parent_terms as $parent_term ){
                        if( $term_id == $parent_term ){
                            $is_child = false;
                            break;
                        }
                    }
                    if( $is_child ) $child_terms[] = $term_id;
                }
                //子を持たない（＝一番下の階層の）タームがひとつだけの時のみパンくずリストの親階層になれる
                if( count( $child_terms ) === 1 ){
                    //親カテゴリーを再帰的に取得
                    $terms = $this->get_ancestors( $child_terms[0], $taxonomy_name );
                    $terms[] = $child_terms[0];
                    //順番にタームをパンくずリストに追加していく
                    foreach( $terms as $term ){
                        $term = get_term_by( 'id', $term, $taxonomy_name );
                        $this->addBreadCrumb( $term->name, get_term_link( $term->term_id ) );
                    }
                }
            }
        }
        //記事タイトルをパンくずリストに追加
        $this->addBreadCrumb( $this->queried->post_title );
        //記事タイトルをヘッダータイトルに設定
        $this->title = $this->queried->post_title;
        //記事本文をヘッダーディスクリプションに設定
        $content = wp_strip_all_tags( $this->queried->post_content, true );
        $this->description = mb_substr( $content, 0, 120 );

        return $this->breadcrumb;

    }

    /*

    カテゴリー・タクソノミー一覧ページのパンくずリストを生成
    ・・・引数：ターム名の後テキスト

    */
    public function getTaxPageBreadCrumb( $after_text = null ){
        //ターム名の後テキストを設定
        if( !$after_text ) $after_text = $this->default_archive_after_text;
        //クエリオブジェクトからタクソノミーオブジェクトを取得
        $taxonomy_name = $this->queried->taxonomy;
        $taxonomy = get_taxonomy( $taxonomy_name );
        //登録されている投稿タイプをパンくずリストに追加
        $registrated_post_type = $taxonomy->object_type[0];
        $this->addBreadCrumbForPostType( $registrated_post_type );
        //階層を持つタクソノミーの場合のみ
        $is_category = $taxonomy->hierarchical ? true : false ;
        if( $is_category ){
            $terms = $this->get_ancestors( $this->queried->term_id, $taxonomy_name );
            //親タームを順番にパンくずリストに追加していく
            foreach( $terms as $term ){
                $term = get_term_by( 'id', $term, $taxonomy_name );
                $this->addBreadCrumb( $term->name, get_term_link( $term->term_id ) );
            }
        }
        //最下層タームをパンくずリストに追加
        $this->addBreadCrumb( $this->queried->name.$after_text );
        //ターム名をヘッダータイトルに設定
        $this->title = $this->queried->name;
        //ターム名と後テキストとサイト説明文をヘッダーディスクリプションに設定
        $this->description = $this->queried->name.$after_text.get_bloginfo('description');

        return $this->breadcrumb;
    }

    /*

    タグ一覧ページのパンくずリストを生成
    ・・・引数：ターム名の後テキスト

    */
    public function getTagPageBreadCrumb( $after_text = null ){

        //上とほとんど同じ

        if( !$after_text ) $after_text = $this->default_archive_after_text;
        $taxonomy = get_taxonomy( $this->queried->taxonomy );
        $registrated_post_type = $taxonomy->object_type[0];
        $this->addBreadCrumbForPostType( $registrated_post_type );
        $this->addBreadCrumb( $this->queried->name.$after_text );
        $this->title = $this->queried->name;
        $this->description = $this->queried->name.$after_text.get_bloginfo('description');

        return $this->breadcrumb;
    }

    /*

    固定ページのパンくずリストを生成

    */
    public function getPageBreadCrumb(){
        //親ページを再帰的に取得
        $pages = $this->get_ancestors( $this->queried->ID, 'page' );
        //親ページを順番にパンくずリストに追加
        foreach( $pages as $page ){
            $page = get_post( $page );
            $this->addBreadCrumb( $page->post_title, get_the_permalink( $page ) );
        }
        //このページをパンくずリストに追加
        $this->addBreadCrumb($this->queried->post_title);
        //記事タイトルをヘッダータイトルに設定
        $this->title = $this->queried->post_title;
        $content = wp_strip_all_tags( $this->queried->post_content, true );
        $this->description = mb_substr( $content, 0, 120 );

        return $this->breadcrumb;
    }

    /*

    検索結果ページのパンくずリストを生成
    ・・・引数：検索ワードの後テキスト

    */
    public function getSearchPageBreadCrumb( $after_text = null ){
        //検索ワードの後テキストを設定
        if( !$after_text ) $after_text = $this->default_search_after_text;
        //検索ワードを取得
        $search_query = get_search_query();
        //検索ワードと後テキストをヘッダータイトルに設定
        $this->title =  "「 " . $search_query . " 」".$after_text;
        //パンくずリストに追加
        $this->addBreadCrumb( $this->title );

        return $this->breadcrumb;
    }

    /*

    ４０４ページのパンくずリストを生成
    引数：パンくずリストに表示するテキスト

    */
    public function getNotFoundPageBreadCrumb( $notfound_text ){
        //表示テキストを設定
        if( !$notfound_text ) $notfound_text = $this->default_notfound_text;
        //パンくずリストに追加
        $this->addBreadCrumb( $notfound_text );
        //テキストをヘッダータイトルに設定
        $this->title = $notfound_text;

        return $this->breadcrumb;
    }

    //プライベート関数：パンくずリストにテキストとリンクの配列を追加
    private function addBreadCrumb( $text = '', $link = '' ){
        array_push( $this->breadcrumb, array(
            'text' => $text,
            'link' => $link,
        ));
    }

    //プライベート関数：パンくずリストに投稿タイプ一覧ページを追加
    private function addBreadCrumbForPostType( $post_type ){
        if( $post_type !== 'post' ){
            $post_type_name = get_post_type_object( $post_type )->labels->singular_name;
            $this->title = $post_type_name;
            $this->addBreadCrumb( $post_type_name, get_post_type_archive_link( $post_type ) );
        }
    }

    //プライベート関数：タームやページの親を再帰的に取得
    private function get_ancestors( $term_id, $taxonomy_or_page ){
        $terms = get_ancestors( $term_id, $taxonomy_or_page );
        return array_reverse( $terms );
    }




/*


★★★★★★★★★★　記事取得関連　★★★★★★★★★★


*/

    /*

    記事データをまとめて取得
    ・・・引数：投稿ID

    */
    public function getPost( $post_id = null ){
        $data = array();
        if( $id = $post_id ? $post_id : get_the_ID() ){
            $data['id']              = $id;
            $data['post_type']       = get_post_type( $id );
            $data['post_type_name']  = get_post_type_object( $data['post_type'] )->labels->singular_name;
            $data['permalink']       = get_the_permalink( $id );
            $data['title']           = get_the_title( $id );
            $data['content']         = apply_filters('the_content', get_post_field('post_content', $id));
            $data['archive_content'] = $this->generateShortContent( $data['content'], 100, '<span class="more">...</span>' );
            $data['thumbnail']       = has_post_thumbnail( $id ) ? get_the_post_thumbnail_url( $id, 'medium' ) : NULL;
            $data['date']            = get_the_date( get_option( 'date_format' ), $id );
            $data['excerpt']         = get_the_excerpt();
        }
        return $data;
    }

    /*

    アーカイブ用に短い記事の本文を作る
    ・・・引数：記事本文、文字数、もっと読むテキスト

    */
    public function generateShortContent( $content, $length, $more ){
        $content = wp_strip_all_tags( $content );
        if( mb_strlen( $content ) <= $length ){
            return $content;
        }else{
            if( !$more ) $more = "...";
            return mb_substr( $content, 0, $length ).$more;
        }
    }

    /*

    カスタムフィールドだけ取得
    ・・・引数：投稿ID

    */
    public function getPostCustomField( $post_id ){
        self::$data['custom_field'] = get_post_meta( $post_id );
        return self::$data;
    }

    /*+

    記事のタームを全て取得
    ・・・引数：投稿ID

    */
    public static function getPostTerms( $post_id ){
        //リターン用ターム配列
        $data = array();
        //ターム（階層ありタイプ）
        $data['category'] = array();
        //ターム（階層なしタイプ）
        $data['tag']      = array();
        //記事のタクソノミーを取得
        if( $taxs = get_post_taxonomies( $post_id ) ){
            //タクソノミーをひとつひとつ処理
            foreach( (array)$taxs as $tax ){
                //タクソノミーがpost_formatじゃない時のみ
                if( $tax !== "post_format" ){
                    $taxonomy = get_taxonomy( $tax );
                    //階層ありタイプか階層なしタイプかに振り分ける
                    $taxonomy_type = $taxonomy->hierarchical ? 'category' : 'tag';
                    //タクソノミー名と投稿IDからタームを取得
                    if( $terms = get_the_terms( $post_id, $tax ) ){
                        foreach( (array)$terms as $term ){
                            $data[$taxonomy_type][$tax][$term->term_id] = array( 'name' => $term->name, 'slug'=> $term->slug, 'link' => get_term_link( $term->term_id ) );
                        }
                    }
                }
            }
        }
        //親子タームが登録されている場合は一番下の子のみを残す
        foreach( $data['category'] as $tax => $terms ){
            foreach( $terms as $term_id => $value ){
                $parent_terms = get_ancestors( $term_id, $tax );
                foreach( $parent_terms as $parent_term ){
                    unset( $data['category'][$tax][$parent_term] );
                }
            }
        }

        return $data;
    }

    /*

    関連記事を取得（シングルページ）
    引数：投稿ID、タクソノミーまたは投稿タイプの配列

    */
    public function getRelatedPosts( $post_id, $taxonomy_names ) {
        //リターン用の配列
        $related_posts = array();
        //記事のタームを取得
        $terms = $this->getPostTerms( $post_id );
        //すべてのカスタム投稿タイプを取得
        $post_types = get_post_types( array('_builtin'=>false,'public'=>true),'names');
        //普通の投稿タイプも追加
        array_push( $post_types, 'post' );
        //まずは投稿タイプかどうかチェック
        foreach( $taxonomy_names as $taxonomy_name ){
            //カスタム投稿タイプだったら
            if(  in_array( $taxonomy_name, $post_types ) ){
                //もう使わないので引数の中からこのカスタム投稿タイプを取り除く
                $taxonomy_names = array_diff( $taxonomy_names, array( $taxonomy_name ) );
                $taxonomy_names = array_values( $taxonomy_names );
                //このカスタム投稿タイプの関連記事を取得
                $args = array(
                    'post_type'   => $taxonomy_name,
                    'post_status' => 'publish',
                    'exclude'     => $post_id,
                    'numberposts' => -1,
                );
                if( $posts = get_posts( $args ) ){
                    $data = array();
                    foreach( $posts as $post ){
                        $data[] = $post->ID;
                    }
                    //リターン配列にタイトルと記事を入れる
                    $related_posts[] = [ 'title' => get_post_type_object( $taxonomy_name )->labels->singular_name, 'post' => $data ];
                }
            }

        }
        //次はタクソノミーをチェック
        //階層ありタイプ（カテゴリー）と階層なしタイプ（タグ）のタームを結合
        if( $all_terms = $terms['category'] + $terms['tag'] ){
            foreach( $taxonomy_names as $taxonomy_name ){
                //このタクソノミーがあったら
                if( $all_terms[$taxonomy_name] ){
                    foreach( $all_terms[$taxonomy_name] as $terms){
                            //このタクソノミーの関連記事を取得
                            $args = array(
                                'post_type'   => $post_types,
                                'post_status' => 'publish',
                                'exclude'     => $post_id,
                                'numberposts' => -1,
                                'tax_query'   => array(
                                    array(
                                        'taxonomy' => $taxonomy_name,
                                        'field'    => 'slug',
                                        'terms'    => $terms['slug'],
                                    )
                                ),
                            );
                            if( $posts = get_posts( $args ) ){
                                $data = array();
                                foreach( $posts as $post ){
                                    $data[] = $post->ID;
                                }
                                //リターン配列にタイトルと記事を入れる
                                $related_posts[] = [ 'title' => $terms['name'], 'post' => $data ];
                            }
                    }
                }
            }
        }

        return $related_posts;

    }

    /*

    関連記事を取得（固定ページ）
    ・・・引数：投稿ID
    */
    public function getRelatedPages( $post_id ){
        //リターン用の配列
        $related_pages = array();
        //投稿IDから記事を取得
        $post = get_post( $post_id );
        //親がいたら
        if( $parents = get_ancestors( $post_id, 'page') ){
            $parents = array_reverse( $parents );
            //一番上の親の関連記事（子ページ）を取得
            $target = $parents[0];
            $parent = get_post( $target );
            $title = $parent->post_title;
        //親がいなかったら
        }else{
            //この記事の関連記事（子ページ）を取得
            $target = $post_id;
            $title  = $post->post_title;
        }
        //子ページを再帰的に取得する
        $this->get_children( $target, $related_pages );
        //ターゲットの情報を取り除く
        $related_pages = $related_pages[$target];

        return array(
            'title' => $title,//の関連記事はこちらのタイトル
            'pages' => $related_pages//記事
        );

    }
    //子ページを再帰的に取得する
    private function get_children( $post_id, &$args ){
        if( $pages = get_children( array( 'post_parent' => $post_id, 'post_type' => 'page' ) ) ){
            $args[$post_id] = array();
            foreach( $pages as $page ){
                $this->get_children( $page->ID, $args[$post_id] );
            }
        }else{
            $args[$post_id] = NULL;
        }
    }




    /*

    ページネーション出力
    引数：クエリ、前のページへテキスト、次のページへテキスト

    */
    public function pagination( $query = null, $prev_text = null, $next_text = null ){
        global $wp_query;
        $current_query = $query ? $query : $wp_query ;
        $big = 999999999;
        $args =	array(
            'type' => 'array',
            'base' => str_replace( $big, '%#%', esc_url( get_pagenum_link( $big ) ) ),
            'current' => max( 1, get_query_var('paged') ),
            'total' => $current_query->max_num_pages,
            'prev_text' => $prev_text ? $prev_text : "前のページへ",
            'next_text' => $next_text ? $next_text : "次のページへ",
        );
        $pager = paginate_links( $args );
        if( !$pager ) return;
        echo "<ul class='pagination'>";
        foreach( $pager as $page ){
            if ( strpos( $page, 'next' ) != false ){
                echo "<li class='next'>" . $page . "</li>";
            } elseif ( strpos( $page, 'prev' ) != false ){
                echo "<li class='prev'>" . $page . "</li>";
            } elseif ( strpos( $page, 'current' ) != false ){
                echo "<li class='current'>" . $page . "</li>";
            } elseif ( strpos( $page, 'dots' ) != false ){
                echo "<li class='dots'>" . $page . "</li>";
            } else {
                echo "<li>" . $page . "</li>";
            }
        }
        echo "</ul>";
    }



}


?>
