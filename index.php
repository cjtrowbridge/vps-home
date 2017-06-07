<!DOCTYPE html>
<html>

<head>
	<title><?php echo file_get_contents('/etc/hostname'); ?> | VPS-Home</title>
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.4/jquery.min.js"></script>
	<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/js/bootstrap.min.js" integrity="sha512-K1qjQ+NcF2TYO/eI3M6v8EiNYZfA95pQumfvcVrTHtwQVDG+aHRqLi/ETn2uB+1JqwYqVG3LIvdm9lj6imS/pQ==" crossorigin="anonymous"></script>
	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css" integrity="sha512-dTfge/zgoMYpP7QbHy4gWMEGsbsdZeCXz7irItjcC3sPUFtf0kuFbDz/ixG7ArTxmDjLXDmezHubeNikyKGVyQ==" crossorigin="anonymous">
	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap-theme.min.css" integrity="sha384-aUGj/X2zp5rLCbBxumKTCw2Z50WgIr1vs/PFN4praOTvYXWlVyh2UtNUU0KAUhAX" crossorigin="anonymous">
</head>

<body>
<div class="container-fluid">
	<h1><?php echo file_get_contents('/etc/hostname'); ?> (<?php echo $_SERVER['SERVER_ADDR']; ?>)</h1>
	<?php 
	
		if(disk_free_space('/')<(1e+9)){
			echo '<h2 class="warning">LOW DISK SPACE</h2>';
		}
		
	?>
	<hr>
	<div class="col-md-6 col-sm-12 col-xs-12">
		
		<h2>Directory Listing:</h2>
		<?php 

		$path='/var/www';
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
			echo '<div><a href="'.$name.'"><img src="/icons/folder.gif" alt="[DIR]"> '.$name.'</a><br></div>';
		}
		foreach($files as $name => $file){
			echo '<div><a href="'.$name.'"><img src="/icons/unknown.gif" alt="[DIR]"> '.$name.'</a><br></div>';
		}

		?>

		<h2><a href="webs/">Webs</a></h2>
		<pre><?php echo shell_exec('du -sh /var/www/*');?></pre>
		
		<h2><a href="webs/">Top</a></h2>
                <pre><?php passthru('/usr/bin/top -b -n 1'); ?></pre>


	</div>
	<div class="col-md-6 col-sm-12 col-xs-12">
		
		<h2>Uptime</h2>
		<pre><?php echo shell_exec('uptime'); ?></pre>
		<h2>/etc/motd:</h2>
		<pre><?php echo file_get_contents('/etc/motd'); ?></pre>
		<h2>df -h</h2>
		<pre><?php passthru('df -h'); ?></pre>
	</div>
</div>
</body>

</html>
