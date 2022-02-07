$(function() {

	$(".select").select2();
	//获取系统安装模板信息
	$.ajax({
		url: "/api/IdcOs_General/getOsTmpSelect",
		type: "get",
		dataType: "json",
		contentType: 'application/json',
		success: function(rusult) {
			for (var index in rusult) {
				rusult[index].id = rusult[index].text;
			}
			$('.js_ostmpname').select2({
				data: rusult
			});
		}
	});
	//获取硬件配置信息
	$.ajax({
		url: "/api/IdcOs_General/getHardwareSelect",
		type: "get",
		dataType: "json",
		contentType: 'application/json',
		success: function(rusult) {
			for (var index in rusult) {
				rusult[index].id = rusult[index].text;
			}
			$('.js_hardwarename').select2({
				data: rusult
			});
		}
	});

	// 点击更多搜索条件
	$(".js_more").click(function() {
		$(".search_area .conditions").toggle()
		if ($(".search_area .conditions").is(":visible")) {
			$(".js_more .world").text("收起更多选项");
			$(".search_out .more .icon").addClass("up");
		} else {
			$(".js_more .world").text("展开更多选项");
			$(".search_out .more .icon").removeClass("up");
		}
	})

	//等待安装列表--取消安装功能
	$(".js_deleteDevice").click(function() {
		var devices = [];
		var device = {};
		$("tbody input:checked").each(function(index, element) {
			device['id'] = $(this).parent().parent().find("td").eq(9).html();
			device['sn'] = $(this).parent().parent().find("td").eq(1).find("a").html();
			devices.push(JSON.stringify(device));
		})
		if (devices.length > 0) {
			$.ajax({
				url: "/api/IdcOs_Device/deleteDevice",
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
					if ( !! data.status && data.status == 401) {
						$.confirm(data.msg, function() {
							window.location.href = "/login/logout";
						});
					} else if (data.status) {
						$(".js_result_dialog .tip").html("操作成功");
					} else {
						$(".js_result_dialog .tip").html("操作失败");

					}
				}
			});
		}

	})


	//重新安装
	$(".js_reInstall").click(function() {

		var devices = [];
		var device = {};
		$("tbody input:checked").each(function(index, element) {
			$(this).parent().parent().find("td").each(function(i) {
				switch (i) {
					case 1:
						device['sn'] = $(this).find("span").attr("sn");
						break;
					case 2:
						device['hostname'] = $(this).find("span").attr("hostname");
						break;
					case 3:
						device['ip'] = $(this).text();
						break;
					case 4:
						device['oobip'] = $(this).text();
						break;
					case 5:
						device['ostmpid'] = $(this).text();
						break;
					case 6:
						device['ostmpname'] = $(this).find("span").attr("ostmpname");
						break;
					case 7:
						device['hardwareid'] = $(this).text();
						break;
					case 8:
						device['hardwarename'] = $(this).find("span").attr("hardwarename");
						break;
					case 9:
						device['id'] = $(this).text();
						break;
					case 13:
						device['idc'] = $(this).text();
						break;
					case 14:
						device['idccode'] = $(this).text();
						break;
				}
			});
			devices.push(JSON.stringify(device));
		})
		if (devices.length > 0) {
			$.ajax({
				url: "/api/IdcOs_Device/reInstallDevice",
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
					if ( !! data.status && data.status == 401) {
						$.confirm(data.msg, function() {
							window.location.href = "/login/logout";
						});
					} else if (data.status) {
						$(".js_result_dialog .tip").html("操作成功");
					} else {
						$(".js_result_dialog .tip").html("操作失败");

					}
				}
			});
		}
	})
	// 点击关闭按钮，刷新当前页面
	$(".js_result_dialog button").click(function() {
		window.location.reload();
	})
})