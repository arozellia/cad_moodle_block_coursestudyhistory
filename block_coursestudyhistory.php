<?php
declare( strict_types=1 );

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
 * Block coursestudyhistory is defined here.
 *
 * @package     block_coursestudyhistory
 * @copyright   2019 Tia <tia@techiasolutions.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * coursestudyhistory block.
 *
 * @package    block_coursestudyhistory
 * @copyright  2019 Tia <tia@techiasolutions.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_coursestudyhistory extends block_base {
	/**
	 * @var string
	 */
	public $content;
	
	private $percentCompleted;
	/**
	 * @var string
	 */
	public $title;
	
	/**
	 * Returns completion percent.
	 * @return float
	 *
	 */
	private function get_completion_data(): float {
		return (float)$this->percentCompleted;
	}
	
	/**
	 * Returns the block contents.
	 *
	 * @return stdClass The block contents.
	 * @throws moodle_exception
	 */
	public function get_content() {
		
		if ($this->content !== null) {
			return $this->content;
		}
		
		if (empty($this->instance)) {
			$this->content = '';
			return $this->content;
		}
		
		$this->content = new stdClass();
		$this->content->items = [];
		$this->content->icons = [];
		$this->content->footer = '';
		$this->set_completion_data();
		
		if (!empty($this->config->text)) {
			$this->content->text = $this->config->text;
		} else {
			$thisPercentCompleted = (float)$this->get_completion_data();
			
			if (empty($thisPercentCompleted) || $thisPercentCompleted === 0) {
				$thisPercentCompleted = .5;
			} 
			
			$text = '<div class="progress">
					  <div class="progress-bar progress-bar-info progress-bar-striped" role="progressbar"
					  aria-valuenow="';
			$text .= $thisPercentCompleted;
			$text .= '" aria-valuemin="0" aria-valuemax="100" style="width:';
			$text .= $thisPercentCompleted;
			$text .= '%">';
			$text .= $thisPercentCompleted . '%';
			$text .= '</div></div>';
			$text .= '<div  class="text-center"><a href = "'
				. new moodle_url('/report/coursestudyhistory/')
				. '"> View progress </a></div>';
			
			$this->content->text = $text;
		}
		
		return $this->content;
	}
	
	/**
	 * Defines configuration data.
	 *
	 * The function is called immediately after init().
	 *
	 * @throws coding_exception
	 */
	public function specialization() {
		
		// Load user defined title and make sure it's never empty.
		if (empty($this->config->title)) {
			$this->title = get_string('usertitle', 'block_coursestudyhistory');
		} else {
			$this->title = $this->config->title;
		}
	}
	
	/**
	 * Allow multiple instances in a single course?
	 *
	 * @return bool True if multiple instances are allowed, false otherwise.
	 */
	public function instance_allow_multiple() {
		return true;
	}
	
	function _self_test() {
		return true;
	}
	
	function applicable_formats() {
		return [
			'all' => true,
			'mod' => true,
		];
	}
	
	/**
	 * Initializes class member variables.
	 *
	 * @throws coding_exception
	 */
	public function init() {
		// Needed by Moodle to differentiate between blocks.
		$this->title = get_string('pluginname', 'block_coursestudyhistory');
	}
	
	/**
	 * Returns the number of courses completed based on certificate issues.
	 *
	 * @return float Completion percent data.
	 * @throws dml_exception
	 */
	private function set_completion_data(): float {
		global $USER, $DB;
		
		if ($this->percentCompleted !== null) {
			return $this->percentCompleted;
		}
		
		$sql = "select ifnull(round((sum(case when cc.timecompleted is not null then 1 else 0 end )/ count(c.id)) * 100),0) as percentcompleted
					, count(c.id) as coursestocomplete
				    , sum(case when cc.timecompleted is not null then 1 else 0 end ) as coursescompleted
				from {role_assignments} ra
				inner join {context} ctx on ctx.id = ra.contextid and ctx.contextlevel = 50
				inner join {course} c on c.id = ctx.instanceid and c.visible = 1
				inner join {course_completions} cc on cc.course = c.id and cc.userid = ra.userid
				where ra.userid = ?
				and ra.roleid = 5
				group by ra.userid";
		$result = $DB->get_record_sql($sql, [$USER->id]);
		
		if (!$result) {
			$result = new stdClass();
			$result->percentcompleted = 0;
			$result->coursestocomplete = 0;
			$result->coursescompleted = 0;
		}
		
		$this->percentCompleted = (float)$result->percentcompleted;
		
		return $this->percentCompleted;
	}
}
