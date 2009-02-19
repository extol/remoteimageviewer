<?php
/*
 Written by Tom Carlson, Dec 16, 2008
 to display a list of files in a directory

this could be a local directory (easy)
    filelist.php?l=/images

a remote directory without an index file 
    filelist.php?l=http://tomcarlson.com/images
    
or an amazon directory that is read accessible
    filelist.php?l=http://images.pagesalt.com/
*/

function get_tag_contents($xmlcode,$tag) {
  $i=0;
  $offset=0;
  $xmlcode = trim($xmlcode);
  do{ #Step through each ocurrence of <$tag>...</$tag> in the XML
    #find the next start tag
    $start_tag=strpos ($xmlcode,"<".$tag.">",$offset);
    $offset = $start_tag;
    #find the closing tag for the start tag you just found
    $end_tag=strpos ($xmlcode,"</".$tag.">",$offset);
    $offset = $end_tag;
    #split off <$tag>... as a string, leaving the </$tag> 
    $our_tag = substr ($xmlcode,$start_tag,($end_tag-$start_tag));
    $start_tag_length = strlen("<".$tag.">");
    if (substr($our_tag,0,$start_tag_length)=="<".$tag.">"){
      #strip off stray start tags from the beginning
      $our_tag = substr ($our_tag,$start_tag_length);
    }
    $array_of_tags[$i] =$our_tag;
    $i++;
  }while(!(strpos($xmlcode,"<".$tag.">",$offset)===false));
return $array_of_tags;
}

function keep_extensions($file_array,$allowedextension_array)
// takes an array of filenames
// removes any that don't have the correct extension
// returns pruned array back
{
   $returnarray = array();
	 foreach($file_array as $filename)
	   foreach($allowedextension_array as $extension)
	   {
	   	 $pattern = "/" . "\." . $extension . "/";
	     if (preg_match($pattern,$filename))
	       $returnarray[]=$filename;
	   }
	   
	 return($returnarray);
}

function thc_filelist($location)
{
	$newline="\n";
	
  // deny them higher directories or even the current directory
  if ((substr($location,0,1)==".") || (strlen($location)==0))
     return;  // they tried to get clever and go to a higher directory
    
  $remote = (substr($location, 0, 4)=="http");
  
  if (!$remote) // they want a local directory
  {
  	$lowest = getcwd();  // make sure they can't go lower than the script
  	
  	$dir = $lowest . $location;
  	if (!is_dir($dir))
  	{
  		// echo "<h1>" . $dir . " - Not a Valid Directory </h1>";
  		return;
    }
    else
  	  if ($handle = opendir($dir)) {
  	  	$filestring = "";      
        while (false !== ($file = readdir($handle))) {
        	if (($file!=".")&&($file!=".."))        	
            $filestring .= $location . "/" . $file . $newline;          
        }
      
        closedir($handle);        
        $filearray=explode($newline,$filestring);
        return $filearray;
      }
  }
  else  // they want a remote directory
  { $result = "";
  	$handle = fopen($location, "r");
  	while  (!feof($handle))
  	  $result .= fgets($handle, 4096);
  	fclose ($handle);
  	
  	if (preg_match("/ListBucketResult/",$result)) // Amazon!
  	{
  	  $filearray = get_tag_contents($result,"Key");  	    	 
  	  $filestring = "";    	  
  	  foreach($filearray as $value)
  	    $filestring .= $location . "/" . $value . $newline;
  	  
  	  $filearray=explode($newline,trim($filestring,$newline));
  	  return $filearray;
    }
    else
    {  // they are just doing a directory to a remote server (without an index.html file, of course)
    	
    	$lines = explode("\n",$result);
    	$filestring = "";   
    	foreach($lines as $value)
    	{
    		// $pattern = '/[^\w][a-z0-9- ]+(\.[a-z0-9]{3})[^\w]/';
    		$pattern = '/[a-z0-9_ -]+\.([a-z0-9]{3})/';
        if (preg_match($pattern, $value, $matches))
          $filestring .= $location . "/" . $matches[0] . $newline;
      } 
      
      $filearray=explode($newline,trim($filestring,$newline));
      return $filearray;
    }
  }
}


?> 