<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2010, Phoronix Media
	Copyright (C) 2010, Michael Larabel
	pts_PieChart.php: A pie chart object for pts_Graph

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation; either version 3 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program. If not, see <http://www.gnu.org/licenses/>.
*/

class pts_PieChart extends pts_Graph
{
	private $pie_sum = 0;

	public function __construct(&$result_object, &$result_file = null)
	{
		parent::__construct($result_object, $result_file);
		$this->graph_value_type = "ABSTRACT";
		$this->graph_hide_identifiers = false;
		$this->update_graph_dimensions($this->graph_attr_width, $this->graph_attr_height + 100);
	}
	protected function render_graph_pre_init()
	{
		$pie_slices = count($this->graph_identifiers);
		$this->pie_sum = 0;

		for($i = 0; $i < $pie_slices; $i++)
		{
			$this->pie_sum += $this->graph_data[0][$i];
		}

		if($pie_slices > 8)
		{
			$this->update_graph_dimensions($this->graph_attr_width, $this->graph_attr_height + 100);
		}

	}
	protected function render_graph_identifiers()
	{
		$key_strings = array();

		foreach(array_keys($this->graph_identifiers) as $i)
		{
			$percent = pts_math::set_precision($this->graph_data[0][$i] / $this->pie_sum * 100, 2);
			array_push($key_strings, '[' . $percent . "%]");
			//array_push($key_strings, '[' . $this->graph_data[0][$i] . ' / ' . $percent . "%]");
		}

		$key_count = count($key_strings);
		$key_item_width = 18 + $this->text_string_width(pts_strings::find_longest_string($this->graph_identifiers), $this->graph_font, $this->graph_font_size_key);
		$key_item_width_value = 12 + $this->text_string_width(pts_strings::find_longest_string($key_strings), $this->graph_font, $this->graph_font_size_key);
		$keys_per_line = floor(($this->graph_left_end - $this->graph_left_start - 14) / ($key_item_width + $key_item_width_value));

		if($keys_per_line < 1)
		{
			$keys_per_line = 1;
		}

		$key_line_height = 14;
		$this->graph_top_start += 12;
		$c_y = $this->graph_top_start - $key_line_height - 5;
		//$this->reset_paint_index();

		for($i = 0; $i < $key_count; $i++)
		{
			$this_color = $this->get_paint_color($i);

			if($i > 0 && $i % $keys_per_line == 0)
			{
				$c_y += $key_line_height;
				$this->graph_top_start += $key_line_height;
			}

			$c_x = $this->graph_left_start + 13 + (($key_item_width + $key_item_width_value) * ($i % $keys_per_line));

			$this->svg_dom->add_element('rect', array('x' => ($c_x - 13), 'y' => ($c_y - 5), 'width' => 10, 'height' => 10, 'fill' => $this_color, 'stroke' => $this->graph_color_notches, 'stroke-width' => 1));
			$this->svg_dom->add_text_element($this->graph_identifiers[$i], array('x' => $c_x, 'y' => $c_y, 'font-size' => $this->graph_font_size_key, 'fill' => $this_color, 'text-anchor' => 'start', 'dominant-baseline' => 'middle'));
			$this->svg_dom->add_text_element($key_strings[$i], array('x' => ($c_x + $key_item_width + 30), 'y' => $c_y, 'font-size' => $this->graph_font_size_key, 'fill' => $this_color, 'text-anchor' => 'end', 'dominant-baseline' => 'middle'));
		}
	}
	public function render_graph_finish()
	{
		$this->render_graph_identifiers();
		$this->render_graph_heading(false);

		$pie_slices = count($this->graph_identifiers);
		$radius = min(($this->graph_attr_height - $this->graph_top_start - $this->graph_top_end_opp), ($this->graph_attr_width - $this->graph_left_start - $this->graph_left_end_opp)) / 2;
		$center_x = ($this->graph_attr_width / 2);
		$center_y = $this->graph_top_start + (($this->graph_attr_height - $this->graph_top_start - $this->graph_top_end_opp) / 2);
		$offset_percent = 0;

		for($i = 0; $i < $pie_slices; $i++)
		{
			$percent = pts_math::set_precision($this->graph_data[0][$i] / $this->pie_sum, 3);

			$this->svg_dom->draw_svg_arc($center_x, $center_y, $radius, $offset_percent, $percent, array('fill' => $this->get_paint_color($i), 'stroke' => $this->graph_color_border, 'stroke-width' => 2, 'xlink:title' =>  $this->graph_identifiers[$i] . ': ' . $this->graph_data[0][$i]));
			$offset_percent += $percent;
		}

		if(!empty($this->graph_watermark_text))
		{
			$this->svg_dom->add_text_element($this->graph_watermark_text, array('x' => ($this->graph_attr_width / 2), 'y' => ($this->graph_attr_height - 15), 'font-size' => 10, 'fill' => $this->graph_color_text, 'text-anchor' => 'middle', 'dominant-baseline' => 'text-before-edge'));
		}

		return $this->return_graph_image(100);
	}
}

?>