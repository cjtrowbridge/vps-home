<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
if (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] !== 'on'){
  header("Status: 301 Moved Permanently");
  header(sprintf('Location: https://%s%s',$_SERVER['HTTP_HOST'],$_SERVER['REQUEST_URI']));
  exit();
}

$FolderSVG = '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" x="0px" y="0px" viewBox="0 0 100 125" enable-background="new 0 0 100 100" xml:space="preserve"><path d="M100,31.806c0-2.07-1.678-3.75-3.75-3.75h-0.323c-0.348-1.711-1.859-3-3.675-3H41.181l-9.803-11.374l-0.019,0.005  c-0.681-0.696-1.628-1.13-2.679-1.13H3.75c-2.071,0-3.749,1.679-3.749,3.75v67.38L0,83.693c0,2.071,1.678,3.75,3.748,3.75H3.75  h16.873h0.002H37.5h0.001H92.25h0.002c2.072,0,3.75-1.679,3.75-3.75v-0.025L100,31.806z M37.5,84.443H20.623H3.748  c-0.389,0-0.709-0.298-0.745-0.679L6.991,31.96C6.997,31.883,7,31.806,7,31.729c0-0.414,0.335-0.75,0.747-0.75h0.721l87.534,0.076  l0,0h0.248c0.39,0,0.711,0.299,0.747,0.679l-0.995,12.907l0,0l-2.993,38.821C93.003,83.539,93,83.616,93,83.693  c0,0.413-0.337,0.75-0.75,0.75H37.5z"/></svg>';
$FileSVG = '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" x="0px" y="0px" viewBox="0 0 100 100" enable-background="new 0 0 100 100" xml:space="preserve"><path fill="#000000" d="M63.2,5.026V5h-0.026h-3.573H16.25v90h67.5V25.499L63.2,5.026z M63.2,10.108l15.426,15.367H63.2V10.108z   M19.85,91.4V8.6h39.751v20.475h20.55V91.4H19.85z"></path><path fill="#000000" stroke="#231815" stroke-width="3.6" stroke-miterlimit="10" d="M84.033,26.991"></path><path fill="#000000" stroke="#231815" stroke-width="3.6" stroke-miterlimit="10" d="M61.758,4.716"></path></svg>';

?><!DOCTYPE html>
<html>

<head>
	<title><?php echo file_get_contents('/etc/hostname'); ?> | VPS-Home</title>
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.4/jquery.min.js"></script>
	<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/js/bootstrap.min.js" integrity="sha512-K1qjQ+NcF2TYO/eI3M6v8EiNYZfA95pQumfvcVrTHtwQVDG+aHRqLi/ETn2uB+1JqwYqVG3LIvdm9lj6imS/pQ==" crossorigin="anonymous"></script>
	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css" integrity="sha512-dTfge/zgoMYpP7QbHy4gWMEGsbsdZeCXz7irItjcC3sPUFtf0kuFbDz/ixG7ArTxmDjLXDmezHubeNikyKGVyQ==" crossorigin="anonymous">
	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap-theme.min.css" integrity="sha384-aUGj/X2zp5rLCbBxumKTCw2Z50WgIr1vs/PFN4praOTvYXWlVyh2UtNUU0KAUhAX" crossorigin="anonymous">
</head>

<body>
<div class="container">
  <div class="row">
	  <div class="col-xs-12">
		<h1><?php echo basename(__DIR__); ?></h1>
		<?php 
		$path='./';
		$directories=array();
		$files=array();
		if($handle = opendir($path)){
			while(false !== ($entry = readdir($handle))){
				if(is_dir($path.DIRECTORY_SEPARATOR.$entry)){
					if(($entry !== '.')&& ($entry!=='..')){
						$directories[$entry]=$path.DIRECTORY_SEPARATOR.$entry;
					}
				}else{
					$files[$entry]=$path.DIRECTORY_SEPARATOR.$entry;
				}
			}
			closedir($handle);
		}
		asort($directories);
		asort($files);
		foreach($directories as $name => $directory){
			echo '<p><a href="'.$name.'">📁 '.$name.'</a></p>';
		}
		foreach($files as $name => $file){
			echo '<p><a href="'.$name.'">📄 '.$name.'</a></p>';
		}
		?>

		</div>
	</div>
</div>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.1.1/jquery.min.js" integrity="sha384-3ceskX3iaEnIogmQchP8opvBy3Mi7Ce34nWjpBIwVTHfGYWQS9jwHDVRnpKKHJg7" crossorigin="anonymous"></script>
</body>
</html>
