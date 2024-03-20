<?php
/**
 *  @author: Hasan Shahriar
 *  Resize Images proportionaly example
 */
mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');

require_once ('class.imageresizer.php');

$args = array(
    'height'    => $_POST['height'],
    'width'     => $_POST['width'],
    'contain' => $_POST['contain'],
    'convertToJpg' => $_POST['toJpg'],
    'path' => $_POST['filePath'],
    'thumb_dir' => $_POST['thumb_dir']
);

$img = new ImageResizer($args);
$img->create();