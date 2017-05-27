<?php

require_once( '../../../../wp-load.php' );
require_once( ABSPATH . 'wp-admin/includes/image.php' );
require_once( ABSPATH . 'wp-admin/includes/file.php' );
require_once( ABSPATH . 'wp-admin/includes/media.php' );

$site_id = 2;
$file_id = media_handle_upload( 'file', 0 );
$file_url = wp_get_attachment_url( $file_id );

$di_media_post = array(
	'post_title' => 'Test',
	'post_content' => '',
	'post_status' => 'publish',
	'post_type' => 'di_media'
);
$di_media_post_meta['type'] = 'image';
$di_media_post_meta['url'] = $file_id;
$di_media_post_meta['location'] = $site_id;
$di_media_id = wp_insert_post( $di_media_post );
add_post_meta( $di_media_id, 'di_media_meta', $di_media_post_meta );

echo $file_url;

die();

// file_put_contents( 'temp_upload/' . $user . '-' . $_FILES["file"]["name"], $data);
// $_FILES['file']['tmp_name'] = 'temp_upload/' . $user . '-' . $_FILES["file"]["name"];

// media_handle_upload( 'file', 0 );

// print_r( $_FILES );

// echo 'http://localhost:8888/ubcar/wp-content/plugins/digging-in/php/temp_upload/' . $user . '-' . $_FILES["file"]["name"];

die();

if(isset($_FILES["file"]["type"]))
{
	$validextensions = array("jpeg", "jpg", "png");
	$temporary = explode(".", $_FILES["file"]["name"]);
	$file_extension = end($temporary);
	if ((($_FILES["file"]["type"] == "image/png") || ($_FILES["file"]["type"] == "image/jpg") || ($_FILES["file"]["type"] == "image/jpeg")
	) && ($_FILES["file"]["size"] < 1000000)//Approx. 100kb files can be uploaded.
	&& in_array($file_extension, $validextensions)) {
		if ($_FILES["file"]["error"] > 0)
		{
			echo "Return Code: " . $_FILES["file"]["error"] . "<br/><br/>";
		}
		else
		{
			if (file_exists("upload/" . $_FILES["file"]["name"])) {
				echo $_FILES["file"]["name"] . " <span id='invalid'><b>already exists.</b></span> ";
			}
			else
			{
				$sourcePath = $_FILES['file']['tmp_name']; // Storing source path of the file in a variable
				$targetPath = "upload/".$_FILES['file']['name']; // Target path where file is to be stored
				move_uploaded_file($sourcePath,$targetPath) ; // Moving Uploaded file
				echo $_FILES['file']['tmp_name'];
				echo "<span id='success'>Image Uploaded Successfully...!!</span><br/>";
				echo "<br/><b>File Name:</b> " . $_FILES["file"]["name"] . "<br>";
				echo "<b>Type:</b> " . $_FILES["file"]["type"] . "<br>";
				echo "<b>Size:</b> " . ($_FILES["file"]["size"] / 1024) . " kB<br>";
				echo "<b>Temp file:</b> " . $_FILES["file"]["tmp_name"] . "<br>";
			}
		}
	}
	else
	{
		echo "<span id='invalid'>***Invalid file Size or Type***<span>";
	}
}
?>
