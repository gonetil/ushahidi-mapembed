<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">
<head>
	<title></title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<?php echo $header_block; ?>
	<?php
	// Action::header_scripts - Additional Inline Scripts from Plugins
	Event::run('ushahidi_action.header_scripts');
	?>
	
	<style type="text/css">
		body{ background: white repeat !important; overflow:auto !important; padding:5px; padding-left:10px;
		}
		.floatbox{padding:5px; padding-left:10px; width: <?php echo $width;?>px; height:<?php echo $height;?>px;overflow:auto !important;}
		div.map{ width: <?php echo ($width - 10);?>px !important; height:<?php echo ($height -30);?>px !important;}
		#mapStatus{width: <?php echo ($width - 10);?>px !important;}
		div.slider-holder{width: <?php echo ($width - 10);?>px !important; padding-left:0 !important;}
	</style>

	
</head>


<body>
