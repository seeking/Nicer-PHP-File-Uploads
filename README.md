# Nicer PHP File Uploads

File uploads in PHP are pretty easy, but they're also a bit of a hassle.
This library takes the $_FILES array and replaces it with an array of file objects.

## But why?
- So you can easily detect whether a file is an image, video, etc.
- So you can easily move uploaded files to a new location.
- So you can quickly generate a random, unused name for the uploaded file.
- So you can squash security issues related to file uploads
- So you don't have to constantly reinvent the wheel.

## Issues
Right now, this library is in development. It's not quite ready to be used but soon
at least some of the functionality will be present.
