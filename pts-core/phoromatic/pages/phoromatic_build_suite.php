<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2015, Phoronix Media
	Copyright (C) 2015, Michael Larabel

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


class phoromatic_build_suite implements pts_webui_interface
{
	public static function page_title()
	{
		return 'Build Custom Test Suite';
	}
	public static function page_header()
	{
		return null;
	}
	public static function preload($PAGE)
	{
		return true;
	}
	public static function render_page_process($PATH)
	{
		if(isset($_POST['suite_title']))
		{
		//	echo '<pre>';
		//	var_dump($_POST);
		//	echo '</pre>';

			if(strlen($_POST['suite_title']) < 3)
			{
				echo '<h2>Suite title must be at least three characters.</h2>';
			}

			//echo 'TEST SUITE: ' . $_POST['suite_title'] . '<br />';
			//echo 'TEST SUITE: ' . $_POST['suite_description'] . '<br />';
			$tests = array();

			foreach($_POST['test_add'] as $i => $test_identifier)
			{
				$test_prefix = $_POST['test_prefix'][$i];
				$args = array();
				$args_name = array();

				foreach($_POST as $i => $v)
				{
					if(strpos($i, $test_prefix) !== false && substr($i, -9) != '_selected')
					{
						if(strpos($v, '||') !== false)
						{
							$opts = explode('||', $v);
							$a = array();
							$d = array();
							foreach($opts as $opt)
							{
								$t = explode('::', $opt);
								array_push($a, $t[1]);
								array_push($d, $t[0]);
							}
							array_push($args, $a);
							array_push($args_name, $d);
						}
						else
						{
							array_push($args, array($v));
							array_push($args_name, array($_POST[$i . '_selected']));
						}
					}
				}

				$test_args = array();
				$test_args_description = array();
				pts_test_run_options::compute_all_combinations($test_args, null, $args, 0);
				pts_test_run_options::compute_all_combinations($test_args_description, null, $args_name, 0, ' - ');

				foreach(array_keys($test_args) as $i)
				{
					array_push($tests, array('test' => $test_identifier, 'description' => $test_args_description[$i], 'args' => $test_args[$i]));
				}
			}

			if(count($tests) < 1)
			{
				echo '<h2>You must add at least one test to the suite.</h2>';
			}

			$suite_writer = new pts_test_suite_writer();
			$version_bump = 0;

			do
			{
				$suite_version = '1.' . $version_bump . '.0';
				$suite_id = $suite_writer->clean_save_name_string($_POST['suite_title']) . '-' . $suite_version;
				$suite_dir = phoromatic_server::phoromatic_account_suite_path($_SESSION['AccountID'], $suite_id);
				$version_bump++;
			}
			while(is_dir($suite_dir));
			pts_file_io::mkdir($suite_dir);
			$save_to = $suite_dir . '/suite-definition.xml';

			$suite_writer->add_suite_information($_POST['suite_title'], $suite_version,  $_SESSION['UserName'], 'System', $_POST['suite_description']);
			foreach($tests as $m)
			{
				$suite_writer->add_to_suite($m['test'], $m['args'], $m['description']);
			}

			$suite_writer->save_xml($save_to);
			echo '<h2>Saved As ' . $suite_id . '</h2>';
		}
		echo phoromatic_webui_header_logged_in();
		$main = '<h1>Local Suites</h1><p>Find already created local test suites by your account/group via the <a href="/?local_suites">local suites</a> page.</p>';


		if(!PHOROMATIC_USER_IS_VIEWER)
		{
			$main .= '<h1>Build Suite</h1><p>A test suite in the realm of the Phoronix Test Suite, OpenBenchmarking.org, and Phoromatic is <strong>a collection of test profiles with predefined settings</strong>. Establishing a test suite makes it easy to run repetitive testing on the same set of test profiles by simply referencing the test suite name.</p>';
			$main .= '<form action="' . $_SERVER['REQUEST_URI'] . '" name="build_suite" id="build_suite" method="post" onsubmit="return validate_suite();">
			<h3>Title:</h3>
			<p><input type="text" name="suite_title" /></p>
			<h3>Description:</h3>
			<p><textarea name="suite_description" id="suite_description" cols="60" rows="2"></textarea></p>
			<h3>Tests In Schedule:</h3>
			<p><div id="test_details"></div></p>
			<h3>Add Another Test</h3>';
			$main .= '<select name="add_to_suite_select_test" id="add_to_suite_select_test" onchange="phoromatic_build_suite_test_details();">';
			foreach(pts_openbenchmarking::available_tests() as $test)
			{
				$main .= '<option value="' . $test . '">' . $test . '</option>';
			}
			$main .= '</select>';
			$main .= '<p align="right"><input name="submit" value="Create Suite" type="submit" onclick="return pts_rmm_validate_suite();" /></p>';
		}

		echo '<div id="pts_phoromatic_main_area">' . $main . '</div>';
		echo phoromatic_webui_footer();
	}
}

?>
