;(function( $, window, document, undefined ) {
	"use strict";
	$(function(){
		var $cbox = $("#gravatar-toggle");
		var toggleAvatar = function(el){
			if ($(el).prop("checked")) {
				$(".avatarChooser").hide();
			} else {
				$(".avatarChooser").show();
				if ($(el).data("gravatar-orig")) {
					$(".avatarChooser img.avatar").attr("src", $(el).data("gravatar-orig"));
				}
			}
		};
		$cbox.on("click", function(){
			toggleAvatar(this);
		});
		toggleAvatar($cbox);
	});
}( jQuery, window, document, undefined ));