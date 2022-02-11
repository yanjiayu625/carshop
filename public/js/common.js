
layui.config({
    dir: '/static/layui/'
    ,version: true
    ,debug: true
    ,base: '/static/layui/lay/modules/'
});
layui.use('element', function(){
    var element = layui.element; //导航的hover效果、二级菜单等功能，需要依赖element模块
});