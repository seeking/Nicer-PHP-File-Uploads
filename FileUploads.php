<?php

/**
 * Nicer PHP File Uploads
 *
 * Nicer PHP File Uploads loops through the $_FILES variable and replaces the
 * sub-arrays with File objects. From then on, it's much easier to manipulate
 * the uploaded files, validate them and save them.
 *
 * An example of how simple it is to save a file upload, safely:
 *
 *      if (!$_FILES['foo']->hasError() && $_FILES['foo']->isImage()) {
 *          $_FILES['foo']->save('/home/websites/uploaded_files/');
 *      }
 *
 * Please report bugs at: https://github.com/dotty/Nicer-PHP-File-Uploads/issues
 *
 * @author Tim Davies <mail@timdavi.es>
 * @license http://www.gnu.org/licenses/lgpl.html
 */


class NonUploadedFile       extends Exception {}
class CouldNotMoveFile      extends Exception {}
class DirectoryDoesNotExist extends Exception {}
class DirectoryUnwritable   extends Exception {}


/** Set file upload constants for compatibility with earlier PHP versions: **/
$error_codes = array(
    0 => "UPLOAD_ERR_OK",
    1 => "UPLOAD_ERR_INI_SIZE",
    2 => "UPLOAD_ERR_FORM_SIZE",
    3 => "UPLOAD_ERR_PARTIAL",
    4 => "UPLOAD_ERR_NO_FILE",
    6 => "UPLOAD_ERR_NO_TMP_DIR",  // Introduced in PHP 4.3.10 and PHP 5.0.3.
    7 => "UPLOAD_ERR_CANT_WRITE",  // Introduced in PHP 5.1.0.
    8 => "UPLOAD_ERR_EXTENSION",   // Introduced in PHP 5.2.0.
);

foreach ($error_codes as $code => $constant) {
    if (!defined($constant)) {
        define($constant, $code);
    }
}

unset($error_codes, $code, $constant);


/**
 * File object
 */
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



    /**
     * Create File object. Expects a sub-array from $_FILES.
     * @param array $data Sub-array from $_FILES.
     * @return File Returns self.
     */
    public function __construct($data) {
        // Extract variables from array:
        $this->name      = $data['name'];
        $this->mime_type = $data['type'];
        $this->size      = $data['size'];
        $this->tmp_name  = $data['tmp_name'];
        $this->error     = $data['error'];

        // Get file extension:
        $this->file_extension = strtolower(
            substr(strrchr($this->name, '.'), 1)
        );

        return $this;
    }

    
    /**
     * Returns true if the file uploaded with error.
     * @return boolean True if file has error
     */
    public function hasError() {
        if ($this->error == UPLOAD_ERR_OK) {
            return false;
        }
        return true;
    }


    /**
     * Return error message if there is one.
     * @return string
     */
    public function getErrorMessage() {
        // Check file upload error:
        switch ($this->error) {
            case UPLOAD_ERR_INI_SIZE:
                return 'The uploaded file exceeds the upload_max_filesize ' .
                       'directive in php.ini.';
            case UPLOAD_ERR_FORM_SIZE:
                return 'The uploaded file exceeds the MAX_FILE_SIZE ' .
                       'directive that was specified in the HTML form.';
            case UPLOAD_ERR_PARTIAL:
                return 'The uploaded file was only partially uploaded.';
            case UPLOAD_ERR_NO_FILE:
                return 'No file was uploaded.';
            case UPLOAD_ERR_NO_TMP_DIR:
                return 'Missing a temporary folder.';
            case UPLOAD_ERR_CANT_WRITE:
                return 'Failed to write file to disk.';
            case UPLOAD_ERR_EXTENSION:
                return 'A PHP extension stopped the file upload.';
            case UPLOAD_ERR_OK:
            default:
                return false;
        }
    }
    
    
    /** Methods for determining file type / info: **/

    /**
     * Generic function for determining file type.
     *
     * @param string $mime_type_segment String to look for in first segment of
     *                                  mime type.
     * @param array $file_extensions Array of file extensions to compare file
     *                               against.
     * @return boolean Returns true or false based on whether conditions are
     *                 matched.
     */
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


    /**
     * Check whether file is an image file.
     * @return bool Returns true or false
     */
    public function isImage() {
        return $this->checkFileType("image", $this->image_file_extensions);
    }

    /**
     * Check whether file is an audio file.
     * @return bool Returns true or false
     */
    public function isAudio() {
        return $this->checkFileType("audio", $this->audio_file_extensions);
    }

    /**
     * Check whether file is a video file.
     * @return bool Returns true or false
     */
    public function isVideo() {
        return $this->checkFileType("video", $this->video_file_extensions);
    }



    /** Methods for saving file: **/

    /**
     * Generates random filename that doesn't exist in directory
     *
     * @param string $directory Directory image is to be saved in.
     * @return string Randomly generated filename
     */
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

    /**
     * Set name of file for when moved.
     *
     * @param string $filename Name of file
     * @return File Returns self
     */
    public function setMovedName($filename) {
        $this->random_name = $filename;
        return $this;
    }


    /**
     * Save file in directory specified
     *
     * @param string $directory Directory to save file into
     * @param bool $allow_non_uploaded_files Whether to allow files who have not
     *                                       been uploaded to be saved.
     * @return string If the file saves successfully then the new filename is
     *                returned.
     * @throws NonUploadedFile Thrown if file object was not uploaded via form
     *                         (potentially a sign that someone has tried to
     *                          attack the application)
     * @throws DirectoryDoesNotExist Thrown if directory specified doesn't exist
     * @throws DirectoryUnwritable Thrown if directory specified cannot be
     *                             written to.
     * @throws CouldNotMoveFile Catch all exception thrown if the file could not
     *                          be moved and there is no understanding of why
     */
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
        $path = $directory . $this->random_name;
        if (@move_uploaded_file($this->tmp_name, $path)) {
            return $this->random_name;
        } else {
            throw new CouldNotMoveFile;
        }
    }
}


/**
 * The following code is not required to use the library, however it makes the
 * experience a lot smoother.
 *
 * However, if the library is being introduced into existing software or into
 * an environment where it may affect other pieces of software, it is
 * recommended that you remove this code as code that relies on $_FILES
 * containing arrays full of strings rather than objects may break in weird and
 * wonderful ways.
 */
foreach ($_FILES as $key => $file) {
    $_FILES[$key] = new File($file);
}
