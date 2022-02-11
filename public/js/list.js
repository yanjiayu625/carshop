function classTag(showContent,selfObj,k){
	
	var kk="#"+showContent;
	var show = $(kk).css("display");
	if(show == "none"){
		
		$(kk).show();
		$(kk).siblings(".dropdown_box").hide();
		$(".dropdown").show();
		$(".nav-item").removeClass("curr");
		$(selfObj).addClass("curr");
	}else{
		$(".nav-item").removeClass("curr");
		$(kk).hide();
		$(".dropdown").hide();
	}
	
}

$(function(){
	
    $(".dropdown_list li a").click(function(){
		var xx = $(this).parents(".dropdown_box").attr("id");
		var ss = $(".sort_selected").css("display");
		if(ss == "none"){
			$(".sort_selected").show();
			$("#"+xx+"_s").text($(this).text());
			$("#"+xx+"_s").parents(".s_selected_cont").show();
			}
		else{
			$("#"+xx+"_s").text($(this).text());
			$("#"+xx+"_s").parents(".s_selected_cont").show();
			};
		$(".dropdown").hide();
		$(".nav-item").removeClass("curr");
	});
	
	$(".s_selected_cont").click(function(){
	  var u=0;
	   var sscLength = $(this).parent().children(".s_selected_cont").length;
	   for(var i=0 ; i<sscLength; i++){
		    
			var dd = $(".s_selected_cont").eq(i).css("display");
		   if(dd == "block"){
			   u+=1;
			 }
		  
		 }
		if(u == 1){
			$(this).hide();
			$(".sort_selected").hide();
			}
		else{
			$(this).hide();
			}
		
		});	
	
	})