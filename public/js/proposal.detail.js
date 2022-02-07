var list = {
    init: function() {
        var that = this;
        that.render_log();
        that.get_data();
        that.getServerRelateInfo();
        $('[data-toggle="tooltip"]').popover();//流程btn hover显示‘查看流程文字’
        $(".js_showprocessing").click(function() {//显示 流程状态
            that.showprocessing();
        })

        $(document).on("click", ".js_details img", function() {
            var src = $(this).attr("src");
            var showPicHtml = "";
            showPicHtml+='<div class="modal fade in" id="showBigPic" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="false">';
            showPicHtml+='    <img class="bigPicImg" style="display: block; min-width: 800px; margin: 0 auto;position: absolute; left: 50%; top: 50%;" src="'+src+'">';
            showPicHtml+='</div>';

            $("#showBigPic").remove();
            $('body').append(showPicHtml);
            $("#showBigPic").modal('show');
            setTimeout( function() {
                $(".bigPicImg").css({marginTop: -$(".bigPicImg").height()/2, marginLeft: -$(".bigPicImg").width()/2})

            }, 200 )
        })
    },
    append_htm: function(data) {
        return '<div class="progress progress-striped active"><div class="progress-bar progress-bar-' + data.css + ' progress-bar-striped" style="width: ' + data.width + ';"></div></div><p style="margin-top:-43px;;text-align: center;color: #003399;">' + data.word + '</p>';
    },
    render_info:function(result){
        var that = this;
        var statusCode = {
            '0': '<span class="label label-warning">未批准</span>',
            '1': '<span class="label label-success">已批准</span>',
            '2': '<span class="label label-danger">重开</span>',
            '3': '<span class="label label-default">已关闭</span>',
            '4': '<span class="label label-success">已执行</span>'
        };
        var resultCode = {
            '0': '<span class="label label-info">处理中</span>',
            '1': '<span class="label label-success">成功</span>',
            '2': '<span class="label label-default">失败</span>',
            '3': '<span class="label label-danger">驳回</span>',
            '4': '<span class="label label-default">取消</span>',
            '5': '<span class="label label-danger">终止</span>'
        };
        var riskCode = {
            '0': '<span >无</span>',
            '1': '<span class="label label-default">低</span>',
            '2': '<span class="label label-danger">高</span>'
        };
        var urgentCode = {
            '1': '<span class="label label-default">非紧急</span>',
            '2': '<span class="label label-warning">紧急</span>'
        };
        if (result.status) {
            $(".js_id").html(result.data.id);
            $(".js_title").html(result.data.i_title);
            $(".js_name").html(result.data.i_name);
            $(".js_applicant").html(result.data.i_applicant);
            $(".js_email").html(result.data.i_email);
            $(".js_department").html(result.data.i_department);
            $(".js_createtime").html(result.data.c_time);
            $(".js_endtime").html(result.data.f_time);
            $(".js_updatetime").html(result.data.u_time);
            $(".js_exe_person").html(result.data.exe_person);
            $(".js_result").html(resultCode[result.data.i_result]);
            $(".js_status").html(statusCode[result.data.i_status]);
            $(".js_risk").html(riskCode[result.data.i_risk]);
            $(".js_urgent").html(urgentCode[result.data.i_urgent]);

            //审批记录
            var stepCheck = 1;
            $.each(result.data.checkJson.list.check, function(k, v) {
                var html;
                if (v.status == "1") { //正在审核
                    html = that.append_htm({css:"success", width:"50%", word:"正在审批提案"});
                } else if (v.status == "0") { //
                    html = that.append_htm({css:"striped", width:"50%", word:"等待审批提案"});
                } else if (v.status == "2") { //
                    html = that.append_htm({css:"success", width:"100%", word:"完成审批提案"});
                } else { //3驳回
                    html = that.append_htm({css:"danger", width:"100%", word:"驳回提案"});
                }
                var agent = v.agent? '('+v.agent+'代)' : '';
                $(".js_checkJson").append('<tr><td width="30%">Step' + stepCheck + '审批结果</td><td width="70%">' + k +agent+ '<br>' + html + '</td></tr>');
                stepCheck++;

            })

            //会签人审批记录
            var stepCS =1;
            $.each(result.data.checkJson.list.cs, function(k, v) {
                var html;
                if (v.status == 1) { //正在审核
                    html = that.append_htm({css:"success", width:"50%", word:"正在审批提案"});
                } else if (v.status == 0) { //
                    html = that.append_htm({css:"striped", width:"50%", word:"等待审批提案"});
                } else if (v.status == 2) { //
                    html = that.append_htm({css:"success", width:"100%", word:"完成审批提案"});
                } else { //3驳回
                    html = that.append_htm({css:"danger", width:"100%", word:"驳回提案"});
                }
                $(".js_checkJson").append('<tr><td width="30%">会签人审批结果</td><td width="70%">' + k + '<br>' + html + '</td></tr>');
                stepCS++;
            })
            if((stepCheck == 1)&&(stepCS == 1)){
                var noApprovHhtml = that.append_htm({css:"success", width:"100%", word:"免审"});
                $(".js_checkJson").append('<tr><td width="30%">审批结果</td><td style="width:70%; padding-top:10px;">' + noApprovHhtml + '</td></tr>');
            }
           
            //渲染执行组信息
            var exeGroup = '';
            $.each(result.data.exeJson.list, function(k, v) {
                var html;
                switch(v.status)
                {
                    case 0:
                        html = that.append_htm({css:"striped", width:"50%", word:"等待执行提案"});
                        break;
                    case 1:
                        html = that.append_htm({css:"warning", width:"50%", word:"等待领取提案"});
                        $(".js_receive").attr("exeGroup",k);
                        break;
                    case 2:
                        html = that.append_htm({css:"success", width:"50%", word:"正在执行提案"});
                        $(".js_transfer").attr("exeGroup",k);
                        $(".js_exe").attr("exeGroup",k);
                        break;
                    case 3:
                        html = that.append_htm({css:"success", width:"100%", word:"完成执行提案"});
                        break;
                    case 4:
                        html = that.append_htm({css:"warning", width:"50%", word:"等待分配提案"});
                        $(".js_receive").attr("exeGroup",k);
                        break;
                    case 5:
                        html = that.append_htm({css:"striped", width:"50%", word:"等待指派提案"});
                        $(".js_appoint").attr("exeGroup",k);
                        break;
                    case 9:
                        html = that.append_htm({css:"end", width:"100%", word:"已终止"});
                        $(".js_appoint").attr("exeGroup",k);
                        break;
                    default:
                        html = that.append_htm({css:"warning", width:"100%", word:"等待系统分配"});
                }
                exeGroup = k;
                $(".js_exeJson").append('<tr><td width="30%">' + v.name + '执行结果</td><td width="70%">' + html + '</td></tr>');
            })
             //最后执行组标志
            if(result.data.exeJson.list[exeGroup].status == 2){
                $(".js_exe").data("lastGroupFlag",true);
            }else{
                $(".js_exe").data("lastGroupFlag",false);

            }
            //渲染提案详细信息
            $.each(result.data.infoJson, function(k, v) {
                if(k == "t_rich_text"){
                    if(v.value){
                        $(".js_details").append('<tr><td>'+v.label+'</td><td name="'+k+'"> <a href="'+v.value+'" class="btn btn-primary btn-sm" target="_blank">点击查看</a></td></tr>');
                    }else{
                        $(".js_details").append('<tr><td>'+v.label+'</td><td name="'+k+'"> <a href="'+v.value+'" disabled class="btn btn-primary btn-sm" target="_blank">点击查看</a></td></tr>');
                    }
                }else if(k == "t_daily_content"){
                    $(".js_details").append('<tr><td>' + v.label + '</td><td name="'+k+'">' + v.value+ '</td></tr>');
                }else{
                    if(!!v.value){
                        if(v.value.indexOf("uploadfile") != -1){//附件
                            if(v.value.indexOf("href")!= -1){
                                var imgarr = v.value.split(',')
                                var imghtml = '';
                                $.each(imgarr, function(i, j) {
                                    imghtml += '<a href="'+j+'" class="btn btn-primary btn-sm" target="_blank">点击查看附件'+(i+1)+'</a>'
                                })
                                $(".js_details").append('<tr><td>' + v.label + '</td><td name="'+k+'">'+v.value+'</td></tr>');
                            }else{
                                var imgarr = v.value.split(',')
                                var imghtml = '';
                                $.each(imgarr, function(i, j) {
                                    imghtml += '<a href="'+j+'" class="btn btn-primary btn-sm" target="_blank">点击查看附件'+(i+1)+'</a>'
                                })
                                $(".js_details").append('<tr><td>' + v.label + '</td><td name="'+k+'">'+imghtml+'</td></tr>');
                                
                            }
                        }else if(v.value.indexOf("data:image") != -1) {//base64图片
                            $(".js_details").append('<tr><td>' + v.label + '</td><td name="'+k+'">'+v.value+'</td></tr>');
                        }else{
                            $(".js_details").append('<tr><td>' + v.label + '</td><td name="'+k+'">' + v.value.replace(/\n/g, "<br>") + '</td></tr>');
                        }
                    }else {
                        $(".js_details").append('<tr><td>' + v.label + '</td><td name="'+k+'">' + v.value + '</td></tr>');
                    }
                }
            })
        }
    },
    render_log: function() {
        $.ajax({
            url: "/api/issue_server/getServerNode",
            type: "GET",
            dataType: "json",
            data: {
                id: $.getUrlParam("id")
            },
            cache:false,
            success: function(result) {
                if (result.status) {
                    $.each(result.data.reverse(), function(k, v) {
                        var name = v.name? '('+v.name+')' : '';
                        if(v.agent){
                            $(".js_log").append('<tr><td>' + v.agent +name+'('+v.user+ '代)</td><td>' + v.role + '</td><td>' + v.action + '</td><td>' + v.remarks + '</td><td>' + v.way + '</td><td>' + v.time + '</td></tr>')
                        }else {
                            $(".js_log").append('<tr><td>' + v.user +name+ '</td><td>' + v.role + '</td><td>' + v.action + '</td><td>' + v.remarks + '</td><td>' + v.way + '</td><td>' + v.time + '</td></tr>')
                        }
                    })
                }
            }
        })
    },
    get_data: function(){
        var that = this;
        $.ajax({
            url: "/api/issue_server/getServerInfo?id="+$.getUrlParam("id"),
            type: "GET",
            dataType: "json",
            // data: {
            //     id: $.getUrlParam("id")
            // },
            cache:false,
            success: function(result) {
                that.render_info(result);
                that.getServerChangeInfo();
            },
            error:function(){
                console.log("没有数据");
            }
        });

    },
    getServerChangeInfo: function(){
        $.ajax({
            url: "/api/issue_server/getServerChangeInfo",
            type: "GET",
            dataType: "json",
            data: {
                id: $.getUrlParam("id")
            },
            cache:false,
            success: function(result) {
                if(result.status){
                    $.each(result.data,function(key,value){
                        $("[name="+key+"]").append('<br><span style="text-decoration:line-through;color:#ccc;">'+value+'</span>');
                    })

                }

            }
        });

    },
    getServerRelateInfo: function(){
        var statusCode = {
            '00': 'btn-warning',
            '10': 'btn-primary ',
            '41': 'btn-success',
            '03': 'btn-danger',
            '34': 'btn-default',
            '33': 'btn-gray',
            '99': 'btn-default'
        };
        var titleCode = {
            'parent': '父提案',
            'son': '子提案 ',
            'relate': '相关提案'
        };
        $.ajax({
            url: "/api/issue_server/getServerRelateInfo",
            type: "GET",
            dataType: "json",
            data: {
                id: $.getUrlParam("id")
            },
            cache:false,
            success: function(result) {
                console.log(result.data)
                if(result.status){
                    var data = result.data
                    $.each(data, function(k, v) {
                        if(v.length){
                            var parentHtml = '';
                            if(document.body.clientWidth>1450){
                                $.each(v, function(index,val){
                                    var operatorHtml =''
                                    val.operator ? operatorHtml = '<span style="margin-left:10px" class="label label-info">'+val.operator+'</span>':''
                                    parentHtml+= '<p><a href="/issue/server/detail?id='+val.id+'"><span  class="btn '+statusCode[val.status]+' btn-sm">提案 '+val.id+'</span></a>　'+val.title + operatorHtml+'<span style="margin-left:10px" class="label label-info">'+(!!val.time?val.time:val.msg)+'</span></p>';
                                })
                            }else{
                                 $.each(v, function(index,val){
                                    var operatorHtml =''
                                    val.operator ? operatorHtml = '<span style="margin-right: 10px" class="label label-info">'+val.operator+'</span>':''
                                    parentHtml+= '<div style="position:relative;margin-bottom:20px;"><a style="vertical-align: -10px;" href="/issue/server/detail?id='+val.id+'"><span  class="btn '+statusCode[val.status]+' btn-sm">提案 '+val.id+'</span></a>　'+val.title +'<br/><p style="position: absolute;top:24px;padding-left: 96px">'+ operatorHtml+'<span style="margin-left:0px" class="label label-info">'+(!!val.time?val.time:val.msg)+'</span></p></div>';
                                })

                            }
                            $("#js_logBox").before('<div class="box"><div class="box-inner"><div class="box-header well" data-original-title=""><h2><i class="glyphicon glyphicon-bookmark"></i> '+titleCode[k]+'</h2><div class="box-icon"><a href="#" class="btn btn-minimize btn-round btn-default"><i  class="glyphicon glyphicon-chevron-up"></i></a> <a href="#" class="btn btn-close btn-round btn-default"><i class="glyphicon glyphicon-remove"></i></a></div></div><div class="box-content">'+parentHtml+'<div class="clear"></div></div></div></div>');
                        }
                    })
                }
            }
        });

    },
    showprocessing: function() {
        $("#process").remove()
        var processHtm = [];
        processHtm.push('<div class="modal fade in" id="process" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">');
        processHtm.push('<div class="modal-dialog modal-lg">');
        processHtm.push('<div class="modal-content">');
        processHtm.push('<div class="modal-body process-area">');
        processHtm.push('</div>');
        processHtm.push('');
        processHtm.push('<div class="modal-footer">');
        processHtm.push('<a href="#" class="btn btn-default js_cancelExe" data-dismiss="modal" >关闭</a>');
        processHtm.push('</div>');
        processHtm.push('</div>');
        processHtm.push('</div>');
        processHtm.push('</div>');

        $('body').append(processHtm.join(''))
        $("#process").modal('show');

        var processData = [
            {
                type:'parent',
                step:2,
                data: [{
                    title: "创建",
                    content: "创建/创建创建创建创建创建",
                    son: [
                        {
                            step:4,
                            data:[
                                {
                                    title: "创建",
                                    content: "创建/创建创建创建创建创建"
                                }, {
                                    title: "审核",
                                    content: "乐捐平台工作人员审核项目"
                                }, {
                                    title: "执行组1",
                                    content: "执行组1执行组1执行组1执行组1执行组1"
                                }, {
                                    title: "执行组2",
                                    content: "执行组2执行组2执行组2执行组2执行组2"
                                }, {
                                    title: "结束",
                                    content: "结束结束结束结束结束结束"
                                }
                            ]
                        },
                    ],

                }, {
                    title: "审核",
                    content: "乐捐平台工作人员审核项目",
                    son: [
                        {
                            step:4,
                            data:[
                                {
                                    title: "创建",
                                    content: "创建/创建创建创建创建创建"
                                }, {
                                    title: "审核",
                                    content: "乐捐平台工作人员审核项目"
                                }, {
                                    title: "执行组1",
                                    content: "执行组1执行组1执行组1执行组1执行组1"
                                }, {
                                    title: "执行组2",
                                    content: "执行组2执行组2执行组2执行组2执行组2"
                                }, {
                                    title: "结束",
                                    content: "结束结束结束结束结束结束"
                                }
                            ]
                        },
                        {
                            step:3,
                            data:[
                                {
                                    title: "创建",
                                    content: "创建/创建创建创建创建创建"
                                }, {
                                    title: "审核",
                                    content: "乐捐平台工作人员审核项目"
                                }, {
                                    title: "执行组1",
                                    content: "执行组1执行组1执行组1执行组1执行组1"
                                }, {
                                    title: "执行组2",
                                    content: "执行组2执行组2执行组2执行组2执行组2"
                                }, {
                                    title: "结束",
                                    content: "结束结束结束结束结束结束"
                                }
                            ]
                        },{
                            step:3,
                            data:[
                                {
                                    title: "创建",
                                    content: "创建/创建创建创建创建创建"
                                }, {
                                    title: "审核",
                                    content: "乐捐平台工作人员审核项目"
                                }, {
                                    title: "执行组1",
                                    content: "执行组1执行组1执行组1执行组1执行组1"
                                }, {
                                    title: "执行组2",
                                    content: "执行组2执行组2执行组2执行组2执行组2"
                                }, {
                                    title: "结束",
                                    content: "结束结束结束结束结束结束"
                                }
                            ]
                        },{
                            step:3,
                            data:[
                                {
                                    title: "创建",
                                    content: "创建/创建创建创建创建创建"
                                }, {
                                    title: "审核",
                                    content: "乐捐平台工作人员审核项目"
                                }, {
                                    title: "执行组1",
                                    content: "执行组1执行组1执行组1执行组1执行组1"
                                }, {
                                    title: "执行组2",
                                    content: "执行组2执行组2执行组2执行组2执行组2"
                                }, {
                                    title: "结束",
                                    content: "结束结束结束结束结束结束"
                                }
                            ]
                        },{
                            step:3,
                            data:[
                                {
                                    title: "创建",
                                    content: "创建/创建创建创建创建创建"
                                }, {
                                    title: "审核",
                                    content: "乐捐平台工作人员审核项目"
                                }, {
                                    title: "执行组1",
                                    content: "执行组1执行组1执行组1执行组1执行组1"
                                }, {
                                    title: "执行组2",
                                    content: "执行组2执行组2执行组2执行组2执行组2"
                                }, {
                                    title: "结束",
                                    content: "结束结束结束结束结束结束"
                                }
                            ]
                        },{
                            step:3,
                            data:[
                                {
                                    title: "创建",
                                    content: "创建/创建创建创建创建创建"
                                }, {
                                    title: "审核",
                                    content: "乐捐平台工作人员审核项目"
                                }, {
                                    title: "执行组1",
                                    content: "执行组1执行组1执行组1执行组1执行组1"
                                }, {
                                    title: "执行组2",
                                    content: "执行组2执行组2执行组2执行组2执行组2"
                                }, {
                                    title: "结束",
                                    content: "结束结束结束结束结束结束"
                                }
                            ]
                        },
                    ],
                   
                }, {
                    title: "执行组1",
                    content: "执行组1执行组1执行组1执行组1执行组1",
                   
                }, {
                    title: "执行组2",
                    content: "执行组2执行组2执行组2执行组2执行组2"
                }, {
                    title: "结束",
                    content: "结束结束结束结束结束结束"
                }],
               
            },{
                type:'parent',
                step:3,
                data: [{
                    title: "创建",
                    content: "创建/创建创建创建创建创建",
                 
                }, {
                    title: "审核",
                    content: "乐捐平台工作人员审核项目",
                   
                }, {
                    title: "执行组1",
                    content: "执行组1执行组1执行组1执行组1执行组1"
                }, {
                    title: "执行组2",
                    content: "执行组2执行组2执行组2执行组2执行组2",
                 
                }, {
                    title: "结束",
                    content: "结束结束结束结束结束结束"
                }],
            },
            {
                type:'parent',
                step:3,
                data: [{
                    title: "创建",
                    content: "创建/创建创建创建创建创建",
                 
                }, {
                    title: "审核",
                    content: "乐捐平台工作人员审核项目",
                   
                }, {
                    title: "执行组1",
                    content: "执行组1执行组1执行组1执行组1执行组1"
                }, {
                    title: "执行组2",
                    content: "执行组2执行组2执行组2执行组2执行组2",
                    son: [
                        {
                            step:4,
                            data:[
                                {
                                    title: "创建",
                                    content: "创建/创建创建创建创建创建"
                                }, {
                                    title: "审核",
                                    content: "乐捐平台工作人员审核项目"
                                }, {
                                    title: "执行组1",
                                    content: "执行组1执行组1执行组1执行组1执行组1"
                                }, {
                                    title: "执行组2",
                                    content: "执行组2执行组2执行组2执行组2执行组2"
                                }, {
                                    title: "结束",
                                    content: "结束结束结束结束结束结束"
                                }
                            ]
                        },
                    ],

                 
                }, {
                    title: "结束",
                    content: "结束结束结束结束结束结束"
                }],
            },
        ]
        var index = 1
        $.each(processData, function(key, val){
            $(".process-area").append('<div class="ystep'+index+' process-item"></div>');
            $(".ystep"+index).loadStep({
                //ystep的外观大小
                //可选值：small,large
                size: "large",
                //ystep配色方案
                //可选值：green,blue
                color: "green",
                //ystep中包含的步骤
                steps: val.data
            });
            $(".ystep"+index).setStep(val.step)
            
            $.each(val.data, function(k,v) {
                if(!!v.son){
                    $.each(v.son, function(i,j){
                        console.log(i)
                        $(".ystep"+(index)).append('<div class="ystepson'+index+k+i+' process-item-son"></div>');
                       
                        $(".ystep"+(index)).append('<p class="ysonline ysonline'+index+k+i+'"></p>');
                        var left =  16+100*k
                        var marginleft =  8+100*k
                        var top =  28
                        var height = 64+35*($('.ystep'+index+' .process-item-son').size()-1)
                        $(".ysonline"+index+k+i).css({left:left+'px',top:top+'px'})
                        $(".ystepson"+index+k+i).css({marginLeft:marginleft+'px'})
                        $(".ysonline"+index+k+i).animate({
                          height:height+'px',
                        },1000);
                        $(".ystepson"+index+k+i).loadStep({
                            size: "small",
                            color: "green",
                            steps: j.data
                        });
                        $(".ystepson"+index+k+i).setStep(j.step)
                        // index++

                    })
                }

            })
            index++

        })


        

    }
}
list.init();