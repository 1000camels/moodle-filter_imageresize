<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 *  Image resize filtering
 *
 *  This filter will select a different sized image to use when 
 *  rendering, based upon the scaling which is done within the editor.
 * 
 *  It requires a core change to file_storage, documented in the tracker
 *  issue: http://tracker.moodle.org/browse/MDL-10950
 *
 * @package    filter
 * @subpackage imageresize
 * @copyright  2012 darcy w. christ  {@link http://www.1000camels.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

//require_once($CFG->libdir.'/filelib.php');

/**
 * Automatic image resizing class.
 *
 * Image sizes are defined in Site administration > Appearance > Image Sizes
 * Available sizes are: Thumbnail, Small, Medium and Large, but the actual 
 * dimensions can be configured
 *
 * @package    filter
 * @subpackage imageresize
 * @copyright  2012 darcy w. christ  {@link http://www.1000camels.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class filter_imageresize extends moodle_text_filter {
        
    function filter($text, array $options = array()) {
        global $CFG;

        if (!is_string($text) or empty($text)) {
            // non string data can not be filtered anyway
            return $text;
        }
        if (stripos($text, '<img') === false) {
            // performance shortcut - all regexes below start with the <img> tag,
            // if not present nothing can match
            return $text;
        }

        $newtext = $text; // we need to return the original value if regex fails!
        
        
            $search = '/<img(.*)\/>/is'; //\s([^>]*)src="(.*)"\s[^>]*>/is';
            $newtext = preg_replace_callback($search, 'filter_resizeimage_callback', $newtext);
        


        if (empty($newtext) or $newtext === $text) {
            // error or not filtered
            unset($newtext);
            return $text;
        }


        return $newtext;
    }
    
}



///===========================
/// utility functions


/**
 * Checks the html width attribute and chooses a more appropriate scaled image
 * to use rather than the first sized.
 * 
 * @global type $CFG
 * @param Array $img
 * @return String $newimg 
 */
function filter_resizeimage_callback($img) {
    global $CFG;
    
    $attrs = split(' ',trim($img[1]));
    
    foreach($attrs as $attr) {
        list($name,$value) = split('=',$attr);
        
        $attributes[$name] = trim($value,"\x22\x27");
    }
    
    // not dimensions?
    if(!isset($attributes['width']) && !isset($attributes['height'])) return $img[0];
    
    $sizes = array(
        'thumbnail' => $CFG->imagesizethumbnail,
        'small' => $CFG->imagesizesmall,
        'medium' => $CFG->imagesizemedium,
        'large' => $CFG->imagesizelarge,
    );
    
    foreach($sizes as $size => $dimension) {
        $attributes['height'] = (integer) $attributes['height'];
        $attributes['width'] = (integer) $attributes['width'];
        
        if($attributes['width'] <= $dimension) {
            $resized = $size;
            break;
        }
    }
    
    if($resized) {
        
        $baseURL = dirname($attributes['src']);
        $filename = basename($attributes['src']);
        
        $attributes['src'] = $baseURL.'/size/'.$resized.'/'.$filename;
        
        $newimg = '<img';
        foreach($attributes as $name => $value) {
            $newimg .= ' '.$name.'="'.$value.'"';
        }
        
        // set title for testing purposes
        $newimg .= ' title="'.$attributes['src'].' '.$attributes['width'].' x '.$attributes['height'].'"';
        
        $newimg .= ' />';
        
    } else {
        
        $newimg = $img[0];
        
    }
    
    
    return $newimg;
    
}