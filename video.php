<?php
$title=isset($_REQUEST['title'])?$_REQUEST['title']:'';
$video=isset($_REQUEST['video'])?$_REQUEST['video']:'';
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=2.0, user-scalable=0,minimum-scale=0.5">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black">
    <title><?php echo $title ?></title>
    <link rel="shortcut icon" href="/favicon.ico">
    	  <meta name="name" itemprop="name" content="<?php echo $title ?>">
</head>
<body>
	<video src="<?php echo $video ?>" controls ></video>
</body>
</html>