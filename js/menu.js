(function($){

    //アンカーリンク
    var headerHeight = $('header').outerHeight();
    var urlHash = location.hash;
    if(urlHash) {
        $('body,html').stop().scrollTop(0);
        setTimeout(function(){
            var target = $(urlHash);
            var position = target.offset().top - headerHeight;
            $('body,html').stop().animate({scrollTop:position}, 500);
            return false;
        }, 100);
    }
    $('a[href^="#"]').click(function() {
         var href = $(this).attr("href");
         var target = $( href == "#" || href == "" ? 'html' : href );
         var position = target.offset().top - headerHeight;
         $('body,html').animate({ scrollTop:position}, 500);
         return false;
    });

    var menu = $('nav');

    menu.find('a').on('click',function(){
        if (menu.is(':visible')) {
            menu.slideUp('1500');
        }
    });

    $('#hamburger').on('click', function () {
        if (menu.is(':visible')) {
            menu.slideUp('1500');
        } else {
            menu.slideDown('1500');
        }
    });

    function SlideDown(element){
        $(element).find('ul').slideDown('fast');
    };

    function SlideUp(element){
        $(element).find('ul').slideUp('fast');
    };

    $('.menu > li').on('mouseover', function(){
        SlideDown( this );
    });

    $('.menu').on('mouseleave',function(){
        SlideUp( this );
    });

    var timer = false;

    $(window).on('load resize', function(){

        if (timer !== false) clearTimeout(timer);

        timer = setTimeout(function() {

            $('.menu > li').off('mouseover');
            $('.menu').off('mouseleave');

            if(window.innerWidth > 768){

                $('.menu > li').on('mouseover', function(){
                    SlideDown( this );
                });

                $('.menu').on('mouseleave',function(){
                    SlideUp( this );
                });

            }

        }, 200);


    });

})(jQuery);
