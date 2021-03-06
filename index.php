<?php

$StartTime=microtime(true);

error_reporting(E_ALL);
ini_set('display_errors', '1');

/*
if (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] !== 'on'){
  header("Status: 301 Moved Permanently");
  header(sprintf('Location: https://%s%s',$_SERVER['HTTP_HOST'],$_SERVER['REQUEST_URI']));
  exit();
}
*/

function FetchURL($URL, $Method = 'GET', $Arguments = false,$Authorization = false,$UserAgent = 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13',$OtherHeaders = false,$ForceNoCache = true){
  
  if($URL==''){
    return false;
  }
  
  //Make sure method is uppercase
  $Method=strtoupper($Method);
  
  //Initialize Arguments array if null
  if($Arguments==false){
    $Arguments=array();
  }
  if($ForceNoCache){
    $Arguments['cache']=md5(uniqid());
  }
  //Set up cURL  
  $cURL = curl_init();
  curl_setopt($cURL,CURLOPT_URL, $URL);
  
  //Maybe add arguments into POSTFIELDS
  if($Method=='POST'){
    curl_setopt($cURL,CURLOPT_POST, count($Arguments));
    $URLArguments = http_build_query($Arguments);
    curl_setopt($cURL,CURLOPT_POSTFIELDS, $URLArguments);
  }
  
  //Maybe pass authorization
  if($Authorization){
    curl_setopt($cURL,CURLOPT_HTTPHEADER, array(
      'Authorization: Bearer '.$Token
    ));
  }
  
  if($OtherHeaders){
    curl_setopt($cURL,CURLOPT_HTTPHEADER, $OtherHeaders);
  }
  
  if($Method=='PUT'){
    curl_setopt($cURL,CURLOPT_RETURNTRANSFER, false);
  }else{
    curl_setopt($cURL,CURLOPT_RETURNTRANSFER, true);
  }
  
  curl_setopt($cURL,CURLOPT_USERAGENT,$UserAgent);
  
  
  //Run cURL and close it
  $Data = curl_exec($cURL);
  curl_close($cURL);
  
  //Note: This function returns the data as it is received; it is not parsed.
  
  return $Data;
}

function gitGlobalHash(){
  $Hash = FetchURL('https://api.github.com/repos/cjtrowbridge/vps-home/git/refs/heads/master');
  $Hash = json_decode($Hash,true);
  if($Hash==false){return false;}
  if(!(isset($Hash['object']))){return false;}
  if(!(isset($Hash['object']['sha']))){return false;}
  return trim($Hash['object']['sha']);
}

function UpdateNow(){
	$GlobalHash = gitGlobalHash();
	echo 'Updating... ';
	//echo exec("wget https://raw.githubusercontent.com/cjtrowbridge/vps-home/master/index.php -O index.php");
	$New = file_get_contents('https://raw.githubusercontent.com/cjtrowbridge/vps-home/master/index.php?'.uniqid());
	if($New==false){
		//echo '<p>Unable to fetch update! Check connection?</p>';
	}else{
		//echo '<p>Fetched Update. Saving...</p>';
	}

	$New = '<?php $CurrentHash = "'.$GlobalHash.'"; ?>'.$New;
	$New = str_replace('?><?php',PHP_EOL,$New);
	$Save = file_put_contents('index.php',$New);

	if($Save==false){
		echo 'Update Failed.';
	}else{
		echo 'Done! (Refresh to see changes.)';
	}
	exit;
}
if(isset($_GET['fetch'])){
	switch($_GET['fetch']){
		case 'apache_error_log':
			die(shell_exec('tail /var/log/apache2/error.log'));
		case 'uptime':
			die(shell_exec('uptime'));
		case 'motd':
			die(file_get_contents('/etc/motd'));
		case 'df':
			passthru('df -h');
			exit;
		case 'top':
			 passthru('/usr/bin/top -b -n 1');
			exit;
		case 'update-vps-home':
			$GlobalHash = gitGlobalHash();
			if(
				(!(isset($CurrentHash)))||
				(!($GlobalHash==$CurrentHash))
			){
				UpdateNow();
			}
			exit;
		case 'backups':
			if($handle = opendir('/var/www/backups')){
				while (false !== ($dir = readdir($handle))){
					$Path = '/var/www/backups/'.$dir;
					if(
						is_dir($Path) &&
						(!(
							$dir=='.'||
							$dir=='..'
						))
					  ){
						echo $dir.PHP_EOL;
						echo shell_exec('du -sh /var/www/backups/'.$dir.'/*').PHP_EOL;
					}
				}
			}
			exit;
		case 'free-space-error':
			if(disk_free_space('/')<(1e+9)){
				echo '<h2 class="warning">LOW DISK SPACE</h2>';
			}
			exit;
		case 'webs':
			$Lines = shell_exec('du -sh /var/www/webs/*');
			  $Lines = explode(PHP_EOL,$Lines);
			  foreach($Lines as $Line){
				$Link = stristr($Line,'/var/www/webs/');
				$Link = substr($Link,14);
				echo '<div>';
				$IP = gethostbyname($Link);
				if(
					$IP==$_SERVER['SERVER_ADDR']||
					$IP=='127.0.1.1'||
					$IP=='127.0.0.1'
				){$Local = true;}else{$Local = false;}
				if(!($Local)){echo '<strike>';}
				if(!($Local)){echo '<span title="'.$Link.' resolves to '.$IP.'.'.PHP_EOL.'This does not match local IP of '.$_SERVER['SERVER_ADDR'].'">';}
				echo '<a href="//'.$Link.'" target="_blank">'.$Line.'</a></div>'.PHP_EOL;
				if(!($Local)){echo '</span></strike>';}
			  }
			exit;
		case 'large_files':
			$LocalPrefix = '/var/www/webs/';
			ListFilesLargerThan(100, $LocalPrefix);
			ListFilesLargerThan(10, $LocalPrefix);
			//ListFilesLargerThan(1, $LocalPrefix);
			exit;
		case 'dirs':
			die(shell_exec('du -sh /var/www/*'));
	}
	die('Unknown Error at Path: /?fetch='.$_GET['fetch']);
}

function ListFilesLargerThan($Megabytes, $LocalPrefix){
	if(intval($Megabytes==0)){die('Invalid Size');}
	if(!(file_exists($LocalPrefix))){die('Invalid LocalPrefix');}
	$Command = 'find "'.$LocalPrefix.'" -type f -size +'.$Megabytes.'M';
	echo $Command.PHP_EOL;
	$Files = shell_exec($Command);
	$Files = explode(PHP_EOL, $Files);
	$Counter = 0;
	foreach($Files as $File){
	  $URL = 'https://'.substr($File,strlen($LocalPrefix));
	  if(strlen(trim($File))>0){
	    echo 'rm "<a href="'.$URL.'">'.$File.'</a>"<br>'.PHP_EOL;
	    $Counter++;
	  }
	}
	echo PHP_EOL;
	if($Counter==0){
	  echo 'None Found!'.PHP_EOL.PHP_EOL;
	}		
	return $Counter;
}


function ShowDirectoryTree($Root,$CurrentPath=''){
	$DirectoriesNotToExpandByDefault=array(
		'phpmyadmin',
		'.sync',
		'webs',
		'wordpress'
	);
	$Root = rtrim($Root,"/");
	$CurrentPath = trim($CurrentPath,"/");
		
	$directories=array();
	$files=array();
	echo '<ul class="tree">';
	if($handle = opendir($Root.DIRECTORY_SEPARATOR.$CurrentPath)){
		while(false !== ($entry = readdir($handle))){
			if(is_dir($Root.DIRECTORY_SEPARATOR.$CurrentPath.DIRECTORY_SEPARATOR.$entry)){
				if(($entry !== '.')&& ($entry!=='..')){
					$directories[$entry]=$entry;
				}
			}else{
				$files[$entry]=$entry;
			}
		}
		closedir($handle);
	}
	asort($directories);
	asort($files);
	foreach($directories as $name => $directory){
		echo '<li><a href="'.$name.'"><img src="/icons/folder.gif" alt="[DIR]"> '.$name.'</a>';
		
		$Skip = false;
		foreach($DirectoriesNotToExpandByDefault as $Ignore){
			if( strpos(strtolower($name),strtolower($Ignore) ) !== false){
				$Skip = true;
			}
		}
		if(!($Skip)){
			$RecursivePath=$CurrentPath.DIRECTORY_SEPARATOR.$name;
			//ShowDirectoryTree($Root,$RecursivePath);
		}
		echo '</li>';
	}
	foreach($files as $name => $file){
		echo '<li><a href="'.$CurrentPath.DIRECTORY_SEPARATOR.$name.'"><img src="/icons/unknown.gif" alt="[DIR]"> '.$name.'</a></li>';
	}
	echo '</ul>';
}

?><!DOCTYPE html>
<html>

<head>
	<title><?php echo file_get_contents('/etc/hostname'); ?> | VPS-Home</title>
	<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.2.1/css/bootstrap.min.css" integrity="sha384-GJzZqFGwb1QTTN6wy59ffF1BuGJpLSa9DkKMp0DgiMDm4iYMj70gZWKYbI706tWS" crossorigin="anonymous">
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.1.1/jquery.min.js" integrity="sha384-3ceskX3iaEnIogmQchP8opvBy3Mi7Ce34nWjpBIwVTHfGYWQS9jwHDVRnpKKHJg7" crossorigin="anonymous"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.6/umd/popper.min.js" integrity="sha384-wHAiFfRlMFy6i5SRaxvfOCifBUQy1xHdJ/yoi7FRNXMRBu5WHdZYu1hA6ZOblgut" crossorigin="anonymous"></script>
	<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.2.1/js/bootstrap.min.js" integrity="sha384-B0UglyR+jN6CkvvICOB2joaf5I4l3gm9GU6Hc1og6Ls7i6U/mkkaduKaBhlAXv9k" crossorigin="anonymous"></script>
	<link rel="stylesheet" href="https://cjtrowbridge.com/projects/simple-tree/simple-tree.css">
</head>

<body>
<div class="container">
	<div class="row">
		<div class="col-12">
			<h1<?php if(isset($CurrentHash)){echo ' title="Version: '.$CurrentHash.'"';} ?>><?php echo file_get_contents('/etc/hostname'); ?> (<?php echo $_SERVER['SERVER_ADDR']; ?>)</h1>
			<span class="fetch" data-uri="./?fetch=uptime"></span>
			<div class="fetch" data-uri="./?fetch=free-space-error"></div>
			<div class="fetch" data-uri="./?fetch=update-vps-home"></div>
		</div>
	</div>
	<div class="row">
		<div class="col-xs-12 col-6">
			<h2>Directory Listing:</h2>
			<?php ShowDirectoryTree('/var/www'); ?>
		
		</div>
		<div class="col-xs-12 col-6">
		
			<h2>df -h</h2>
			<pre class="fetch" data-uri="./?fetch=df"></pre>

			<h2>Directory Sizes</h2>
			<pre class="fetch" data-uri="./?fetch=dirs"></pre>

		</div>
		<div class="col-xs-12 col-6">
			
			<h2>Backup Sizes</h2>
			<pre class="fetch" data-uri="./?fetch=backups"></pre>
			
		</div>
		<div class="col-xs-12 col-6">
		

			<h2>Webs</h2>
			<pre class="fetch" data-uri="./?fetch=webs"></pre>

			<h2>Large Files</h2>
			<pre class="fetch" data-uri="./?fetch=large_files"></pre>

		</div>
	</div>
	<div class="row">
		<div class="col-12">
			<h2>Top</h2>
			<pre class="fetch" data-uri="./?fetch=top"></pre>
		</div>
	</div>
</div>

<script>
  $(function () {
    $('#myTab li:first-child a').tab('show')
  })
</script>
  
		
		
		
	</div>
</div>

<script>
	function LoadTools(){
		$('.fetch').each( function( index, listItem ) {
			
			var uri = $(this).data('uri');
			
			$(this).html('Loading <a href="'+uri+'">'+uri+'</a>...');
			
			$.get(uri, function(data){
				$("*[data-uri='"+uri+"']").html(data);
			})
			.fail(function(data){
				$("*[data-uri='"+uri+"']").html('<h1>Error</h1>');
			});

		});
	}
	LoadTools();
</script>
	
	<p><small>Loaded in <?php echo round(microtime(true)-$StartTime,4); ?> seconds.</small></p>

</body>
</html>
