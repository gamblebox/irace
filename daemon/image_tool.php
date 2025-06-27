<?php
/* function:  generates thumbnail */
function make_thumb($src,$dest,$desired_width) {
    /* read the source image */
    $source_image = imagecreatefrompng($src);
    $width = imagesx($source_image);
    $height = imagesy($source_image);
    /* find the "desired height" of this thumbnail, relative to the desired width  */
    $desired_height = floor($height*($desired_width/$width));
    /* create a new, "virtual" image */
    $virtual_image = imagecreatetruecolor($desired_width,$desired_height);
    /* copy source image at a resized size */
    imagecopyresized($virtual_image,$source_image,0,0,0,0,$desired_width,$desired_height,$width,$height);
    /* create the physical thumbnail image to its destination */
    imagepng($virtual_image,$dest);
}

/* function:  returns files from dir */
function get_files($images_dir,$exts = array('png')) {
    $files = array();
    if($handle = opendir($images_dir)) {
        while(false !== ($file = readdir($handle))) {
            $extension = strtolower(get_file_extension($file));
            if($extension && in_array($extension,$exts)) {
                $files[] = $file;
            }
        }
        closedir($handle);
    }
    return $files;
}

/* function:  returns a file's extension */
function get_file_extension($file_name) {
    return substr(strrchr($file_name,'.'),1);
}

/** settings **/
$images_dir = __DIR__ . '/../../../www/theme/login/screenshot/';
$thumbs_dir = __DIR__ . '/../../../www/theme/login/screenshot/thumbs/';
$thumbs_width = 400;
$images_per_row = 3;

/** generate photo gallery **/
$image_files = get_files($images_dir);
if(count($image_files)) {
    $index = 0;
    foreach($image_files as $index=>$file) {
        $index++;
        $thumbnail_image = $thumbs_dir.$file;
        if(!file_exists($thumbnail_image)) {
            $extension = get_file_extension($thumbnail_image);
            if($extension) {
                make_thumb($images_dir.$file,$thumbnail_image,$thumbs_width);
            }
        }
        echo '<a href="', str_replace(__DIR__ . '/../..', '', $images_dir.$file), '" data-lightbox="' . $index, '" class="photo-link smoothbox" rel="gallery"><img src="',str_replace(__DIR__ . '/../..', '', $thumbnail_image),'" /></a>';
        if($index % $images_per_row == 0) { echo '<div class="clear"></div>'; }
    }
    echo '<div class="clear"></div>';
}
else {
    echo '<p>There are no images in this gallery.</p>';
}
?>