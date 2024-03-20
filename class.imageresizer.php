<?php
/**
 *  @author: Hasan Shahriar
 *  @url: http://github.com/hsleonis
 *  Resize Bulk Images using GD Library
 */

class ImageResizer{
    private $path;
    private $height;
    private $width;
    private $thumb_dir;
    private $compress;
    private $is_crop_hard;
    private $resize_list;
    private $background_color;
    private $convertToJpg;

    /**
     * ImageResizer constructor.
     * @param array $arr
     */
    function __construct($arr=array()){

        if(is_array($arr)) {
            // Default value
            $this->path         = isset($arr['path'])? $arr['path'] : dirname(__FILE__).'/img';
            $this->height       = isset($arr['height'])? $arr['height'] : 200;
            $this->width        = isset($arr['width'])? $arr['width'] : 200;
            $this->thumb_dir    = isset($arr['thumb_dir'])? $arr['thumb_dir'].'/': dirname(__FILE__).'/thumb/';
            $this->compress     = isset($arr['compress'])? ($arr['compress']>=0 && $arr['compress']<=1)? $arr['compress']:1:0.8;
            $this->is_crop_hard = isset($arr['is_crop_hard'])?(bool)$arr['is_crop_hard']:false;
            $this->resize_list = isset($arr['resize_list'])?(bool)$arr['resize_list']:true;

            $this->background_color = isset($arr['background_color'])? $arr['background_color'] : '#fff';
            

            $this->contain = isset($arr['contain']) ? (bool)$arr['contain'] : false;
            
            $this->convertToJpg = isset($arr['convertToJpg']) ? (bool)$arr['convertToJpg'] : false;


        }
        else return false;
    }

    /**
     * Create directory for images
     */
    private function set_directory(){
        if(!is_dir($this->thumb_dir)) mkdir($this->thumb_dir);
    }

    /**
     * Increase memory limit, execution time to work on lots of images
     */
    private function set_env(){
        ini_set("memory_limit", "256M");
        ini_set("max_execution_time", 3000);
    }

    /**
     * Check image type and parse data
     * @param $path
     * @return bool|resource
     */
    private function image_data($path, $mime){

        if (!strstr($mime, 'image/')) {
            return false;
        }

        if($mime=='image/png'){ $src_img = imagecreatefrompng($path); }
        else if($mime=='image/jpeg' or $mime=='image/jpg' or $mime=='image/pjpeg') {
            $src_img = imagecreatefromjpeg($path);
        }
        else $src_img = false;
        return $src_img;
    }

    /**
     * Save new image to new_thumb_loc
     * @param $dst_src
     * @param $new_thumb_loc
     * @param $mime
     * @return bool
     */
    private function save($dst_src, $new_thumb_loc, $mime){
        if($mime=='image/png'){ $result = imagepng($dst_src,$new_thumb_loc,$this->compress*10); }
        else if($mime=='image/jpeg' or $mime=='image/jpg' or $mime=='image/pjpeg') {
            $result = imagejpeg($dst_src,$new_thumb_loc,$this->compress*100);
        }
        return $result;
    }

    /**
     * Create thumbnail from larger image using GD library
     *
     * @param $imageName
     * @param $newWidth
     * @param $newHeight
     * @return bool
     */
    public function createThumbnail($path,$newWidth,$newHeight) {
       
        $mime_info  = getimagesize($path);
        $mime       = $mime_info['mime'];

        $imageName = basename($path);


        if ($this->convertToJpg && $mime === 'image/png') {
            // Si la imagen es un PNG, convertir a JPEG antes de procesarla
            $converted_jpeg_name = str_replace('.png', '.jpg', $imageName);
            $jpeg_path = $this->thumb_dir . '/' . $converted_jpeg_name;
            $conversion_result = $this->convertPngToJpeg($path, $jpeg_path);
            if (!$conversion_result) {
                // Si la conversión falla, mostrar un mensaje de error
                //echo '<li class="msg-error">Error al convertir ' . $path . ' a JPEG</li>';
                return false;
            }
            // Cambiar la ruta a la imagen JPEG recién creada
            $path = $jpeg_path;
            // Actualizar la información MIME
            $mime = 'image/jpeg';
            $imageName = $converted_jpeg_name;
        }

        $src_img    = $this->image_data($path, $mime);
        if($src_img===false) return false;

        $old_w = imageSX($src_img);
        $old_h = imageSY($src_img);

        $source_aspect_ratio = $old_w / $old_h;
        $desired_aspect_ratio = $newWidth / $newHeight;

        if ($source_aspect_ratio > $desired_aspect_ratio) {
            /*
             * Triggered when source image is wider
             */
            $thumb_h = $newHeight;
            $thumb_w = ( int ) ($newHeight * $source_aspect_ratio);
        } else {
            /*
             * Triggered otherwise (i.e. source image is similar or taller)
             */
            $thumb_w = $newWidth;
            $thumb_h = ( int ) ($newWidth / $source_aspect_ratio);
        }

        $dst_img     =   ImageCreateTrueColor($thumb_w,$thumb_h);

        $color = imagecolorallocatealpha($dst_img, 0, 0, 0, 127);
        imagefill($dst_img,0,0,$color);
        imagesavealpha($dst_img, true);

        imagecopyresampled($dst_img, $src_img, 0, 0, 0, 0, $thumb_w, $thumb_h, $old_w, $old_h);

        if($this->is_crop_hard) {
            $x = ($thumb_w - $newWidth) / 2;
            $y = ($thumb_h - $newHeight) / 2;

            $tmp_img    = imagecreatetruecolor($newWidth, $newHeight);
            $color      = imagecolorallocatealpha($tmp_img, 0, 0, 0, 127);
            imagefill($tmp_img,0,0,$color);
            imagesavealpha($tmp_img, true);

            imagecopy($tmp_img, $dst_img, 0, 0, $x, $y, $newWidth, $newHeight);
            $dst_img = $tmp_img;
        }

        if($this->contain){

            // Establecer un ancho y alto máximo
            $ancho_maximo = $newWidth;
            $alto_maximo = $newHeight;

            $ancho = $ancho_maximo;
            $alto = $alto_maximo;

            $ratio_orig = $old_w/$old_h;

            if ($ancho/$alto > $ratio_orig) {
               $ancho = $alto*$ratio_orig;
            } else {
               $alto = $ancho/$ratio_orig;
            }

            // Redimensionar
            $dst_img = imagecreatetruecolor($ancho_maximo, $alto_maximo);
            $blanco = imagecolorallocate($dst_img, 255, 255, 255);
            imagefill($dst_img, 0, 0, $blanco);

            $center_x = ($ancho_maximo/2) - ($ancho/2);
            $center_y = ($alto_maximo/2) - ($alto/2);

            imagecopyresampled($dst_img, $src_img, $center_x, $center_y, 0, 0, $ancho, $alto, $old_w, $old_h);

        }

        $new_thumb_loc = $this->thumb_dir . $imageName;
        $result = $this->save($dst_img, $new_thumb_loc, $mime);

        imagedestroy($dst_img);
        imagedestroy($src_img);
        return $result;
    }

    /**
     * Generate thumbnails
     */
    public function create(){

        // Set environment
        $this->set_env();

        // check directory location
        $this->set_directory();


        $path = $this->path;

        $log = '';

        //$imageName = basename($filePath);

        // check if there are files
        if(file_exists($path)) {

            $i = $this->createThumbnail($path, $this->width, $this->height);

            if($this->resize_list) {
                if ($i) $log = '<p><b>' . $path . '</b>  resized.</p>';
                else $log = '<p>Resizing error on <b>' . $path . '</b></p>';
            }
           
        }
        else {
            if($this->resize_list)
            $log = '<p>No files found</p>';
        }

        echo json_encode(array(
            'log' => $log
        ));

        return true;
    }

    /**
     * Convertir una imagen PNG a JPEG
     * @param string $pngPath Ruta de la imagen PNG
     * @param string $jpegPath Ruta de la imagen JPEG de salida
     * @return bool
     */
    private function convertPngToJpeg($pngPath, $jpegPath) {
        $src_img = imagecreatefrompng($pngPath);
        if ($src_img === false) {
            return false;
        }

        $width = imagesx($src_img);
        $height = imagesy($src_img);
        $dst_img = imagecreatetruecolor($width, $height);
        imagefill($dst_img, 0, 0, imagecolorallocate($dst_img, 255, 255, 255));
        imagecopy($dst_img, $src_img, 0, 0, 0, 0, $width, $height);

        $result = imagejpeg($dst_img, $jpegPath, $this->compress * 100);

        imagedestroy($src_img);
        imagedestroy($dst_img);

        return $result;
    }

}