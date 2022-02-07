$(document).ready(function () {

    //Left Menu Active
    $('.accordion > a').click(function (e) {
        e.preventDefault();
        var $ul = $(this).siblings('ul');
        $(".accordion").removeClass('active'); 
        $(this).parent().addClass('active');
        $(".secondary-menu").slideUp();
        $ul.slideToggle();
    });

    //页模块按钮
    $('.btn-close').click(function (e) {
        e.preventDefault();
        $(this).parent().parent().parent().fadeOut();
    });
    //$('.btn-minimize').click(function (e) {
    //    e.preventDefault();
    //    var $target = $(this).parent().parent().next('.box-content');
    //    if ($target.is(':visible')) $('i', $(this)).removeClass('glyphicon-chevron-up').addClass('glyphicon-chevron-down');
    //    else                       $('i', $(this)).removeClass('glyphicon-chevron-down').addClass('glyphicon-chevron-up');
    //    $target.slideToggle();
    //});
    //$('.btn-setting').click(function (e) {
    //    e.preventDefault();
    //    $('#myModal').modal('show');
    //});
});

$(document).on('click', '.btn-minimize', function (e) {
    e.preventDefault();
    var $target = $(this).parent().parent().next('.box-content');
    if ($target.is(':visible')) $('i', $(this)).removeClass('glyphicon-chevron-up').addClass('glyphicon-chevron-down');
    else                       $('i', $(this)).removeClass('glyphicon-chevron-down').addClass('glyphicon-chevron-up');
    $target.slideToggle();
});
function footerposition() {
    if($(window).height()>$("body").height()+60){
        $('footer').css({'position':'absolute','padding':'0 20px'})
    }else{
        $('footer').css({'position':'static','padding':'0 0px'})
    }
}

$(document).ajaxStop(function(){
    footerposition();
})
window.onload=function(){
    footerposition();
}

