(function($) {
  //过滤空格
  $.ignoreSpaces = function (string) {
      var temp = "";
      string = '' + string;
      splitstring = string.split(" ");
      for (i = 0; i < splitstring.length; i++)
          temp += splitstring[i];
      return temp;
  }
  //数组去重
  $.uniqueitem = function(arr) {
      arr.sort();
      var re = [arr[0]];
      for (var i = 1; i < arr.length; i++) {
          if (arr[i] !== re[re.length - 1]) {
              re.push(arr[i]);
          }
      }
      return re;
  }
  //数组去空
  $.delnull = function(arr) {
      for(var i = 0 ;i<arr.length;i++){
           if(arr[i] == "" || typeof(arr[i]) == "undefined")
           {
            arr.splice(i,1);
            i= i-1;
           }
      }
      return arr;
  }
  
  $.getUrlParam = function(name) {
    var reg = new RegExp("(^|&)" + name + "=([^&]*)(&|$)");
    var r = window.location.search.substr(1).match(reg);
    // if (r != null) return unescape(r[2]);
    if (r != null) return r[2];
    return null;
  };

  $.log = function() {
    for (var i in arguments) {
      console.log(arguments[i]);
    }
  };

  $.splitLast = function(str, exp) {
    var s = str.split(exp);
    return s[s.length - 1];
  };

  $.alert = function(message, callback) {
    $('#alertModal').remove();
    var htm = ' <div class="modal fade in" id="alertModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">';
    htm += ' <div class="modal-dialog modal-sm">';
    htm += '  <div class="modal-content">';
    htm += '    <div class="modal-body">';
    htm += message;
    htm += '    </div>';
    htm += '    <div class="modal-footer">';
    htm += '      <a href="#" class="btn btn-primary" data-dismiss="modal" ' + (typeof callback == 'function' ? ('id="callback"') : '') + '>确定</a>';
    htm += '    </div>';
    htm += '  </div>';
    htm += ' </div>';
    htm += '</div>';

    $('body').append(htm);
    $('#alertModal').modal('show');
    $("body").delegate("#callback", "click", callback);
  };
  //confirm

  $.confirm = function(message, callback) {
    $('#alertModal').remove();
    var htm = ' <div class="modal fade in" id="alertModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">';
    htm += ' <div class="modal-dialog modal-sm">';
    htm += '  <div class="modal-content">';
    htm += '    <div class="modal-body">';
    htm += message;
    htm += '    </div>';
    htm += '    <div class="modal-footer">';
    htm += '      <a href="#" class="btn btn-default" data-dismiss="modal" >取消</a>';
    htm += '      <a href="#" class="btn btn-primary" data-dismiss="modal" ' + (typeof callback == 'function' ? ('id="callback"') : '') + '>确定</a>';
    htm += '    </div>';
    htm += '  </div>';
    htm += ' </div>';
    htm += '</div>';

    $('body').append(htm);
    $('#alertModal').modal('show');
    $("body").delegate("#callback", "click", callback);
  };

  $.confirmlg = function(message, callback) {
    $('#alertModal').remove();
    var htm = ' <div class="modal fade in" id="alertModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">';
    htm += ' <div class="modal-dialog modal-lg" style="margin: 30% auto">';
    htm += '  <div class="modal-content">';
    htm += '    <div class="modal-body">';
    htm += message;
    htm += '    </div>';
    htm += '    <div class="modal-footer">';
    htm += '      <a href="#" class="btn btn-default" data-dismiss="modal" >取消</a>';
    htm += '      <a href="#" class="btn btn-primary" data-dismiss="modal" ' + (typeof callback == 'function' ? ('id="callbacklg"') : '') + '>确定</a>';
    htm += '    </div>';
    htm += '  </div>';
    htm += ' </div>';
    htm += '</div>';

    $('body').append(htm);
    $('#alertModal').modal('show');
    $("body").delegate("#callback", "click", callback);
  };
  /*
  ajaxConfig = {
    success: function(result) {
      console.log(result);
    }
  }
  */
  $.ifosAjax = function(ajaxConfig) {
    var suc = ajaxConfig.success;
    $.ajax($.extend(ajaxConfig, {
      success: function(result) {
        if (result.status == 401) {
          $.confirm(result.msg, function() {
            window.location.href = "/login/logout";
          });
          return;
        } else if (result.status == 403) {
          $.confirm(result.msg, function() {
            window.location.href = "/login/logout";
          });
          return;
        } else {
          suc(result);
        }
      }
    }));
  };
})(jQuery);