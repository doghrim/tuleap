<?php
/* 
 * Copyright (c) STMicroelectronics, 2006. All Rights Reserved.
 *
 * Originally written by Mahmoud MAALEJ, 2006. STMicroelectronics.
 *
 * This file is a part of CodeX.
 *
 * CodeX is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * CodeX is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with CodeX; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

require_once('GraphOnTrackers_Chart.class.php');
require_once(dirname(__FILE__).'/../data-transformation/GraphOnTrackers_Chart_GanttDataBuilder.class.php');
require_once(dirname(__FILE__).'/../graphic-library/gantt_engine.class.php');
require_once('common/html/HTML_Element_Selectbox_TrackerFields_Selectboxes.class.php');
require_once('common/html/HTML_Element_Selectbox_TrackerFields_Dates.class.php');
require_once('common/html/HTML_Element_Selectbox_TrackerFields_Numerics.class.php');
require_once('common/html/HTML_Element_Input_Date.class.php');
require_once('common/html/HTML_Element_Selectbox_Scale.class.php');
require_once('common/html/HTML_Element_Selectbox_TrackerFields_Texts.class.php');

class GraphOnTrackers_Chart_Gantt extends GraphOnTrackers_Chart {
    
    protected $field_start;
    protected $field_due;
    protected $field_finish;
    protected $field_percentage;
    protected $field_hintleft;
    protected $field_hintcenter;
    protected $field_hintright;
    protected $field_righttext;
    protected $scale;
    protected $as_of_date;
    protected $summary;
    
    /**
     * class constructor
     *
     */    
    function __construct($graphic_report, $id, $rank, $title, $description, $width, $height) {
        parent::__construct($graphic_report, $id, $rank, $title, $description, $width, $height);
        $sql = "SELECT * FROM plugin_graphontrackers_gantt_chart WHERE id = ". db_ei($id);
        $res = db_query($sql);
        $arr = db_fetch_array($res);
        $this->field_start      = $arr['field_start'];
        $this->field_due        = $arr['field_due'];
        $this->field_finish     = $arr['field_finish'];
        $this->field_percentage = $arr['field_percentage'];
        $this->field_hintleft   = $arr['field_hintleft'];
        $this->field_hintcenter = $arr['field_hintcenter'];
        $this->field_hintright  = $arr['field_hintright'];
        $this->field_righttext  = $arr['field_righttext'];
        $this->scale            = $arr['scale'];
        $this->as_of_date       = $arr['as_of_date'];
        $this->summary          = $arr['summary'];
    }
    public static function create($graphic_report, $id, $rank, $title, $description, $width, $height) {
        $sql = sprintf("INSERT INTO plugin_graphontrackers_gantt_chart
                       (id,field_start, field_due, field_finish, field_percentage, field_hintleft, field_hintcenter, field_hintright, field_righttext, scale, as_of_date, summary)
                       VALUES(%d,'','','','','','','','','','','summary')",
                       db_ei($id));

        $res = db_query($sql);
        return db_affected_rows($res) ? new GraphOnTrackers_Chart_Gantt($graphic_report, $id, $rank, $title, $description, $width, $height) : null;
    }
    
    public function getField_start() { return $this->field_start; }
    public function setField_start($field_start) { return $this->field_start = $field_start; }
    public function getField_due() { return $this->field_due; }
    public function setField_due($field_due) { return $this->field_due = $field_due; }
    public function getField_finish() { return $this->field_finish; }
    public function setField_finish($field_finish) { return $this->field_finish = $field_finish; }
    public function getField_percentage() { return $this->field_percentage; }
    public function setField_percentage($field_percentage) { return $this->field_percentage = $field_percentage; }
    public function getField_hintleft() { return $this->field_hintleft; }
    public function setField_hintleft($field_hintleft) { return $this->field_hintleft = $field_hintleft; }
    public function getField_hintcenter() { return $this->field_hintcenter; }
    public function setField_hintcenter($field_hintcenter) { return $this->field_hintcenter = $field_hintcenter; }
    public function getField_hintright() { return $this->field_hintright; }
    public function setField_hintright($field_hintright) { return $this->field_hintright = $field_hintright; }
    public function getField_righttext() { return $this->field_righttext; }
    public function setField_righttext($field_righttext) { return $this->field_righttext = $field_righttext; }
    public function getScale() { return $this->scale; }
    public function setScale($scale) { return $this->scale = $scale; }
    public function getAs_of_date() { return $this->as_of_date; }
    public function setAs_of_date($as_of_date) { return $this->as_of_date = $as_of_date; }
    public function getSummary() { return $this->summary; }
    public function setSummary($summary) { return $this->summary = $summary; }
    public static function getDefaultHeight(){return 0; }
    public static function getDefaultWidth(){return 0;  }
    
    protected function getEngine() {
        return new gantt_engine();
    }
    protected function getChartDataBuilder($artifacts) {
        return new GraphOnTrackers_Chart_GanttDataBuilder($this,$artifacts);
    }
    public function delete() {
        $sql = "DELETE FROM plugin_graphontrackers_gantt_chart WHERE id = ". db_ei($this->id);
        $res = db_query($sql);
    }
    public function getProperties() {
    	
    	$parent_properties=parent::getProperties();
    	unset($parent_properties['dimensions']);
        return array_merge($parent_properties,
            array(
            	new HTML_Element_Columns(
                    new HTML_Element_Selectbox_TrackerFields_Dates($GLOBALS['Language']->getText('plugin_graphontrackers_gantt_property','gantt_field_start'), 'chart[field_start]', $this->getField_start()),
                    new HTML_Element_Selectbox_TrackerFields_Dates($GLOBALS['Language']->getText('plugin_graphontrackers_gantt_property','gantt_field_due'), 'chart[field_due]', $this->getField_due(), true),
                    new HTML_Element_Selectbox_TrackerFields_Dates($GLOBALS['Language']->getText('plugin_graphontrackers_gantt_property','gantt_field_finish'), 'chart[field_finish]', $this->getField_finish())
                ),
                new HTML_Element_Columns(
                    new HTML_Element_Selectbox_TrackerFields_Texts($GLOBALS['Language']->getText('plugin_graphontrackers_gantt_property','gantt_summary'), 'chart[summary]', $this->getSummary()),
                    new HTML_Element_Input_Date($GLOBALS['Language']->getText('plugin_graphontrackers_gantt_property','gantt_as_of_date'), 'chart[as_of_date]', $this->getAs_of_date()),
                    new HTML_Element_Selectbox_TrackerFields_Numerics($GLOBALS['Language']->getText('plugin_graphontrackers_gantt_property','gantt_field_percentage'), 'chart[field_percentage]', $this->getField_percentage(), true)

                ),

	            new HTML_Element_Columns(
	                new HTML_Element_Selectbox_TrackerFields_Selectboxes($GLOBALS['Language']->getText('plugin_graphontrackers_gantt_property','gantt_field_hintleft'), 'chart[field_hintleft]', $this->getField_hintleft(), true),
	                new HTML_Element_Selectbox_TrackerFields_Selectboxes($GLOBALS['Language']->getText('plugin_graphontrackers_gantt_property','gantt_field_hintcenter'), 'chart[field_hintcenter]', $this->getField_hintcenter(), true),
	                new HTML_Element_Selectbox_TrackerFields_Selectboxes($GLOBALS['Language']->getText('plugin_graphontrackers_gantt_property','gantt_field_hintright'), 'chart[field_hintright]', $this->getField_hintright(), true)
                ),

                new HTML_Element_Columns(
                    new HTML_Element_Selectbox_TrackerFields_Selectboxes($GLOBALS['Language']->getText('plugin_graphontrackers_gantt_property','gantt_field_righttext'), 'chart[field_righttext]', $this->getField_righttext(), true),
                    new HTML_Element_Selectbox_Scale($GLOBALS['Language']->getText('plugin_graphontrackers_gantt_property','gantt_scale'), 'chart[scale]', $this->getScale())
                ),
        ));
    }
    protected function updateSpecificProperties($row) {
        $db_update_needed = false;
        foreach(array('field_start', 'field_due', 'field_finish', 'field_percentage', 'field_hintleft', 'field_hintcenter', 'field_hintright', 'field_righttext', 'scale', 'as_of_date', 'summary') as $prop) {
            if (isset($row[$prop]) && $this->$prop != $row[$prop]) {
                if ($prop == 'as_of_date') {
                    $this->$prop = strtotime($row[$prop]);
                } else {
                    $this->$prop = $row[$prop];
                }
                $db_update_needed = true;
            }
        }
        if ($db_update_needed) {
            $sql = sprintf("UPDATE plugin_graphontrackers_gantt_chart SET
                            field_start      = '%s',
                            field_due        = '%s',
                            field_finish     = '%s',
                            field_percentage = '%s',
                            field_hintleft   = '%s',
                            field_hintcenter = '%s',
                            field_hintright  = '%s',
                            field_righttext  = '%s',
                            scale            = '%s',
                            as_of_date       = %d,
                            summary          = '%s'
                       WHERE id = %d",
                       db_es($this->field_start),
                       db_es($this->field_due),
                       db_es($this->field_finish),
                       db_es($this->field_percentage),
                       db_es($this->field_hintleft),
                       db_es($this->field_hintcenter),
                       db_es($this->field_hintright),
                       db_es($this->field_righttext),
                       db_es($this->scale),
                       db_es($this->as_of_date),
                       db_es($this->summary),
                       db_ei($this->id));
            $res = db_query($sql);
            return db_affected_rows($res);
        }
        return false;
    }
    
    function userCanVisualize(){	
    	
    	if($this->field_due){   	
	    	$artifact_field_due=new ArtifactField();
	    	$artifact_field_due->fetchData($GLOBALS['ath']->getID(),$this->field_due);
	    	if(!$artifact_field_due->userCanRead($GLOBALS['group_id'],$GLOBALS['ath']->getID(),user_getid())){
    			return false;
    		}
    	}
    	if($this->field_percentage){
	    	$artifact_field_percentage=new ArtifactField();
	    	$artifact_field_percentage->fetchData($GLOBALS['ath']->getID(),$this->field_percentage);
	    	if(!$artifact_field_percentage->userCanRead($GLOBALS['group_id'],$GLOBALS['ath']->getID(),user_getid())){
    			return false;
    		}
    	}
    	if($this->field_hintleft){
	    	$artifact_field_hintleft=new ArtifactField();
    		$artifact_field_hintleft->fetchData($GLOBALS['ath']->getID(),$this->field_hintleft);
	    	if(!$artifact_field_hintleft->userCanRead($GLOBALS['group_id'],$GLOBALS['ath']->getID(),user_getid())){
    			return false;
    		}
    	}
    	if($this->field_hintcenter){
	    	$artifact_field_hintcenter=new ArtifactField();
    		$artifact_field_hintcenter->fetchData($GLOBALS['ath']->getID(),$this->field_hintcenter);
	    	if(!$artifact_field_hintcenter->userCanRead($GLOBALS['group_id'],$GLOBALS['ath']->getID(),user_getid())){
    			return false;
    		}
    	}
    	if($this->field_hintright){
	    	$artifact_field_hintright=new ArtifactField();
    		$artifact_field_hintright->fetchData($GLOBALS['ath']->getID(),$this->field_hintright);
	    	if(!$artifact_field_hintright->userCanRead($GLOBALS['group_id'],$GLOBALS['ath']->getID(),user_getid())){
    			return false;
    		}
    	}
    	
    	if($this->field_righttext){
    		$artifact_field_fieldright=new ArtifactField();
    		$artifact_field_fieldright->fetchData($GLOBALS['ath']->getID(),$this->field_righttext);
	    	if(!$artifact_field_fieldright->userCanRead($GLOBALS['group_id'],$GLOBALS['ath']->getID(),user_getid())){
    			return false;
    		}
    	}
    	if($this->summary){	
	    	$artifact_field_summary=new ArtifactField();
    		$artifact_field_summary->fetchData($GLOBALS['ath']->getID(),$this->summary);
	    	if(!$artifact_field_summary->userCanRead($GLOBALS['group_id'],$GLOBALS['ath']->getID(),user_getid())){
    			return false;
    		}
    	}
    	
    	if($this->field_start){  		
	    	$artifact_field_start=new ArtifactField();
    		$artifact_field_start->fetchData($GLOBALS['ath']->getID(),$this->field_start);
	    	if(!$artifact_field_start->userCanRead($GLOBALS['group_id'],$GLOBALS['ath']->getID(),user_getid())){
    			return false;
    		}
    	}
    	
    	  if($this->field_finish){   		
	    	$artifact_field_finish=new ArtifactField();
    		$artifact_field_finish->fetchData($GLOBALS['ath']->getID(),$this->field_finish);
	    	if(!$artifact_field_finish->userCanRead($GLOBALS['group_id'],$GLOBALS['ath']->getID(),user_getid())){
    			return false;
    		}
    	}
    	return true;
    	
    	
    }
}
?>
