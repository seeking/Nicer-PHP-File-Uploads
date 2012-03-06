# Nicer PHP File Uploads

File uploads in PHP are pretty easy, but they're also a bit of a hassle.
This library takes the $_FILES array and replaces it with an array of file
objects.

## But why?
- So you can easily detect whether a file is an image, video, etc.
- So you can easily move uploaded files to a new location.
- So you can quickly generate a random, unused name for the uploaded file.
- So you can squash security issues related to file uploads
- So you don't have to constantly reinvent the wheel.

## How to use
If you're creating a new project, you should be fine to just drop the code in
using an include at the top of your application structure. This will
automatically convert the $_FILES array to an array of objects.

This functionality may break existing software so you may wish to remove the
snippet of code that does this automatically. It can be found at the very bottom
of the file. Just remove that and there should be no compatibility issues.


## Examples
    
A regular use of the library:

    if (!$_FILES['foo']->hasError() && $_FILES['foo']->isImage()) {
        $new_filename = $_FILES['foo']->save('/home/websites/uploaded_files/');
    }

An example of how to use the library without the automatic $_FILES array
conversion:
    
    $foo = new File($_FILES['foo']);
    
    if (!$foo->hasError() && $foo->isImage()) {
        $new_filename = $foo->save('/home/websites/uploaded_files/');
    }
