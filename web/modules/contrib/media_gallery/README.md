# CONTENTS OF THIS FILE

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Maintainers


## INTRODUCTION

A simple gallery of media.

 * For a full description of the module, visit the project page:
   <https://drupal.org/project/media_gallery>

 * To submit bug reports and feature suggestions, or to track changes:
   <https://drupal.org/project/issues/media_gallery>


## REQUIREMENTS

 * media (drupal core)
 * media_library (drupal core)
 * photoswipe <https://drupal.org/project/photoswipe>


## INSTALLATION

Install as you would normally install a contributed Drupal module. Visit:
<https://www.drupal.org/node/1897420> for further information.


## CONFIGURATION

    1. Navigate to Extend and enable the module.
    2. Add new galleries: /admin/content/media-gallery
    3. Add own fields to galleries (if you want):
       /admin/structure/media-gallery/fields
    4. All galleries view: /galleries

### Change PhotoSwipe to ColorBox (if you want)

    1. Add new media view mode:
      /admin/structure/display-modes/view/add/media
    2. Enable new view mode and set formatter as ColorBox:
      /admin/structure/media/manage/image/display/media_colorbox
    3. Set view mode as ColorBox for the galleries:
      /admin/structure/media-gallery/display

## MAINTAINERS

 * Andrei Ivnitskii - <https://www.drupal.org/u/ivnish>
