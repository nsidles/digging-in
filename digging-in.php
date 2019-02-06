<?php
	/**
	 * Plugin Name: Digging In
	 * Plugin URI: http://sidl.es/
	 *
	 * Description: Digging In is a web tool that allows educators to create educational lessons associated with precise geographic points. Students can answer questions, fill out surveys, upload media, and have their assessments evaluated. This plugin was created for soil scientists, but it can be used for many lessons.
	 *
	 * Version: 0.9.3
	 * Author: Nathan Sidles
	 * Author URI: http://sidl.es/
	 * LICENSE: GPL2
	 *
	 * This program is free software; you can redistribute it and/or modify
	 * it under the terms of the GNU General Public License, version 2, as
	 * published by the Free Software Foundation.
	 *
	 * This program is distributed in the hope that it will be useful,
	 * but WITHOUT ANY WARRANTY; without even the implied warranty of
	 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	 * GNU General Public License for more details.
	 *
	 * You should have received a copy of the GNU General Public License
	 * along with this program; if not, write to the Free Software
	 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
	 *
	 * @package WordPress
	 * @subpackage Digging_In
	 */


	/*
	 * Requires the Digging In classes that control the administration, view, and
	 * data manipulation.
	 */

	require_once( plugin_dir_path( __FILE__ ) . 'php/admin/class-ubc-di-admin.php' );
	require_once( plugin_dir_path( __FILE__ ) . 'php/view/class-ubc-di-view.php' );

	/*
	 * Instantiates the Digging In Classes.
	 */
	new UBC_DI_Admin();
	new UBC_DI_View();
	new UBC_DI_View_JSON();
