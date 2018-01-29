<?php
/**
*   Plugin Name:    Taxonomy Taxi
*   Plugin URI:     https://wordpress.org/plugins/taxonomy-taxi/
*   Description:    Show custom taxonomies in /wp-admin/edit.php automatically
*   Version:        1.0.3
*   Author:         postpostmodern, pinecone-dot-website
*   Author URI:     https://rack.and.pinecone.website
*   Photo Credit:   https://www.flickr.com/photos/photos_mweber/
*   Photo URL:      https://www.flickr.com/photos/photos_mweber/540970484/
*   Photo License:  Attribution-NonCommercial 2.0 Generic (CC BY-NC 2.0)
*   License:        GPL-2.0+
*   License URI:    https://www.gnu.org/licenses/gpl-2.0.txt
*/

if (version_compare(phpversion(), '5.4', "<")) {
    add_action('admin_notices', create_function("", 'function(){
        echo "<div class=\"notice notice-success is-dismissible\">
                <p>Taxonomy Taxi requires PHP 5.4 or greater</p>
              </div>";
    };'));
} else {
    require __DIR__.'/index.php';
}