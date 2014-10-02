;(function( $, window, document, undefined ) {
	"use strict";
	$(function(){
		var $cbox = $("#gravatarToggle");
		var toggleAvatar = function(el){
			if ($(el).prop("checked")) {
				$(".avatarChooser").hide();
				$("#gravatarLink").show();
			} else {
				if ($(el).data("gravatarOrig")) {
					$(".avatarChooser img.avatar").attr("src", $(el).data("gravatarOrig"));
				}
				$(".avatarChooser").show();
				$("#gravatarLink").hide();
			}
		};
		if ($cbox) {
			$cbox.on("click", function(){
				toggleAvatar(this);
			});
			toggleAvatar($cbox);
		}
	});
}( jQuery, window, document, undefined ));