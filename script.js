bds = {};

bds.gup = function (name) {
    name = name.replace(/[\[]/, "\\\[").replace(/[\]]/, "\\\]");
    var regexS = "[\\?&]" + name + "=([^&#]*)";
    var regex = new RegExp(regexS);
    var results = regex.exec(window.location.href);
    if (results == null)
        return "";
    else
        return results[1];
};

jQuery(document).ready(function() {
	var ids = ['description', 'cause', 'content', 'task', 'reason', 'opinion'];

	for (var i = 0; i < ids.length; i++) {
		var textarea = jQuery("#" + ids[i]);
		if (textarea.length > 0) {
			textarea.before('<div id="toolbar'+ids[i]+'"></div>');
			if (textarea.parents("form").find("input[name=id]").length === 0) {
				textarea.before('<input type="hidden" name="id" value="'+bds.gup('id')+'" />');
			}
			initToolbar('toolbar'+ids[i], ids[i], toolbar);
		}
	}

	var $conf = jQuery("#bez_removal_confirm");
	$conf.find(".no").click(function(e) {
		e.preventDefault();
		$conf.hide();
	});

	//delete_button 
	$delete_buts = jQuery("#bez_comments, #bez_causes").find(".bez_delete_button");
	jQuery("body").bind("click", function (e) {
		var $target = jQuery(e.target);
		if (!$target.is($delete_buts))
			$conf.hide();
	});

	$delete_buts.each(function() {
		jQuery(this).click(function(e) {
			e.preventDefault();
			var $click = jQuery(this);
			var off = $click.offset();
			$conf.appendTo("body");
			$conf.css({
					'position': 'absolute',
					'left':		off.left-$conf.width()+$click.width(),
					'top':	off.top+2,
				});
			$conf.find("input").unbind("click");
			$conf.find("input").bind("click", function(e) {
				e.preventDefault();
				window.location = $click.attr("href");
			});
			$conf.show();
		});
	});

	//show/hide opinion
	/*var $form = jQuery("#bez_issue_report.update"); 
	if ($form.length > 0) {
		var $coordinator = $form.find("select[name=coordinator]");
		var $opinion_row = $form.find("textarea[name=opinion]").parents("div[class=row]");
		var $status_row = $form.find("label[for=state]").parents("div[class=row]");
		var $state = $form.find("input[name=state]");

		//state.length == 0 -> nie możemy zmieniać statusu
		if ($state.length == 0)
			$opinion_row.hide();

		var cval = $coordinator.val();
		if (cval == '-proposal' || cval == '-rejected' ) {
			$status_row.hide();
			$opinion_row.hide();
		}
		

		$coordinator.change(function () {
			var cval = $coordinator.val();
			if (cval == '-proposal' || cval == '-rejected') {
				$status_row.hide();
				$opinion_row.hide();
			} else {
				$status_row.show();
				if ($form.find("input[name=state]:checked").val() == "1")
					$opinion_row.show();
			}
		});
		

		if ($form.find("input[name=state]:checked").val() == "0")
			$opinion_row.hide();

		$state.change(function() {
			$this = jQuery(this);
			if ($this.val() == "0")
				$opinion_row.hide();
			else
				$opinion_row.show();
		});
	}*/

	//show/hide reason
	/*$reason_row = jQuery(".bez_task_form textarea[name=reason]").parents("div[class=row]");
	
	if ($reason_row.length > 0) {
		$select = jQuery(".bez_task_form select[name=state]");
		$action = jQuery(".bez_task_form input[name=action]");

		if ($select.val() == "0" || ($select.val() == "1" && $action.val() != "2"))
			$reason_row.hide();
		
		var $label = jQuery(".bez_task_form label[for=reason]");
		var text = $label.text();
		console.log(text);
		var res = text.match(/[a-z ]+/gi);

		if ($select.val() == "1")
			$label.text(res[1]+":");
		else
			$label.text(res[0]+":");
		
		$select.change(function() {
			if (jQuery(this).val() == "0" || (jQuery(this).val() == "1" && $action.val() != "2"))
				$reason_row.hide();
			else
				$reason_row.show();

			if (jQuery(this).val() == "1" && $action.val() == "2")
				$label.text(res[1]+":");
			else
				$label.text(res[0]+":");
		});
	}

	var show = function() {
			jQuery(this).siblings(".bds_block_content").show();
			jQuery(this).find(".toggle").css("background-image", "url(lib/plugins/bez/images/expanded.png)");
		};
	var hide = function() {
			jQuery(this).siblings(".bds_block_content").hide();
			jQuery(this).find(".toggle").css("background-image", "url(lib/plugins/bez/images/collapsed.png)");
		};

	/*jQuery(".bds_block")
		.each(function() {
			$h1 = jQuery(this).find("h1").html(
				function(index, oldhtml) {
					return '<span class="toggle">'+oldhtml+'</span>';
				});

			$h1.find(".toggle").css(
				{
					'background': 'url("lib/plugins/bez/images/collapsed.png") no-repeat scroll 4px 50% rgba(0, 0, 0, 0)',
					'border': 'medium none',
					'border-radius': '0.3em',
					'box-shadow': '0.1em 0.1em 0.3em 0 #BBBBBB',
					'color': '#222222',
					'padding': '0.3em 0.5em 0.3em 20px',
					'text-shadow': '0.1em 0.1em #FCFCFC',
					'cursor': 'pointer'
				});


			var hash = window.location.hash.substring(1);
			if (hash.indexOf("k") === 0) {
				var showed = "bez_comments";
			} else if (hash.indexOf("p") === 0) {
				var showed = "bez_causes";
			} else if (hash.indexOf("z") === 0) {
				var showed = "bez_tasks";
			} else if (hash === "bds_change_issue") {
				var showed = "bds_change_issue";
			}

			if (jQuery(this).attr("id") === showed) {
				jQuery(this).find(".toggle").css("background-image", "url(lib/plugins/bez/images/expanded.png)");
				jQuery(this).find("h1").toggle(hide, show);
			} else {
				jQuery(this).find(".bds_block_content").hide();
				jQuery(this).find("h1").toggle(show, hide);
			}
		});
	jQuery(".bds_block .history_anchor").click(function() {
		show.call(jQuery("#bds_history h1")[0]);
	});*/

	//entities sort
	jQuery("#entities_form input[type=button]").click(function() {
		var textarea = jQuery(this).parents("form").find("textarea");
		var lines = jQuery.trim(textarea.val()).split("\n");
		lines.sort();
		textarea.val(lines.join("\n"));
	});

	/*Zmiana kolorów podczas zmiany priorytetów*/
	var $issue_rep = jQuery("#bez_issue_report");
	if ($issue_rep.length > 0) {
		var colors = {'0': '#B0D2B6', '1':  '#dd9', '2': '#F0AFAD'}; 
		var bgcolors = {'0': '#eeF6F0', '1':  '#ffd', '2': '#F8e8E8'}; 
		var $form = $issue_rep.find(".bds_form");
		var $prior = $issue_rep.find(".priorities");

		$prior.find("input").hide();
		$prior.find("label").css({
				'margin-right': '5px',
				'padding' : '5px',
				'background-color' : '#F7F7F0',
				'border' : '2px solid',
				'border-bottom' : '0',
				'border-top-left-radius' : '5px',
				'border-top-right-radius' : '5px',
				'position' : 'relative',
				'top' : '-1px'
		});

		$form.css({
			'border': '2px solid',
			'border-top-left-radius': '0',
			'position' : 'relative',
			'z-index': '100'
		});


		$prior.find("label").each(function() {
			var $this = jQuery(this);
			var ind = $this.index();
			$this.css('border-color', colors[ind]);
			$this.css('background-color', bgcolors[ind]);
		});

		var change_color = function() {
			var color = $prior.find("input:checked").val();
			$form.css('border-color', colors[color]);
			$form.css('background-color', bgcolors[color]);
			/*ustaw*/
			$prior.find("label").css('z-index', '0');
			$prior.find("input:checked").parents("label").css('z-index', '1000');
		};
		change_color();

		$prior.change(function() {
			change_color();
		});
	}


	/*ukrywanie zakmniętych zadań i formularza dodawania nowych zadań*/
	/*var $tasks = jQuery("#bez_tasks .bds_block_content");
	if ($tasks.length > 0) {
		var hidden = 0;
		$tasks.find(".task").each(function () {
			var $this = jQuery(this);
			if ( ! $this.hasClass("opened")) {
				$this.hide();
				hidden++;
			}
		});
		if (hidden > 0) {
			$button = $tasks.find(".show_tasks_hidden");
			console.log($button);
			$button.show();
			$button.click(function() {
				$tasks.find(".task").show();
				jQuery(this).hide();
			});
		}
		//$form = $tasks.find("form");
	}
	jQuery(".bez_task_form").each(function () {
		$this = jQuery(this);
		if ( ! $this.hasClass('update')) {
			$this.hide();
			$add_task = $this.parent().find(".add_task");
			$add_task.show();
			$add_task.click(function(e) {
				$add_task_btn = jQuery(this);
				var form = $add_task_btn.parent().find("form");
				form.show();
				$add_task_btn.hide();
				e.preventDefault();
			});
		}
	});*/
});
