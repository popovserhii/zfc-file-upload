# ZF2 Files Upload Module

Simple files upload module which allow attach files to any registered item.

There is no complex settings for this module that allow use module immediately after install.
 
# Usage
Enable module in your `modules.config.php` with next name `Popov\ZfcFileUpload`.

Add next code snippet to any template file and upload files you want
```php
 <?= $this->partial('template/file-upload', [
     'prefix' => 'student', // unique key
     'loadJs' => true,
     'permissionUploadFile' => true, // allow upload files
     'permissionDeleteFile' => true, // allow delete files
 ]) ?>
 ```
