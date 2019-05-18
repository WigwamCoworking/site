<?php
/**
 * Plugin Name:     Coworking Group
 * Plugin URI:      
 * Description:     Adds a custom block to list users who purchased current adhesion.
 * Author:          Emmanuel Gendron
 * Author URI:      
 * Text Domain:     coworking-group
 * Domain Path:     /languages
 * Version:         1.0.0
 *
 * @package         Coworking_Group
 */

/*
 * Define globals.
 */
global $this_year;

$this_year = date( 'Y' );

/*
 * Required files.
 */
require_once( 'blocks/group.php' );
