# Lendesk Coding Challenge

Using PHP, create a command line application that recursively reads all of the images from the supplied directory of images, extracts their EXIF GPS data (longitude and latitude), and then writes the name of that image and any GPS co-ordinates it finds to a CSV file.

This utility should be executable from the command line (i.e.: ‘php ./app.rb’ or as an executable).
With no parameters, the utility should default to scanning from the current directory. It should take an optional parameter that allows any other directory to be passed in.

As a bonus, output either CSV or HTML, based on a parameter passed in via the command line.

### Requirements

- PHP version >= 7.2
- PHP "Exif" extension

### Usage

```bash
php -c ./php.ini run.php -d ./gps_images
```

I included a custom php configuration file that enables the exif extension since it is not by default.
