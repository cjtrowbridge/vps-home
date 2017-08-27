<?php

error_reporting(E_ALL);
ini_set('display_errors', '1');

function FetchURL($URL, $Method = 'GET', $Arguments = false,$Authorization = false,$UserAgent = 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13',$OtherHeaders = false){
  
  if($URL==''){
    return false;
  }
  
  //Make sure method is uppercase
  $Method=strtoupper($Method);
  
  //Initialize Arguments array if null
  if($Arguments==false){
    $Arguments=array();
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
<div class="container-fluid">
	<h1><?php echo file_get_contents('/etc/hostname'); ?> (<?php echo $_SERVER['SERVER_ADDR']; ?>)<?php if(isset($CurrentHash)){echo '('.$CurrentHash.')';} ?></h1>
	<div class="col-xs-12">
	<?php 
	
		if(disk_free_space('/')<(1e+9)){
			echo '<h2 class="warning">LOW DISK SPACE</h2>';
		}
		
		$GlobalHash = gitGlobalHash();
		
		if(isset($_GET['update'])){
			echo '<h2>Attempting to update VPS-Home...</h2>';
			//echo exec("wget https://raw.githubusercontent.com/cjtrowbridge/vps-home/master/index.php -O index.php");
			$New = file_get_contents('https://raw.githubusercontent.com/cjtrowbridge/vps-home/master/index.php');
			if($New==false){
				echo '<p>Unable to fetch update! Check connection?</p>';
			}else{
				echo '<p>Fetched Update. Saving...</p>';
			}
			
			
			
			$New = '<?php $CurrentHash = "'.$GlobalHash.'"; ?>'.$New;
			
			$Save = file_put_contents('index.php',$New);
			
			if($Save==false){
				echo '<p>Unable to save update! Check permissions?</p>';
			}else{
				echo '<p>Update complete!</p>';
			}
			exit;
		}else{
			if(
				(!(isset($CurrentHash)))||
				(!($GlobalHash==$CurrentHash))
			){
				echo '<h2><a href="./?update">Updates Available!</a></h2>';
			}
		}
		
	?>
	</div>
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

		<h2>Directory Sizes</h2>
		<pre><?php echo shell_exec('du -sh /var/www/*');?></pre>
		
		<h2>Webs</h2>
		<pre><?php 
		  $Lines = shell_exec('du -sh /var/www/webs/*');
		  $Lines = explode(PHP_EOL,$Lines);
		  foreach($Lines as $Line){
			$Link = stristr($Line,'/var/www/webs/');
			$Link = substr($Link,14);
			echo '<p>';
			$IP = gethostbyname($Link);
			if($IP==$_SERVER['SERVER_ADDR']){$Local = true;}else{$Local = false;}
			if(!($Local)){echo '<strike>';}
			if(!($Local)){echo '<span title="'.$Link.' resolves to '.$IP.'.'.PHP_EOL.'This does not match local IP of '.$_SERVER['SERVER_ADDR'].'">';}
			echo '<a href="//'.$Link.'" target="_blank">'.$Line.'</a></p>'.PHP_EOL;
			if(!($Local)){echo '</span></strike>';}
		  }
		?></pre>

		<h2>Backups</h2>
		<pre><?php echo shell_exec('du -sh /var/www/backups/*');?></pre>
		
	</div>
	<div class="col-md-6 col-sm-12 col-xs-12">
		
		<h2>Uptime</h2>
		<pre><?php echo shell_exec('uptime'); ?></pre>
		<h2>/etc/motd:</h2>
		<pre><?php echo file_get_contents('/etc/motd'); ?></pre>
		<h2>df -h</h2>
		<pre><?php passthru('df -h'); ?></pre>
		<h2>Top</h2>
                <pre><?php passthru('/usr/bin/top -b -n 1'); ?></pre>
		
	</div>
</div>
</body>

</html>
