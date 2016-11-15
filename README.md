# Proxy
A php proxy to browse the internet anonymously.

### Installation

First download all files in this repository (proxy.php, index.php) then upload them to your web server. 
Now create two folders, `img-cache` and `cache` and make sure the web server has read and write permissions to the folders.

Now go into `proxy.php` and replace the line `$proxy = '<username>:<password>@<host>:<port>';` with your own `SOCKS5` proxy.

### Dependencies

This proxy requires a few dependencies to run:

  - PHP 7
  - PHP MCrypt
  - PHP Curl
  
These can be installed on Ubuntu using the following commands:

```sh
$ sudo apt-get update && sudo apt-get upgrade
$ sudo apt-get install php7.0-mcrypt php7.0 mcrypt curl php7.0-curl
```

### Ussage

Just navigate the the index file where you put the two files. Then enter any url and press `Go!` and it will take you to `proxy.php?u=<URL>`

### Settings

There are some settings you can change:

  - What method of encoding/encrypting the url
  
You can choose between AES-256 and Base64 for encrypting/encoding the url, Base64 will be faster but less secure. To change method go into `proxy.php` and change `$use_b64 = true;` to true for Base64 and false for AES-256

The AES-256 encryption uses a different key for each session meaning a link will only be valid for you and for the time you have your browser open, if you use Base64 the link will never change and always be valid.

### License

This is licensed under the MIT license.
