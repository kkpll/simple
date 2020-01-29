<?php

/*
*
* シンプルメニュー
*
*/

class SimpleMenuWalker extends Walker_Nav_Menu {
    function start_lvl( &$output, $depth = 0, $args = Array() ){$output .= "";}
    function end_lvl( &$output, $depth = 0, $args = Array() ) {$output .= "";}
    function start_el( &$output, $item, $depth = 0, $args = Array(), $id = 0 ) {
        if (in_array('menu-item-has-children', $item->classes)) {
            $indent = " ";
            $output .= "\n".'<li>';
            $output .= "<a href='".$item->url."'>".$item->title."</a>";
            $output .= "\n" . $indent . '<ul class="sub-menu">';
        } else {
            $output .= '<li>';
            $output .= "<a href='".$item->url."'>".$item->title."</a>";
        }
    }
    function end_el( &$output, $item, $depth = 0, $args = Array() ) {
        if (in_array('menu-item-has-children', $item->classes)) {
            $output .= "\n".'</li></ul></li>';
        }
        else {
            $output .= "\n".'</li>';
        }
    }
}


 ?>
