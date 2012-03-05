<?php
    
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
    
    require "FileUploads.php";
    
    foreach ($_FILES as $file) {
        if ($file->isImage()) {
            $file->save('/home/tdavies/Test');
        } else {
            echo "Only images are allowed!";
        }
    }
    
    echo "<pre>";
    print_r($_FILES);
    echo "</pre>";
    
?>

<html>
<head><title>Example</title></head>
<body>
    <form enctype="multipart/form-data" method="post" action="test.php">
        <input type="file" name="somefile" />
        <input type="file" name="somefile2" />
        <br/>
        <input type="text" value="Some Random Text Field" name="random" />
        <br/>
        <input type="submit" value="Submit" />
    </form>
</body>
