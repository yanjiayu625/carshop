$(document).ready(function() {

    //input非空判断
    $(".js_blur").blur(function() {
        $(this).parent().find("span").html("");
        if ($(this).val() == "") {
            $(this).css({
                "border-color": "#f00"
            });
            $(this).parent().find("span").html($(this).attr("fld") + "不能为空");
            flag_new = 0;
        }
    })

    var sn = "";
    var ip = "";
    $(".js_SNblur").blur(function() {
        if ($(this).val() != "") {
            var re_sn = /^[A-Za-z0-9]+$/;
            if (re_sn.test($(this).val())) {
                //判断是否重复
                $.ajax({
                    url: "/api/idcos_general/checkSn",
                    type: "get",
                    data: {
                        sn: $(this).val()
                    },
                    dataType: "json",
                    async: false,
                    contentType: 'application/json',
                    success: function(data) {
                        if (data.status) {
                            var now = new Date();
                            var yy = now.getFullYear();      //年
                            var mm = now.getMonth() + 1;     //月
                            var dd = now.getDate();          //日
                            var hh = now.getHours();         //时
                            var ii = now.getMinutes();       //分
                            var time =yy+""+mm+""+dd+""+hh+""+ii+""
                            sn = $(".js_SNblur").val();
                            $(".js_hostname").text("fromifos-"+sn +time+ "-" + ip);
                            $(".js_SNblur").css({
                                "border-color": "#ccc"
                            });
                        } else {
                            $(".js_SNblur").parent().find("span").html("此SN号已存在，请更换");
                            $(".js_SNblur").css({
                                "border-color": "#f00"
                            });
                            flag_new = 0;
                        }
                    },
                    error: function() {
                        return false;
                    }
                });
            } else {
                $(this).parent().find("span").html($(this).attr("fld") + "无效");
                $(this).css({
                    "border-color": "#f00"
                });
                flag_new = 0;
            }
        }
    })

    $(".js_IPblur").blur(function() {
        $(".js_idcname").text("");
        $(".js_idcname").attr("idcid", "");
        ip = "";
        if ($(this).attr("action") == "edit") {
            sn = $(".js_sn").html();
        }
        var now = new Date();
                            var yy = now.getFullYear();      //年
        var mm = now.getMonth() + 1;     //月
        var dd = now.getDate();          //日
        var hh = now.getHours();         //时
        var ii = now.getMinutes();       //分
        var time =yy+""+mm+""+dd+""+hh+""+ii+""
        $(".js_hostname").text("fromifos-"+sn + time+"-");
        if ($(this).val() != "") {
            //ip合法验证
            var ip_val = $(this).val();
            var re_ip = /^(\d{1,2}|1\d\d|2[0-4]\d|25[0-5])\.(\d{1,2}|1\d\d|2[0-4]\d|25[0-5])\.(\d{1,2}|1\d\d|2[0-4]\d|25[0-5])\.(\d{1,2}|1\d\d|2[0-4]\d|25[0-5])$/;
            if (re_ip.test(ip_val)) {
                var lastnum = $(this).val().substring($(this).val().lastIndexOf('.') + 1);
                var num1 = $(this).val().substring(0, $(this).val().lastIndexOf('.'));
                var lastnum1 = num1.substring(num1.lastIndexOf('.') + 1);

                //通过IP获取IDC信息
                $.ajax({
                    url: "/api/IdcOs_general/checkIp",
                    type: "get",
                    data: {
                        ip: $(".js_IPblur").val()
                    },
                    dataType: "json",
                    async: false,
                    contentType: 'application/json',
                    success: function(data) {
                        if (data.status) {
                            // ip = lastnum1 + "-" + lastnum;
                            // $(".js_hostname").text(sn + "-" + lastnum1 + "-" + lastnum);
                            ip = lastnum + "v" + lastnum1;
                            var mm = now.getMonth() + 1;     //月
                            var dd = now.getDate();          //日
                            var hh = now.getHours();         //时
                            var ii = now.getMinutes();       //分
                            var time =yy+""+mm+""+dd+""+hh+""+ii+""
                            $(".js_hostname").text("fromifos-"+sn + time+"-" + lastnum + "v" + lastnum1);
                            $(".js_IPblur").css({
                                "border-color": "#ccc"
                            });
                            $(".js_idcname").text(JSON.parse(data.message).idcname);
                            $(".js_idcname").attr("idccode", JSON.parse(data.message).idccode);
                        } else {
                            $(".js_IPblur").parent().find("span").html(data.message);
                            $(".js_IPblur").css({
                                "border-color": "#f00"
                            });
                            flag_new = 0;
                        }
                    },
                    error: function() {
                        return false;
                    }
                });
            } else {
                $(".js_IPblur").parent().find("span").html("IP无效");
                $(this).css({
                    "border-color": "#f00"
                });
                flag_new = 0;
            }
        }
    })

    //判断是否选择操作系统

    $(".js_ostmpname,.js_hardwarename").on("change", function(e) {
        $(this).parent().find(".err").html("");
        $(this).parent().find(".select2 .select2-selection--single").css({
            "border-color": "#ccc"
        });
        if (!$(this).val()) {
            $(this).parent().find(".err").html("请选择模板");
            flag_new = 0;
            $(this).parent().find(".select2 .select2-selection--single").css({
                "border-color": "#f00"
            });
        }
    });

    //提交单条录入新设备信息
    var flag_new = 1;
    $(".js_new_submit").click(function() {
        flag_new = 1;
        $(".js_blur").trigger("blur");
        $(".js_SNblur").trigger("blur");
        $(".js_IPblur").trigger("blur");
        $(".js_IPcard").trigger("blur");
        $(".js_MACblur").trigger("blur");
        $(".js_ostmpname").trigger("change");
        $(".js_hardwarename").trigger("change");

        //信息完整，允许提交
        if (flag_new) {

            var device = {};
            device['sn'] = $(".js_SNblur").val();
            device['ip'] = $(".js_IPblur").val();
            device['macaddr'] = $(".js_MACblur").val();
            device['oobip'] = $(".js_IPcard").val();
            device['hostname'] = $(".js_hostname").text();
            device['ostmpname'] = $(".js_ostmpname :selected").text();
            device['ostmpid'] = $(".js_ostmpname :selected").val();
            if (!$(".js_hardwarename :selected").val()) {
                device['hardwarename'] = "";
            } else {
                device['hardwarename'] = $(".js_hardwarename :selected").text();
            }
            device['hardwareid'] = $(".js_hardwarename :selected").val();
            device['idc'] = $(".js_idcname").text();
            device['idccode'] = $(".js_idcname").attr("idccode");
            var devices = [JSON.stringify(device)]

            $.ajax({
                url: "/api/IdcOs_Device/postDevice",
                type: "POST",
                data: {
                    deviceArray: JSON.stringify(devices)
                },
                dataType: "json",
                beforeSend: function() {
                    $(".dialog-overlay").show();
                    $(".dialog-overlay").css({
                        "height": $("body").height()
                    });
                    $(".js_wait_dialog").show();
                    var scrollTop = document.body.scrollTop === 0 ? document.documentElement.scrollTop : document.body.scrollTop;
                    $(".js_wait_dialog").css({
                        "left": ($(window).width() - $(".js_wait_dialog").width()) / 2
                    });
                    $(".js_wait_dialog").css({
                        "top": ($(window).height() - $(".js_wait_dialog").height()) / 2 + scrollTop
                    });

                },
                success: function(data) {
                    if(!!data.status && data.status == 401) {
                        $.confirm(data.msg, function() {
                          window.location.href = "/login/logout";
                        });
                    } else if (!!data.status) {
                        //提交等待结果&&提交结果
                        $(".js_wait_dialog").hide();
                        $(".js_result_dialog").show();
                        var scrollTop = document.body.scrollTop === 0 ? document.documentElement.scrollTop : document.body.scrollTop;
                        $(".js_result_dialog").css({
                            "left": ($(window).width() - $(".js_result_dialog").width()) / 2
                        });
                        $(".js_result_dialog").css({
                            "top": ($(window).height() - $(".js_result_dialog").height()) / 2 + scrollTop
                        });

                    }
                }
            });

        }
    })
    // 成功提交数据后点击关闭按钮，刷新当前页面
    $(".js_result_dialog button").click(function() {
        window.location.reload();
    })

    // MAC合法性验证
    $(".js_MACblur").blur(function() {
        $(".js_MACblur").parent().find("span").html("");
        $(this).css({
            "border-color": "#ccc"
        });
        if ($(this).val() != "") {
            var mac_valArr = $(this).val().split("#");
            var re_mac = /^[A-Fa-f0-9]{2}(:[A-Fa-f0-9]{2}){5}$/;
            $.each(mac_valArr, function(key,val) {
                if (!re_mac.test(val)) {
                    $(".js_MACblur").parent().find("span").html("MAC无效,请重新输入");
                    $(this).css({
                        "border-color": "#f00"
                    });
                    flag_new = 0;
                    return false
                }
            })

        }else{
            $(this).css({
                "border-color": "#f00"
            });
            $(".js_MACblur").parent().find("span").html("MAC不能为空.");
            flag_new = 0;

        }
    })
    // 管理卡Ip合法性验证
    $(".js_IPcard").blur(function() {
        $(".js_IPcard").parent().find("span").html("");
        $(this).css({
            "border-color": "#ccc"
        });
        if ($(this).val() != "") {
            var ip_val = $(this).val();
            var re_ip = /^(\d{1,2}|1\d\d|2[0-4]\d|25[0-5])\.(\d{1,2}|1\d\d|2[0-4]\d|25[0-5])\.(\d{1,2}|1\d\d|2[0-4]\d|25[0-5])\.(\d{1,2}|1\d\d|2[0-4]\d|25[0-5])$/;
            if (!re_ip.test(ip_val)) {
                $(".js_IPcard").parent().find("span").html("IP无效,请重新输入");
                $(this).css({
                    "border-color": "#f00"
                });
                flag_new = 0;
            }
        }
    })

    //修改设备信息提交
    $(".js_edit_submit").click(function() {
        flag_new = 1;
        $(".js_blur").trigger("blur");
        $(".js_IPblur").trigger("blur");
        $(".js_IPcard").trigger("blur");
        $(".js_ostmpname").trigger("change");

        //信息完整，允许提交
        if (flag_new) {

            var device = {};
            device['id'] = location.search.substr(4);
            device['sn'] = sn;
            device['ip'] = $(".js_ip").val();
            device['macaddr'] = $(".js_mac").text();
            device['oobip'] = $(".js_oobip").val();
            device['hostname'] = $(".js_hostname").text();
            device['ostmpname'] = $(".js_ostmpname :selected").text();
            device['ostmpid'] = $(".js_ostmpname :selected").val();
            if (!$(".js_hardwarename :selected").val()) {
                device['hardwarename'] = "";
            } else {
                device['hardwarename'] = $(".js_hardwarename :selected").text();
            }
            device['hardwareid'] = $(".js_hardwarename :selected").val();
            device['idc'] = $(".js_idcname").text();
            device['idccode'] = $(".js_idcname").attr("idccode");
            device['user'] = $(".js_user").val();
            device['createtime'] = $(".js_createtime").val();
            var devices = [JSON.stringify(device)]

            $.ajax({
                url: "/api/IdcOs_Device/updateDeviceInfo",
                type: "POST",
                data: {
                    deviceArray: JSON.stringify(devices)
                },
                dataType: "json",
                beforeSend: function() {
                    $(".dialog-overlay").show();
                    $(".dialog-overlay").css({
                        "height": $("body").height()
                    });
                    $(".js_wait_dialog").show();
                    var scrollTop = document.body.scrollTop === 0 ? document.documentElement.scrollTop : document.body.scrollTop;
                    $(".js_wait_dialog").css({
                        "left": ($(window).width() - $(".js_wait_dialog").width()) / 2
                    });
                    $(".js_wait_dialog").css({
                        "top": ($(window).height() - $(".js_wait_dialog").height()) / 2 + scrollTop
                    });

                },
                success: function(data) {
                    if(!!data.status && data.status == 401) {
                        $.confirm(data.msg, function() {
                          window.location.href = "/login/logout";
                        });
                    } else if (!!data.status) {
                        //提交等待结果&&提交结果
                        $(".js_wait_dialog").hide();
                        $(".js_result_dialog_edit").show();
                        var scrollTop = document.body.scrollTop === 0 ? document.documentElement.scrollTop : document.body.scrollTop;
                        $(".js_result_dialog_edit").css({
                            "left": ($(window).width() - $(".js_result_dialog_edit").width()) / 2
                        });
                        $(".js_result_dialog_edit").css({
                            "top": ($(window).height() - $(".js_result_dialog_edit").height()) / 2 + scrollTop
                        });

                    }
                }
            });

        }
    })

    // 成功修改数据后点击关闭按钮，跳转到等待安装页面
    $(".js_result_dialog_edit button").click(function() {
        window.location.href = "/idcos/device/waiting";
    })
});