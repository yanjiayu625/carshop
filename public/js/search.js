var pagetop = 0;
$.fn.selectOption=function(opt) {
    $(document).on("click","#"+opt,function(){
        pagetop = $(window).scrollTop();
        $.get("/index.php?m=ajax&ajax=1&"+opt+"=1", {
        }, function (data, textStatus){
            $("#selectbox").show();
            $("#selectbox").html(data); // 把返回的数据添加到页面上
            //$("#mainbox").hide();
            //$('html').css({"height":"100%","overflow":"hidden"});
            //$('body').css({"height":"100%","overflow":"hidden"});
        });
    });
};