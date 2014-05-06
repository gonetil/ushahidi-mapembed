<?php defined('SYSPATH') or die('No direct script access.');
/**
 * This is the controller for the main site.
 *
 * PHP version 5
 * LICENSE: This source file is subject to LGPL license
 * that is available through the world-wide-web at the following URI:
 * http://www.gnu.org/copyleft/lesser.html
 * @author     Ushahidi Team <team@ushahidi.com>
 * @package    Ushahidi - http://source.ushahididev.com
 * @copyright  Ushahidi - http://www.ushahidi.com
 * @license    http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License (LGPL)
 */
class Mapembed_Controller extends Main_Controller {
	
    public function index($width=FALSE,$height=FALSE)
    {
        // Cacheable Main Controller
		$this->is_cachable = TRUE;
		
		$this->template->header= View::factory('embedmap/header'); //new View('header');
		
		$this->template->footer= new View('embedmap/footer');
		
        $this->template->content = new View('embedmap/layout');	
		if(!$width or !$height)
		{
			$width= '715'; $height='630';
		}
		$this->template->header->width=$width;
		$this->template->header->height=$height;
		// Map and Slider Blocks
		$div_map = new View('embedmap/map');
		$div_timeline = new View('embedmap/timeline');

		// Filter::map_main - Modify Main Map Block
		Event::run('ushahidi_filter.map_main', $div_map);

		// Filter::map_timeline - Modify Main Map Block
		Event::run('ushahidi_filter.map_timeline', $div_timeline);

		$this->template->content->div_map = $div_map;
		$this->template->content->div_timeline = $div_timeline;

		// Check if there is a site message
		$this->template->content->site_message = '';
		$site_message = trim(Kohana::config('settings.site_message'));
		if($site_message != '')
		{
			// Send the site message to both the header and the main content body
			//   so a theme can utilize it in either spot.
			$this->template->content->site_message = $site_message;
			$this->template->header->site_message = $site_message;
		}

		// Get locale
		$l = Kohana::config('locale.language.0');

        
	

		// Get all active Layers (KMZ/KML)
		$layers = array();
		$config_layers = Kohana::config('map.layers'); // use config/map layers if set
		if ($config_layers == $layers) {
			foreach (ORM::factory('layer')
					  ->where('layer_visible', 1)
					  ->find_all() as $layer)
			{
				$layers[$layer->id] = array($layer->layer_name, $layer->layer_color,
					$layer->layer_url, $layer->layer_file);
			}
		}
		else
		{
			$layers = $config_layers;
		}
		$this->template->content->layers = $layers;

		// Get Default Color
		$this->template->content->default_map_all = Kohana::config('settings.default_map_all');		

        // Get The START, END and Incident Dates
        $startDate = "";
		$endDate = "";
		$display_startDate = 0;
		$display_endDate = 0;

		$db = new Database();
		
        // Next, Get the Range of Years
		$query = $db->query('SELECT DATE_FORMAT(incident_date, \'%Y-%c\') AS dates '
		    . 'FROM '.$this->table_prefix.'incident '
		    . 'WHERE incident_active = 1 '
		    . 'GROUP BY DATE_FORMAT(incident_date, \'%Y-%c\') '
		    . 'ORDER BY incident_date');

		$first_year = date('Y');
		$last_year = date('Y');
		$first_month = 1;
		$last_month = 12;
		$i = 0;

		foreach ($query as $data)
		{
			$date = explode('-',$data->dates);

			$year = $date[0];
			$month = $date[1];

			// Set first year
			if ($i == 0)
			{
				$first_year = $year;
				$first_month = $month;
			}

			// Set last dates
			$last_year = $year;
			$last_month = $month;

			$i++;
		}

		$show_year = $first_year;
		$selected_start_flag = TRUE;

		while ($show_year <= $last_year)
		{
			$startDate .= "<optgroup label=\"".$show_year."\">";

			$s_m = 1;
			if ($show_year == $first_year)
			{
				// If we are showing the first year, the starting month may not be January
				$s_m = $first_month;
			}

			$l_m = 12;
			if ($show_year == $last_year)
			{
				// If we are showing the last year, the ending month may not be December
				$l_m = $last_month;
			}

			for ( $i=$s_m; $i <= $l_m; $i++ )
			{
				if ($i < 10 )
				{
					// All months need to be two digits
					$i = "0".$i;
				}
				$startDate .= "<option value=\"".strtotime($show_year."-".$i."-01")."\"";
				if($selected_start_flag == TRUE)
				{
					$display_startDate = strtotime($show_year."-".$i."-01");
					$startDate .= " selected=\"selected\" ";
					$selected_start_flag = FALSE;
				}
				$startDate .= ">".date('M', mktime(0,0,0,$i,1))." ".$show_year."</option>";
			}
			$startDate .= "</optgroup>";

			$endDate .= "<optgroup label=\"".$show_year."\">";
			
			for ( $i=$s_m; $i <= $l_m; $i++ )
			{
				if ( $i < 10 )
				{
					// All months need to be two digits
					$i = "0".$i;
				}
				$endDate .= "<option value=\"".strtotime($show_year."-".$i."-".date('t', mktime(0,0,0,$i,1))." 23:59:59")."\"";

                if($i == $l_m AND $show_year == $last_year)
				{
					$display_endDate = strtotime($show_year."-".$i."-".date('t', mktime(0,0,0,$i,1))." 23:59:59");
					$endDate .= " selected=\"selected\" ";
				}
				$endDate .= ">".date('M', mktime(0,0,0,$i,1))." ".$show_year."</option>";
			}
			
			$endDate .= "</optgroup>";

			// Show next year
			$show_year++;
		}

		Event::run('ushahidi_filter.active_startDate', $display_startDate);
		Event::run('ushahidi_filter.active_endDate', $display_endDate);
		Event::run('ushahidi_filter.startDate', $startDate);
		Event::run('ushahidi_filter.endDate', $endDate);

		$this->template->content->div_timeline->startDate = $startDate;
		$this->template->content->div_timeline->endDate = $endDate;

		// Javascript Header
		$this->themes->map_enabled = TRUE;
		$this->themes->slider_enabled = TRUE;
		
		if (Kohana::config('settings.enable_timeline'))
		{
			$this->themes->timeline_enabled = TRUE;
		}

		// Map Settings
		$marker_radius = Kohana::config('map.marker_radius');
		$marker_opacity = Kohana::config('map.marker_opacity');
		$marker_stroke_width = Kohana::config('map.marker_stroke_width');
		$marker_stroke_opacity = Kohana::config('map.marker_stroke_opacity');

		$this->themes->js = new View('embedmap/main_js');

		$this->themes->js->marker_radius = ($marker_radius >=1 AND $marker_radius <= 10 )
		    ? $marker_radius
		    : 5;

		$this->themes->js->marker_opacity = ($marker_opacity >=1 AND $marker_opacity <= 10 )
		    ? $marker_opacity * 0.1
		    : 0.9;

		$this->themes->js->marker_stroke_width = ($marker_stroke_width >=1 AND $marker_stroke_width <= 5)
		    ? $marker_stroke_width
		    : 2;

		$this->themes->js->marker_stroke_opacity = ($marker_stroke_opacity >=1 AND $marker_stroke_opacity <= 10)
		    ? $marker_stroke_opacity * 0.1
		    : 0.9;


		$this->themes->js->active_startDate = $display_startDate;
		$this->themes->js->active_endDate = $display_endDate;

		$this->themes->js->blocks_per_row = Kohana::config('settings.blocks_per_row');
	}
	
} // End Controller
