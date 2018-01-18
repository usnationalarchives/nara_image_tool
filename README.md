# NARA Image Tool

## Goal
This module aims to bridge the gap between the NARA API and Media entities in Drupal 8. 

## Installation
### The Composer Way
In your composer file add the following two snippets to your Drupal 8 root Composer file. This will download the NARA Image Tool module, Crop, and Image Widget Crop. 
```
"repositories": [
  {
    "type": "vcs",
    "url": "git@github.com:agencychief/nara_image_tool.git"
  }  
]
```
```
"require": {
  "agencychief/nara_image_tool": "dev-develop"
}
```
After running `composer install` enable the module, which will also enable Drupal Core Media. 

### The Non Composer Way
Clone this repository into your Drupal 8 module folder and enable the module. This will also add Drupal's core Media as a requirement. 

To include any cropping tools, you will need to install other contib modules like Crop and Image Widget Crop or Focal Point. 

## Using NARA Image Tool
Currently this module only has one function, create a image File entity and corresponding Media entity using the NARA Catalog API. 

Once the module is installed you can add an ID from the NARA database to the form at `/admin/structure/nara_image_tool/add` or going to `/admin/config` and clicking the link for Media from Archive API. 

Once the entities have been created, they can be used however you wish in the Drupal site. 