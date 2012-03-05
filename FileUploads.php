<?php

class NonUploadedFile       extends Exception {}
class CouldNotMoveFile      extends Exception {}
class DirectoryDoesNotExist extends Exception {}
class DirectoryUnwritable   extends Exception {}

class File {
    /* File attributes: */
    public  $name      = null;
    public  $mime_type = null;
    public  $size      = null;
    private $tmp_name  = null;
    private $error     = null;
    
    
    
    private $image_file_extensions = array(
        'jpg', 'jpeg', 'png', 'gif', 'tiff', 'bmp', 'ico',
    );
    
    private $audio_file_extensions = array(
        'mp3', 'ogg', 'webm',
    );
    
    private $video_file_extensions = array(
        'avi', 'mpeg', 'mp4', 'mkv', 'webm', 'flv',
    );
    
    
    
    public function __construct($data) {
        // Extract variables from array:
        $this->name      = $data['name'];
        $this->mime_type = $data['type'];
        $this->size      = $data['size'];
        $this->tmp_name  = $data['tmp_name'];
        $this->error     = $data['error'];
        
        // Get file extension:
        $this->file_extension = strtolower(substr(strrchr($this->name, '.'), 1));
    }
    
    
    
    /** Methods for determining file type / info: **/
    private function checkFileType($mime_type_segment, $file_extensions) {
        // Check mime type:
        if (!empty($this->mime_type)) {
            $first = substr($this->mime_type, 0, strpos($this->mime_type, '/'));
            if ($first != $mime_type_segment) {
                return false;
            }
        }
        
        // Check file extension:
        if (in_array($this->file_extension, $file_extensions)) {
            return true;
        }
        
        return false;
    }
    
    
    public function isImage() {
        return $this->checkFileType("image", $this->image_file_extensions);
    }
    
    public function isAudio() {
        return $this->checkFileType("audio", $this->image_file_extensions);
    }
    
    public function isVideo() {
        return $this->checkFileType("video", $this->image_file_extensions);
    }
    
    
    
    /** Methods for saving file: **/
    private function generateRandomName($directory) {
        if ($directory[strlen($directory) - 1] != DIRECTORY_SEPARATOR) {
            $directory .= DIRECTORY_SEPARATOR;
        }
        
        $filename = "";
        do {
            $filename = md5(time() . rand()) . '.' . $this->file_extension;
        } while (file_exists($directory . $filename));
        
        return $filename;
    }
    
    
    public function setMovedName($filename) {
        $this->random_name = $filename;
        return $this;
    }
    
    
    public function save($directory, $allow_non_uploaded_files = false) {
        // Add slash to end of directory if required:
        if ($directory[strlen($directory) - 1] != DIRECTORY_SEPARATOR) {
            $directory .= DIRECTORY_SEPARATOR;
        }
        
        // Unless flag set, check that file was uploaded:
        if (!$allow_non_uploaded_files) {
            if (!is_uploaded_file($this->tmp_name)) {
                throw new NonUploadedFile;
            }
        }
        
        // Check directory exists:
        if (!is_dir($directory)) {
            throw new DirectoryDoesNotExist;
        }
        
        // Check it is writable:
        if (!is_writable($directory)) {
            throw new DirectoryUnwritable;
        }
        
        // Generate random name:
        if (!isset($this->random_name)) {
            $this->random_name = $this->generateRandomName($directory);
        }
        
        // Move the file:
        if (@move_uploaded_file($this->tmp_name, $directory . $this->random_name)) {
            return $this->random_name;
        } else {
            throw new CouldNotMoveFile;
        }
    }
}



foreach ($_FILES as $key => $file) {
    $_FILES[$key] = new File($file);
}
