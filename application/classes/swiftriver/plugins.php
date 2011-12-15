<?php defined('SYSPATH') OR die('No direct access allowed.');
/**
 * Plugins Helper Class
 *
 * PHP version 5
 * LICENSE: This source file is subject to GPLv3 license 
 * that is available through the world-wide-web at the following URI:
 * http://www.gnu.org/copyleft/gpl.html
 * @author     Ushahidi Team <team@ushahidi.com> 
 * @package	   SwiftRiver - http://github.com/ushahidi/Swiftriver_v2
 * @category Helpers
 * @copyright  Ushahidi - http://www.ushahidi.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License v3 (GPLv3) 
 */
class Swiftriver_Plugins {
	
	/**
	 * Load all active plugins into the Kohana system
	 *
	 * @return	void
	 */
	public static function load()
	{
		$active_plugins = ORM::factory("plugin")
			->where("plugin_enabled", "=", 1)
			->find_all();
		
		$plugin_entries = array();
		foreach ($active_plugins as $plugin)
		{
			$plugin_entries[$plugin->plugin_path] = PLUGINPATH.$plugin->plugin_path;
		}
		
		// Add the plugin entries to the list of Kohana modules
		Kohana::modules(Kohana::modules() + $plugin_entries);
	}
	
	/**
	 * Find and load all the plugin config files regardless of whether
	 * they've been loaded by the Kohana system or not
	 *
	 * @return	array $configs
	 */
	public static function load_configs()
	{
		$d = @dir(PLUGINPATH); 
		
		// Trap for errors
		if ( ! $d)
		{
			Kohana::$log->add(Log::ERROR, "Failed opening directory :dir for reading", 
			    array(':dir' => PLUGINPATH));
			
			return FALSE;
		}
		
		$directories = array();
		$configs = array();
		while (($entry = $d->read()) !== FALSE )
		{
			// Don't include hidden folders (only applies to UNIX-like systems)
			if ($entry[0] == '.') continue;
			{
				$directories[$entry] = FALSE;
			}
		}
		
		// Cycle through each plugin directory and load config files
		foreach ($directories as $dir => $found)
		{
			$file = PLUGINPATH.$dir.'/config/plugin.php';
			if (file_exists($file))
			{
				$config_array = include $file;
				if (is_array($config_array))
				{
					$configs = array_merge($configs, $config_array);
				}
			}
		}
		
		return $configs;
	}

	/**
	 * Find all the active plugins with channel services
	 *
	 * @return	array $channels
	 */
	public static function channels()
	{
		$channels = array();
		
		// Load the plugin configs and fetch only those that 
		// have the channel property set to TRUE
		$plugins = Kohana::$config->load('plugin');
		foreach($plugins as $key => $plugin)
		{
			if (isset($plugin['channel']) AND $plugin['channel'] == TRUE)
			{
				$active = ORM::factory('plugin')
					->where('plugin_path', '=', $key)
					->where('plugin_enabled', '=', '1')
					->find();
					
				if ($active->loaded())
				{
					$channel_config = array();
					
					// Validate the channel configuration
					if ( ! self::_validate_channel_plugin_config($plugin, $channel_config))
						continue;
					
					// Set the plugin name
					$channel_config['name'] = $plugin['name'];
					
					$channels[$key] = $channel_config;
					
				}
			}
		}

		return $channels;
	}
	
	/**
	 * Validates the configuration of a channel plugin
	 *
	 * @param array $plugin Plugin configuration
	 * @param array $channel_config Channel config parameters to store
	 * @return bool TRUE if the config for the channel plugin is in order, 
	 *     FALSE otherwise
	 */
	private static function _validate_channel_plugin_config($plugin, array & $channel_config)
	{
		// Get the plugin name
		$plugin_name = $plugin['name'];
		
		// 
		// Step 1. Check for grouping
		// 
		if (isset($plugin['channel_group_options']))
		{
			if ($plugin['channel_group_options'])
			{
				// Validation for 'channel_group_name'
				if (isset($plugin['channel_group_name']) AND is_array($plugin['channel_group_name']))
				{
					$channel_config['group'] = array();
					foreach ($plugin['channel_group_name'] as $k => $v)
					{
						$channel_config['group']['key'] = $k;
						$channel_config['group']['label'] = $v;
					}
				}
				else
				{
					// Log the config error
					Kohana::$log->add(Log::ERROR, ":plugin plugin config error. "
					    ."'channel_group_name' MUST be specified as a key=>value array.", 
					    array(':plugin' => $plugin_name));
				
					return FALSE;
				}
			}
			else
			{
				// Log the config error
				Kohana::$log->add(Log::ERROR, ":plugin config error. 'channel_group_options MUST be set to TRUE'", 
				    array(':plugin' => $plugin_name));
				
				return FALSE;
			}
		}
		
		// 
		// Step 2. Check for channel options
		// 
		if (isset($plugin['channel_options']) AND is_array($plugin['channel_options']))
		{
			$channel_config['options'] = $plugin['channel_options'];
		}
		else
		{
			// Log the config error
			Kohana::$log->add(Log::ERROR, ":plugin plugin config error. "
			    . "'channel_options' MUST be an array.", array(':plugin' => $plugin_name));
			
			return FALSE;
		}
		
		// Validation succeeded
		Kohana::$log->add(Log::INFO, "Validation succeeded for the :plugin plugin",
		    array(':plugin' => $plugin_name));
		
		return TRUE;
		
	}


	/**
	 * Determine if a plugin has settings options
	 *
	 * @return bool 
	 */
	public static function has_settings($plugin = NULL)
	{
		if ($plugin)
		{
			$all_plugin_configs = Kohana::$config->load('plugin');
			$plugin_configs = $all_plugin_configs->get($plugin);
			if ( is_array($plugin_configs) 
				AND isset($plugin_configs['settings']) 
				AND $plugin_configs['settings'] == TRUE )
			{
				return TRUE;
			}
			else
			{
				return FALSE;
			}
		}
		else
		{
			return FALSE;
		}
	}

	/**
	 * Determine if a plugin has feed service options
	 *
	 * @return bool 
	 */
	public static function has_service($plugin = NULL)
	{
		if ($plugin)
		{
			$all_plugin_configs = Kohana::$config->load('plugin');
			$plugin_configs = $all_plugin_configs->get($plugin);
			if ( is_array($plugin_configs) 
				AND isset($plugin_configs['service']) 
				AND $plugin_configs['service'] == TRUE )
			{
				return TRUE;
			}
			else
			{
				return FALSE;
			}
		}
		else
		{
			return FALSE;
		}
	}
	
	/**
	 * Helper function that generates the HTML for a channel option item
	 *
	 * @param array $option Channel option item
	 * @param string $option_name Name to assign the HTML element
	 * @param string $value Value to display in the element
	 * @return string
	 */
	public static function get_channel_option_html($option, $option_name, $value = '')
	{
		$ui_html = "";
		if ($option['type'] == 'text' OR $option['type'] == 'password')
		{
			$ui_html .= "<input class=\"filter-option\" type=\"".$option['type']."\""
			    ."name=\"".$option_name."\" value=\"".$value."\">";
		}
		elseif ($option['type'] == 'select')
		{
			$ui_html .= "<select class=\"filter-option\" name=\"".$option_name."\">";
			foreach ($option['values'] as $value_item)
			{
				$selected = ($value == $value_item) ? "selected=\"selected\"" : "";
				
				$ui_html .= "<option value=\"".$value_item."\" ".$selected.">".$value_item."</option>";
			}
			
			$ui_html .= "</select>";
		}
		
		return $ui_html;
	}
	
}
