    //模态框验证
    $(function() {
        //数组去重
        Array.prototype.unique = function() {
            var resultArray_line=[];
            for(var i = 0,len = this.length;i < len;i++){ 
                !RegExp(this[i],"g").test(resultArray_line.join(",")) && (resultArray_line.push(this[i])); 
            }
            return resultArray_line;
        }
        //数组去空
        Array.prototype.delnull = function() {
            for(var i = 0 ;i<this.length;i++){
                 if(this[i] == "" || typeof(this[i]) == "undefined")
                 {
                  this.splice(i,1);
                  i= i-1;
                 }
            }
            return this;
        }
        //删除左右两端的空格

        function trim(str) {
            return str.replace(/(^\s*)|(\s*$)/g, "");　　
        }

        //过滤空格

        function ignoreSpaces(string) {
            var temp = "";
            string = '' + string;
            splitstring = string.split(" ");
            for (i = 0; i < splitstring.length; i++)
                temp += splitstring[i];
            return temp;
        }

        //自定义信息增加拖拽效果
        var fixHelper = function(e, ui) {
            ui.children().each(function() {
                $(this).width($(this).width());
            });
            return ui;
        };
        $('#info').sortable({
            helper: fixHelper,
            axis: "y",
            start: function(e, ui) {
                ui.helper.css({
                    "background": "#fff"
                })
                return ui;
            },
            stop: function(e, ui) {
                return ui;
            }
        });
        $('#info').disableSelection();
        //模态框数据类型变化
        $(".js_data_type").change(function() {
            var type = $(this).val();
            $(this).parent().parent().next().find("select option:eq(0)").removeClass("hidden");
             // $(this).parent().parent().prev().prev().prev().find("td:eq(1)").children().val("");
            $(this).parent().parent().prev().prev().prev().find("td:eq(1)").children().attr("readonly",false);
            switch (type){
                case 'inputText':
                    $(".fild_on").hide();
                    $(".reg_rule").show();
                    break;
                case 'textarea':
                    $(".fild_on").hide();
                    break;
                case 'date':
                    $(".fild_on").hide();
                    break;
                case 'upload':
                    $(".fild_on").hide();
                    break;
                case 'ueditor':
                    $(".fild_on").hide();
                    $(this).parent().parent().prev().prev().prev().find("td:eq(1)").children().val("t_rich_text");
                    $(this).parent().parent().prev().prev().prev().find("td:eq(1)").children().attr("readonly","readonly");
                    break;
                default:
                    $(".fild_on").hide();
                    $(".option_filds").show();
            }
        })
        //validate增加自定义方法：输入框值是否为空
        jQuery.validator.addMethod("isempty", function(value, element) {
            var empty = trim(value);
            return this.optional(element) || empty;
        }, "");

        //英文字符验证    
        jQuery.validator.addMethod("isenword", function(value, element) {
            var word = /^[a-zA-Z_-]*$/g;
            value = trim(value);
            return this.optional(element) || (word.test(value));
        }, "");
        //验证code是否已经存在
        jQuery.validator.addMethod("new_isexist", function(value, element) {
            var exist = 1;
            value = trim(value);
            $("#info > tr > td:nth-child(2)").each(function() {
                var name = $(this).children().attr("name");
                name = name.substr(name.indexOf("_") + 1);
                if (value == name) {
                    exist = 0;
                    return false;
                }
            })
            return this.optional(element) || exist;
        }, "");
        jQuery.validator.addMethod("edit_isexist", function(value, element) {
            var exist = 1;
            value = trim(value);
            $("#info > tr > td:nth-child(2)").each(function() {
                var name = $(this).children().attr("name");
                name = name.substr(name.indexOf("_") + 1);
                if (name != edit_code) {
                    if (value == name) {
                        exist = 0;
                        return false;
                    }
                }
            })
            return this.optional(element) || exist;
        }, "");
        //验证是否输入选项内容
        jQuery.validator.addMethod("hasoption", function(value, element) {
            var option = ignoreSpaces(value);
            var option1 = option.replace(/[\r\n]/g, "")
            return this.optional(element) || option1;
        }, "");
        //增加自定义表单，提交验证
        $("#modal_validate").validate({
            rules: {
                textarea_options: {
                    required: true,
                    hasoption: []
                },
                label_name: {
                    required: true,
                    isempty: []
                }, 
                label_code: {
                    required: true,
                    isenword: [],
                    new_isexist: [],
                    isempty: []
                }
            },
            messages: {
                textarea_options: {
                    required: "选项不能为空",
                    hasoption: "选项不能为空"
                },
                label_name: {
                    required: "标签名称不能为空",
                    isempty: "不能只输入空格哦"
                },
                label_code: {
                    required: "标签代码不能为空",
                    isenword: "请输入英文字符",
                    new_isexist: "代码已存在，请重新输入",
                    isempty: "不能只输入空格哦"
                }
            },
            submitHandler: function() {
                var star = parseInt($("#modal_validate select[name='label_required'] :selected").val()) ? '<i class="glyphicon glyphicon-star red"></i>' : '';
                var html_head = '<tr>' +
                    '<td width="20%">' + star + '<span class="js_name">' + trim($("#modal_validate input[name='label_name']").val()) + '</span></td>';
                var html;
                var html_public = '<td width="25%"></td>' +
                    '<td width="15%">' +
                    '<div class="box-icon">' +
                    '<a class="btn btn-setting btn-round btn-default"><i class="glyphicon glyphicon-cog"></i></a>' +
                    '<a class="btn btn-minimize btn-round btn-default"><i class="glyphicon glyphicon-move"></i></a>' +
                    '<a class="btn btn-close btn-round btn-default"><i class="glyphicon glyphicon-remove"></i></a>' +
                    '</div>' +
                    '</td>' +
                    '</tr>';
                var data_require = parseInt($("#modal_validate select[name='label_required'] :selected").val()) ? 'required' : '';
                var type = $("#modal_validate .js_data_type :selected").val();
                var special_ps_htm="";
                if($(".js_special_ps").val()){
                    special_ps_htm = '<p class="info_special_ps">'+$(".js_special_ps").val()+'</p>';
                }
                switch(type){
                    case "inputText":
                        html = '<td width="40%" title="'+trim($("#modal_validate input[name='label_name']").val())+'" data-container="body" data-toggle="popover" data-placement="right" data-trigger="hover" data-content="'+trim($("#modal_validate .js_new_ps").val())+'"><input type="text" class="form-control"' + data_require + ' name="t_' + trim($("#modal_validate input[name='label_code']").val()) + '" data-label="'+trim($("#modal_validate input[name='label_name']").val())+'" value="" data-rule-regex="' + trim($("#modal_validate input[name='label_rule']").val()) + '" data-msg-regex="' + trim($("#modal_validate input[name='label_remsg']").val()) + '">'+special_ps_htm+'</td>';
                        break;
                    case "textarea":
                        html ='<td width="40%" title="'+trim($("#modal_validate input[name='label_name']").val())+'" data-container="body" data-toggle="popover" data-placement="right" data-trigger="hover" data-content="'+trim($("#modal_validate .js_new_ps").val())+'"><textarea type="text" class="form-control"' + data_require + ' name="t_' + trim($("#modal_validate input[name='label_code']").val()) + '" data-label="'+trim($("#modal_validate input[name='label_name']").val())+'" value="" rows="3"></textarea>'+special_ps_htm+'</td>';

                        break;
                    case "date":
                        html ='<td width="40%" title="'+trim($("#modal_validate input[name='label_name']").val())+'" data-container="body" data-toggle="popover" data-placement="right" data-trigger="hover" data-content="'+trim($("#modal_validate .js_new_ps").val())+'"><input type="text" class="form-control form_datetime"' + data_require + ' name="t_' + trim($("#modal_validate input[name='label_code']").val()) + '" data-label="'+trim($("#modal_validate input[name='label_name']").val())+'" readonly size="16">'+special_ps_htm+'</td>';
                        break;
                    case "upload":
                        html ='<td width="40%" title="'+trim($("#modal_validate input[name='label_name']").val())+'" data-container="body" data-toggle="popover" data-placement="right" data-trigger="hover" data-content="'+trim($("#modal_validate .js_new_ps").val())+'"><div id="uploader" name="h_' + trim($("#modal_validate input[name='label_code']").val()) + '" class="wu-example"><div id="thelist" class="uploader-list"></div><div class="btns"><div id="picker">选择文件</div><input type="hidden" class="form-control"' + data_require + ' name="t_' + trim($("#modal_validate input[name='label_code']").val()) + '" data-label="'+trim($("#modal_validate input[name='label_name']").val())+'" value=""></div></div>'+special_ps_htm+'</td>';
                        break;
                    case "ueditor":
                        html ='<td width="40%" title="'+trim($("#modal_validate input[name='label_name']").val())+'" data-container="body" data-toggle="popover" data-placement="right" data-trigger="hover" data-content="'+trim($("#modal_validate .js_new_ps").val())+'"><div name="t_' + trim($("#modal_validate input[name='label_code']").val()) + '" ' + data_require + ' data-label="'+trim($("#modal_validate input[name='label_name']").val())+'"><script id="editor" type="text/plain" style="width:100%;height:100px;"></script></div> <input type="hidden" name="t_rich_text" />'+special_ps_htm+'</td>';
                        break;
                    default:
                         //各项转化为数组
                        var options = ignoreSpaces($("#modal_validate .option_filds textarea").val()).split(/\n/)
                        options = options.unique();//去重
                        options = options.delnull();//去空
                        if (type == "select") {
                            //拼接option
                            var option_html = "";
                            for (var i = 0; i <options.length; i++) {
                                option_html += '<option value="' + (i+1)+ '">' + options[i] + '</option>'
                            }
                            if(!data_require){
                                option_html = '<option value="">请选择</option>'+option_html;
                            }
                            html = '<td width="40%" title="'+trim($("#modal_validate input[name='label_name']").val())+'" data-container="body" data-toggle="popover" data-placement="right" data-trigger="hover" data-content="'+trim($("#modal_validate .js_new_ps").val())+'"><select class="form-control"' + data_require + ' name="t_' + trim($("#modal_validate input[name='label_code']").val()) + '" data-label="'+trim($("#modal_validate input[name='label_name']").val())+'">' + option_html + '</select>'+special_ps_htm+'</td>';
                        } else if (type == "checkbox") {
                            //拼接option
                            var option_html = "";
                            for (var i = 0; i < options.length; i++) {
                                if((i==0) && data_require){
                                    option_html += '<input type="checkbox" name="t_' + trim($("#modal_validate input[name='label_code']").val()) + '[]" data-label="'+trim($("#modal_validate input[name='label_name']").val())+'" value="' + (i+1) + '"' + data_require + ' checked="checked"><label>' + options[i] + '</label><br>'

                                }else{
                                    option_html += '<input type="checkbox" name="t_' + trim($("#modal_validate input[name='label_code']").val()) + '[]" data-label="'+trim($("#modal_validate input[name='label_name']").val())+'" value="' + (i+1) + '"' + data_require + '><label>' + options[i] + '</label><br>';
                                }
                            }
                            html = '<td width="40%" title="'+trim($("#modal_validate input[name='label_name']").val())+'" data-container="body" data-toggle="popover" data-placement="right" data-trigger="hover" data-content="'+trim($("#modal_validate .js_new_ps").val())+'">' + option_html + ''+special_ps_htm+'</td>';

                        } else if (type == "radio") {
                            //拼接option
                            var option_html = "";
                            for (var i = 0; i < options.length; i++) {
                                if((i==0) && data_require){
                                    option_html += '<input type="radio" name="t_' + trim($("#modal_validate input[name='label_code']").val()) + '" data-label="'+trim($("#modal_validate input[name='label_name']").val())+'" value="' + (i+1) + '"' + data_require + ' checked="checked"><label>' + options[i] + '</label>　　'

                                }else{
                                    option_html += '<input type="radio" name="t_' + trim($("#modal_validate input[name='label_code']").val()) + '" data-label="'+trim($("#modal_validate input[name='label_name']").val())+'" value="' + (i+1) + '"' + data_require + '><label>' + options[i] + '</label>　　'
                                }
                            }
                           
                            html = '<td width="40%" title="'+trim($("#modal_validate input[name='label_name']").val())+'" data-container="body" data-toggle="popover" data-placement="right" data-trigger="hover" data-content="'+trim($("#modal_validate .js_new_ps").val())+'">' + option_html + ''+special_ps_htm+'</td>';
                        } else {
                            //下拉多选框(暂时不需要该功能)
                            //拼接option
                            var option_html = "";
                            for (var i = 0; i < options.length; i++) {
                                option_html += '<option value="' + (i+1) + '">' + options[i] + '</option>'
                            }
                            html = '<td width="40%" title="'+trim($("#modal_validate input[name='label_name']").val())+'" data-container="body" data-toggle="popover" data-placement="right" data-trigger="hover" data-content="'+trim($("#modal_validate .js_new_ps").val())+'"><select class="multiple form-control"' + data_require + ' name="t_' + trim($("#modal_validate input[name='label_code']").val()) + '" data-label="'+trim($("#modal_validate input[name='label_name']").val())+'" ' + data_require + '  multiple>' + option_html + '</select>'+special_ps_htm+'</td>';
                        }
                }
                $('#info').append(html_head + html + html_public);
                // $('.multiple').select2();
                $('[data-toggle="popover"]').popover();
                $('#customTableModal').modal('hide');
                if(type == "ueditor"){
                    var ue = UE.getEditor('editor');
                }
            },
            debug: true,
            onkeyup: false
        })
        //修改自定义表单--提交验证
        $("#edit_validate").validate({
            rules: {
                textarea_options: {
                    required: true,
                    hasoption: []
                },
                label_name: {
                    required: true,
                    isempty: []
                },
                label_code: {
                    required: true,
                    isenword: [],
                    edit_isexist: [],
                    isempty: []
                }
            },
            messages: {
                textarea_options: {
                    required: "选项不能为空",
                    hasoption: "选项不能为空0000"
                },
                label_name: {
                    required: "标签名称不能为空",
                    isempty: "不能只输入空格哦"
                },
                label_code: {
                    required: "标签代码不能为空",
                    isenword: "请输入英文字符",
                    edit_isexist: "代码已存在，请重新输入"
                }
            },
            submitHandler: function() {
                var edit_element = $("#info tr").eq(edit_index);//需要编辑的行元素
                var star = parseInt($("#edit_validate select[name='label_required'] :selected").val()) ? '<i class="glyphicon glyphicon-star red"></i>' : '';
                var data_require = parseInt($("#edit_validate select[name='label_required'] :selected").val()) ? 'required' : '';
                //修改星号和字段名称
                edit_element.find("td").eq(0).html(star + '<span class="js_name">' + trim($("#edit_validate input[name='label_name']").val()) + '</span>');
                //根据type 修改表单输入项的内容
                var type = $("#edit_validate .js_data_type :selected").val();
                //修改提示信息
                edit_element.find("td").eq(1).attr("data-original-title",trim($("#edit_validate input[name='label_name']").val()));
                edit_element.find("td").eq(1).attr("data-content",trim($(".js_edit_ps").val()));
                $('[data-toggle="popover"]').popover();
                var edit_special_ps_htm="";
                if($(".js_edit_special_ps").val()){
                    edit_special_ps_htm = '<p class="info_special_ps">'+$(".js_edit_special_ps").val()+'</p>';
                }

                if (type == "inputText") {
                    edit_element.find("td").eq(1).html('<input type="text" class="form-control"' + data_require + ' name="t_' + trim($("#edit_validate input[name='label_code']").val()) + '" data-label="'+trim($("#edit_validate input[name='label_name']").val())+'" value="" data-rule-regex="' + trim($("#edit_validate .reg_rule input").val()) + '" data-msg-regex="'+ trim($("#edit_validate input[name='label_remsg']").val()) +'"> '+edit_special_ps_htm);
                } else if (type == "textarea") {
                    edit_element.find("td").eq(1).html('<textarea type="text" class="form-control"' + data_require + ' name="t_' + trim($("#edit_validate input[name='label_code']").val()) + '" data-label="'+trim($("#edit_validate input[name='label_name']").val())+'" value="" rows="3"></textarea> '+edit_special_ps_htm);
                } else if (type == "date") {
                    edit_element.find("td").eq(1).html('<input type="text" class="form-control form_datetime"' + data_require + ' name="t_' + trim($("#edit_validate input[name='label_code']").val()) + '" data-label="'+trim($("#edit_validate input[name='label_name']").val())+'" readonly  size="16" value=""> '+edit_special_ps_htm);
                    $(".form_datetime").datetimepicker({format: 'yyyy-mm-dd hh:ii'});
                }else if (type == "upload") {
                    edit_element.find("td").eq(1).html('<div id="uploader"  name="h_' + trim($("#edit_validate input[name='label_code']").val()) + '" class="wu-example"><div id="thelist" class="uploader-list"></div><div class="btns"><div id="picker">选择文件</div><input type="hidden" class="form-control"' + data_require + ' name="t_' + trim($("#edit_validate input[name='label_code']").val()) + '" data-label="'+trim($("#edit_validate input[name='label_name']").val())+'" value=""></div></div>'+edit_special_ps_htm);
                }else if (type == "ueditor") {
                    edit_element.find("td").eq(1).html('<div name="t_' + trim($("#edit_validate input[name='label_code']").val()) + '" ' + data_require + ' data-label="'+trim($("#edit_validate input[name='label_name']").val())+'"><script id="editor" type="text/plain" style="width:100%;height:100px;"></script></div> <input type="hidden" name="t_rich_text" />'+edit_special_ps_htm);
                        // var ue = UE.getEditor('editor');
                } else {
                    //各项转化为数组
                    var options = ignoreSpaces($("#edit_validate .option_filds textarea").val()).split(/\n/);
                    options = options.unique();//去重
                    options = options.delnull();//去空
                    if (type == "select") {
                        //拼接option
                        var option_html = "";
                        for (var i = 0; i < options.length; i++) {
                            option_html += '<option value="' + (i+1) + '">' + options[i] + '</option>'
                        }
                        //不是必填项第一项置为请选择
                        if(!data_require){
                            option_html = '<option value="">请选择</option>'+option_html;
                        }
                        edit_element.find("td").eq(1).html('<select type="text" class="form-control"' + data_require + ' name="t_' + $("#edit_validate input[name='label_code']").val() + '" data-label="'+trim($("#edit_validate input[name='label_name']").val())+'" value="" rows="1">' + option_html + '</select> '+edit_special_ps_htm);
                    } else if (type == "checkbox") {
                        //拼接option
                        var option_html = "";
                        for (var i = 0; i < options.length; i++) {
                            if((i==0) && data_require){
                                option_html += '<input type="checkbox" name="t_' + trim($("#edit_validate input[name='label_code']").val()) + '[]" data-label="'+trim($("#edit_validate input[name='label_name']").val())+'" value="' + (i+1) + '"' + data_require + ' checked="checked"><label>' + options[i] + '</label><br>'

                            }else{
                                option_html += '<input type="checkbox" name="t_' + trim($("#edit_validate input[name='label_code']").val()) + '[]" data-label="'+trim($("#edit_validate input[name='label_name']").val())+'" value="' + (i+1) + '"' + data_require + '><label>' + options[i] + '</label><br>';
                            }
                        }
                        edit_element.find("td").eq(1).html(option_html+edit_special_ps_htm);
                    } else if (type == "radio") {
                        var option_html = "";

                        for (var i = 0; i < options.length; i++) {
                            if((i==0) && data_require){
                                option_html += '<input type="radio" name="t_' + trim($("#edit_validate input[name='label_code']").val()) + '" data-label="'+trim($("#edit_validate input[name='label_name']").val())+'" value="' + (i+1) + '"' + data_require + ' checked="checked"><label>' + options[i] + '</label>　　'

                            }else{
                                option_html += '<input type="radio" name="t_' + trim($("#edit_validate input[name='label_code']").val()) + '" data-label="'+trim($("#edit_validate input[name='label_name']").val())+'" value="' + (i+1) + '"' + data_require + '><label>' + options[i] + '</label>　　'
                            }
                        }
                        edit_element.find("td").eq(1).html(option_html+edit_special_ps_htm);
                    } else {
                        //拼接option
                        var option_html = "";
                        for (var i = 0; i < options.length; i++) {
                            option_html += '<option value="' + (i+1) + '">' + options[i] + '</option>'
                        }
                        edit_element.find("td").eq(1).html('<select class="multiple form-control"' + data_require + ' name="t_' + $("#edit_validate input[name='label_code']").val() + '" data-label="'+trim($("#edit_validate input[name='label_name']").val())+'" ' + data_require + '  multiple>' + option_html + '</select>'+edit_special_ps_htm);
                        $('.multiple').select2();
                    }
                }
                $('#editTableModal').modal('hide');
            },
            debug: true,
            onkeyup: false
        })

    })
  
    $(document).ready(function() {
        $('#IssueTrack').addClass('active').find('ul').slideToggle();
        $('#issue-admin').css({
            "background-color": "#eeeeee"
        });

        $("#commentForm").validate({
            errorPlacement: function(error, element) {
                $(element).closest("tr").children('td').eq(2).html(error);
            },
            submitHandler: function() {
                $.alert("验证通过", chek_dia_close);
            },
            debug: true,
            onkeyup: false
        });
        function chek_dia_close(){
            return false;
        }
        //删除自定义信息
        $(document).on('click', "i", function(e) {
            if ($(this).hasClass("glyphicon-remove")) {
                customTable.del($(this).parents("tr"));
            } else if ($(this).hasClass("glyphicon-cog")) {
                customTable.edit($(this).parents("tr"));
            }

        })


    });

    var edit_code = "";
    var edit_index = ""
    var customTable = {
        new: function() {
            //是否显示下拉框中上传插件选择
            if($("#file_upload").size()){
                $('#customTableModal .js_data_type option:last').addClass("hidden");
            }else{
                $('#customTableModal .js_data_type option:last').removeClass("hidden");
            }
            if($("#editor").size()){
                $('#customTableModal .js_data_type option:eq(6)').addClass("hidden");
            }else{
                $('#customTableModal .js_data_type option:eq(6)').removeClass("hidden");
            }
            
            $('#customTableModal').modal('show');
            $("#modal_validate")[0].reset();
        },
        edit: function(obj) {
            edit_code = "";
            edit_index = obj.index();
            var name = obj.find(".js_name").html();
            var code = obj.find("td").eq(1).children().attr("name");
            code = code.substr(code.indexOf("_") + 1);
            edit_code = code;
            var element = obj.find("td").eq(1).children();
            var require = element.attr("required");
            var tagName = element.get(0).tagName.toLowerCase();
            //是否显示下拉框中上传插件选择
            if($("#file_upload").size() && ($("#file_upload").parent().parent().index()!=edit_index)){
                $('#editTableModal .js_data_type option:last').addClass("hidden");
            }else{
                $('#editTableModal .js_data_type option:last').removeClass("hidden");
            }
            //是否显示下拉框中富文本编辑器插件选择
            if($("#editor").size() && ($("#editor").parent().parent().index()!=edit_index)){
                $('#editTableModal .js_data_type option:eq(6)').addClass("hidden");
            }else{
                $('#editTableModal .js_data_type option:eq(6)').removeClass("hidden");
            }
            $('#editTableModal label').remove();
            $('#editTableModal input').removeClass("error");
            $('#editTableModal').modal('show');
            $('#editTableModal .js_edit_name').val(name);
            $('#editTableModal .js_edit_special_ps').val( obj.find(".info_special_ps").html());
            //复选框需要去掉t_ 和 []
            if(element.attr("type") == "checkbox"){
                $('#editTableModal .js_edit_code').val(code.substr(0,code.indexOf("[")));
            }else{
                $('#editTableModal .js_edit_code').val(code);
            }

            $('#editTableModal .js_edit_ps').val(obj.find("td").eq(1).attr("data-content"));
            if ( !! require) {
                $('#editTableModal .js_edit_require').val("1");
            } else {
                $('#editTableModal .js_edit_require').val("0");
            }
            if (tagName == "input") {
                if (element.attr("type") == "text") { //文本输入框
                    $('#editTableModal .js_data_type').val("inputText");
                    $('#editTableModal input[name="label_rule"]').val(element.attr("data-rule-regex"));
                    $('#editTableModal input[name="label_remsg"]').val(element.attr("data-msg-regex"));
                } else if (element.attr("type") == "checkbox") { //复选框
                    $('#editTableModal .js_data_type').val("checkbox");
                    var parent_element = element.parent();
                    var option_arr = [];
                    for (var i = 0; i < parent_element.children("input").length; i++) {
                        option_arr.push(parent_element.children("label").eq(i).html());
                    }
                    $('#editTableModal textarea').val(option_arr.join("\r\n"));

                } else if (element.attr("type") == "radio") { //单选按钮
                    $('#editTableModal .js_data_type').val("radio");
                    var parent_element = element.parent();
                    var option_arr = [];
                    for (var i = 0; i < parent_element.children("input").length; i++) {
                        option_arr.push(parent_element.children("label").eq(i).html());
                    }
                    $('#editTableModal textarea').val(option_arr.join("\r\n"));
                } else{
                   
                }
            } else if (tagName == "select") {
                if (element.hasClass("multiple")) { //下拉多选列表
                    $('#editTableModal .js_data_type').val("selectMultiple");
                } else { //下拉单选列表
                    $('#editTableModal .js_data_type').val("select");
                }
                var option_arr = [];
                for (var i = 0; i < element.children("option").length; i++) {
                    if(element.children("option").eq(i).val()){
                        option_arr.push(element.children("option").eq(i).text());
                    }
                }
                $('#editTableModal textarea').val(option_arr.join("\r\n"));
            } else if (tagName == "textarea"){ //文本输入域
                $('#editTableModal .js_data_type').val(tagName);
            } else{
                if(element.attr("name") =="t_t_rich_text"){
                    $('#editTableModal .js_data_type').val("ueditor");
                }else{
                    //上传插件
                     $('#editTableModal .js_data_type').val("upload");
                }
            }
            $("#editTableModal .js_data_type").trigger("change");
        },
        del: function(obj) {
            obj.remove();

        }
    }