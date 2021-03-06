<?php
require_once('ITechTable.php');

class Helper extends ITechTable
{

    /**
     * retrieves a list of the prior education course subjects in the database
     *
     * @return array|null
     */
    public function getPriorEducationCourses() {
		$db = $this->dbfunc();
		return $db->fetchPairs($db->select()->from('lookup_prior_education_courses', array('id', 'course_name')));
    }

    public function addPriorEducationCourse($coursename) {
		$db = $this->dbfunc();
		$db->insert('lookup_prior_education_courses', array('course_name' => $coursename));
	}

	public function deletePriorEducationCourse($coursename) {
    	$db = $this->dbfunc();
    	$w = $db->quoteInto('course_name = ?', $coursename);
    	$db->delete('lookup_prior_education_courses', $w);
	}

    #################################
	#                               #
	#   COHORT SPECIFIC FUNCTIONS   #
	#                               #
	#################################

	public function getCohorts($all = false){
		if ($all){
			// RETURNS A LIST OF ALL COHORST ORDERED BY NAME
			$select = $this->dbfunc()->select()
				->from("cohort")
				->order('cohortname');
		} else {
			$ins = $this->getInstitutions(false);
			$_ins = array();
			foreach ($ins as $i){
				$_ins[] = $i['id'];
			}
			if (count ($_ins) == 0){
				$_ins = array('0');
			}
			$ins = $_ins;

			// RETURNS A LIST OF ALL COHORST ORDERED BY NAME
			$select = $this->dbfunc()->select()
				->from("cohort")
				->where('institutionid IN (' . implode(",", $ins) . ')')
				->order('cohortname');
		}
		$result = $this->dbfunc()->fetchAll($select);
		return $result;
	}
	
	//TA:#486
	public function getLicenses(){  
	        $select = $this->dbfunc()->select(array('licensename'))->distinct()
	        ->from("licenses")
	        ->order('licensename');
	    $result = $this->dbfunc()->fetchAll($select);
	    return $result;
	}

	public function getCohortStudents($cid, $tp = "all",$output = "result") {
		switch ($tp){
			case "all":
				$select = $this->dbfunc()->select()
					->from("link_student_cohort")
					->join(array("c" => "cohort"),
							"id_cohort = c.id")
					->where('id_cohort = ?', $cid);
			break;
			case "graduating":
				$select = $this->dbfunc()->select()
					->from("link_student_cohort")
					->join(array("c" => "cohort"),
							"id_cohort = c.id")
					->where("dropdate = '0000-00-00' OR dropdate = c.graddate")
					->where('id_cohort = ?', $cid);
			break;
			case "dropped":
				$select = $this->dbfunc()->select()
					->from("link_student_cohort")
					->join(array("c" => "cohort"),
							"id_cohort = c.id")
					->where("dropdate <> '0000-00-00'")
					->where("dropdate < c.graddate")
					->where('id_cohort = ?', $cid);
			break;
		}
		#die($select->__toString());
		$result = $this->dbfunc()->fetchAll($select);
		if ($output == "count"){
			return count($result);
		} else {
			return $result;
		}
	}

	######################################
	#                                    #
	#   INSTITUTION SPECIFIC FUNCTIONS   #
	#                                    #
	######################################

	public function getInstitutionStudents($sid, $tp = "all",$output = "result") {
		switch ($tp){
			case "all":
				$select = $this->dbfunc()->select()
					->from("link_student_cohort")
					->join(array("c" => "cohort"),
							"id_cohort = c.id")
					->where('institutionid = ?', $sid);
			break;
			case "graduating":
				$select = $this->dbfunc()->select()
					->from("link_student_cohort")
					->join(array("c" => "cohort"),
							"id_cohort = c.id")
					->where("dropdate = '0000-00-00' OR dropdate = c.graddate")
					->where('institutionid = ?', $sid);
			break;
			case "graduated":
				$select = $this->dbfunc()->select()
					->from("link_student_cohort")
					->join(array("c" => "cohort"),
							"id_cohort = c.id")
					->where("(dropdate is null and NOW() > c.graddate) OR (dropdate is not null AND dropdate > c.graddate AND NOW() > c.graddate)")
					->where('institutionid = ?', $sid);
			break;
			case "dropped":
				$select = $this->dbfunc()->select()
					->from("link_student_cohort")
					->join(array("c" => "cohort"),
							"id_cohort = c.id")
					->where("dropdate <> '0000-00-00'")
					->where("dropdate < c.graddate")
					->where('institutionid = ?', $sid);
			break;
		}
		#die($select->__toString());
		$result = $this->dbfunc()->fetchAll($select);
		if ($output == "count"){
			return count($result);
		} else {
			return $result;
		}
	}

	public function getUserInstitutions($uid,$full = true) {
		if ($full){
			$select = $this->dbfunc()->select()
				->from("link_user_institution")
				->where('userid = ?', $uid);
			$result = $this->dbfunc()->fetchAll($select);
		} else {
			$select = $this->dbfunc()->select()
				->from("link_user_institution")
				->where('userid = ?', $uid);
			$result = $this->dbfunc()->fetchAll($select);
			$trimmed = array();
			foreach ($result as $row){
				$trimmed[] = $row['institutionid'];
			}
			$result = $trimmed;
		}
		return $result;
	}

	public function getInstitutions($all = true) {
		if (!$all){
			$institutions = $this->getUserInstitutions($this->myid(),false);
			if ((is_array($institutions)) && (count($institutions) > 0)){
				$insids = implode(",", $institutions);
				$select = $this->dbfunc()->select()
					->from("institution")
					->where("id IN (" . $insids . ")")
					->order('institutionname');
			} else {
				$select = $this->dbfunc()->select()
					->from("institution")
					->order('institutionname');
			}
		} else {
			$select = $this->dbfunc()->select()
				->from("institution")
				->order('institutionname');
		}


		$result = $this->dbfunc()->fetchAll($select);
		return $result;
	}
	
    public function getUserPrograms($uid, $full = true)
    {
        if ($full) {
            $select = $this->dbfunc()
                ->select()
                ->from("link_user_cadres")
                ->where('userid = ?', $uid);
            $result = $this->dbfunc()->fetchAll($select);
        } else {
            $select = $this->dbfunc()
                ->select()
                ->from("link_user_cadres")
                ->where('userid = ?', $uid);
            $result = $this->dbfunc()->fetchAll($select);
            $trimmed = array();
            foreach ($result as $row) {
                $trimmed[] = $row['cadreid'];
            }
            $result = $trimmed;
        }
        return $result;
    }
    
    public function getUserAllowedInstitutionNames($uid) {
    
        $select = $this->dbfunc()
        ->select()
        ->from("link_user_institution")
        ->where('userid = ' . $uid);
    
        $result = $this->dbfunc()->fetchAll($select);
    
        if (count($result) == 0)    {
            $select = $this->dbfunc()
            ->select(array('ins.institutionname'))
            ->from(array('ins' => "institution"));
            //->joinLeft(array('lui' => "link_user_institution"), 'lui.institutionid = ins.id');
        }   else    {
            $select = $this->dbfunc()
            ->select(array('ins.institutionname'))
            ->from(array('ins' => "institution"))
            ->join(array('lui' => "link_user_institution"), 'lui.institutionid = ins.id')
            ->where('lui.userid = ' . $uid);
        }
    
        $result = $this->dbfunc()->fetchAll($select);
        return $result;
    }
    
    public function getUserAllowedCadreNames($uid)
    {
        // RETURNS A LIST OF ALL ACTIVE CADRES ORDERED BY CADRE NAME
        $select = $this->dbfunc()
        ->select()
        ->from("link_user_cadres")
        ->where('userid = ' . $uid);
    
        $result = $this->dbfunc()->fetchAll($select);
    
        if (count($result) == 0)    {
            $select = $this->dbfunc()
            ->select(array('cad.cadrename'))
            ->from(array('cad' => "cadres"));
            //->joinLeft(array('luc' => "link_user_cadres"), 'luc.cadreid = cad.id');
        }   else    {
            $select = $this->dbfunc()
            ->select(array('cad.cadrename'))
            ->from(array('cad' => "cadres"))
            ->join(array('luc' => "link_user_cadres"), 'luc.cadreid = cad.id')
            ->where('luc.userid = ' . $uid);
        }
    
        $result = $this->dbfunc()->fetchAll($select);
        return $result;
    }

    public function getPrograms($all = true)
    {
        if (! $all) {
            $institutions = $this->getUserPrograms($this->myid(), false);
            if ((is_array($programs)) && (count($programs) > 0)) {
                $insids = implode(",", $programs);
                $select = $this->dbfunc()
                    ->select()
                    ->from("cadres")
                    ->where("id IN (" . $insids . ")")
                    ->order('cadrename');
            } else {
                $select = $this->dbfunc()
                    ->select()
                    ->from("cadres")
                    ->order('cadrename');
            }
        } else {
            $select = $this->dbfunc()
                ->select()
                ->from("cadres")
                ->order('cadrename');
        }
        
        $result = $this->dbfunc()->fetchAll($select);
        return $result;
    }



	public function getInstitutionTypes() {
		$select = $this->dbfunc()->select()
			->from("lookup_institutiontype")
			->where('status = 1')
			->order('typename');
		$result = $this->dbfunc()->fetchAll($select);
		return $result;
	}

	public function getInstitutionsSelectedTypes($iid){
		$select = $this->dbfunc()->select()
			->from("link_institution_institutiontype",
					array("id_institutiontype"))
			->where('id_institution = ?',$iid);
#		die ("<br><br>" . $select->__toString());
		$result = $this->dbfunc()->fetchAll($select);
		return $result;
	}

	
	//TA:#254 count only active tutors
	public function getInstitutionTutorCount($iid) {
	    $select = $this->dbfunc()->select()
	    ->from("link_tutor_institution")
	    ->join(array("t" => "tutor"),
	        "link_tutor_institution.id_tutor = t.id",
	        array())
	    ->join(array("p" => "person"),
	        "p.id = t.personid",
	        array())
	    ->where('p.active="active" and p.is_deleted=0 and id_institution = ?',$iid);
	    $result = $this->dbfunc()->fetchAll($select);
	    return count($result);
	}

	######################################
	#                                    #
	#   NATIONALITY SPECIFIC FUNCTIONS   #
	#                                    #
	######################################

	public function getNationalities() {
		$select = $this->dbfunc()->select()
			->from("lookup_nationalities")
			->order('nationality');
		$result = $this->dbfunc()->fetchAll($select);
		return $result;
	}
	
	//TA:#504
	public function getRelationship() {
	    $select = $this->dbfunc()->select()
	    ->from("lookup_relationship")
	    ->order('relationship');
	    $result = $this->dbfunc()->fetchAll($select);
	    return $result;
	}

	###################################
	#                                 #
	#   LANGUAGE SPECIFIC FUNCTIONS   #
	#                                 #
	###################################

	public function getLanguages() {
		$select = $this->dbfunc()->select()
			->from("lookup_languages")
			->order('language');
		$result = $this->dbfunc()->fetchAll($select);
		return $result;
	}

	public function getTutorLanguages($tid){
		$select = $this->dbfunc()->select()
			->from("link_tutor_languages")
			->where("id_tutor = ?", $tid);
		$result = $this->dbfunc()->fetchAll($select);
		return $result;
	}

	###################################
	#                                 #
	#   FACILITY SPECIFIC FUNCTIONS   #
	#                                 #
	###################################

/*
	public function getFacilityTypes() {
		$select = $this->dbfunc()->select()
			->from("lookup_facilitytype")
			->order('facilitytypename');
		$result = $this->dgbfunc()->fetchAll($select);
		return $result;
	}
*/
	public function getFacilityTypes() {
		$select = $this->dbfunc()->select()
			->from("facility_type_option",array('id','facilitytypename' => 'facility_type_phrase'))
			->order('facility_type_phrase');
		$result = $this->dbfunc()->fetchAll($select);
		return $result;
	}
	
	//TA:#416
	public function getEmployeeDSDModels() {
	    $select = $this->dbfunc()->select()
	    ->from("employee_dsdmodel_option",array('id','dsdmodel' => 'employee_dsdmodel_phrase'))
	    ->order('employee_dsdmodel_phrase');
	    $result = $this->dbfunc()->fetchAll($select);
	    return $result;
	}
	
	//TA:#416
	public function getEmployeeDSDTeams() {
	    
	    $select = $this->dbfunc()->select()
	    ->from("employee_dsdteam_option",array('team_id' => 'id','dsdteam' => 'employee_dsdteam_phrase'))
	   ->joinLeft(array('ept' => "employee_partner_option_to_employee_team_option"), 'ept.employee_dsdteam_option_id = employee_dsdteam_option.id')
	   ->columns("ept.partner_id")
	    ->order('employee_dsdteam_phrase');
	    $result = $this->dbfunc()->fetchAll($select);
	    return $result;
	}

	public function getFacilities() {
		$select = $this->dbfunc()->select()
			->from("facility")
			->order('facility_name');
		$result = $this->dbfunc()->fetchAll($select);
		return $result;
	}
	
	###################################
	#                                 #
	#   MECHANISM SPECIFIC FUNCTIONS  #
	#                                 #
	###################################
	public function getSfmId($sfmArr) {
		$select = $this->dbfunc()->select()
		->from(array("sfm" => "subpartner_to_funder_to_mechanism"), array('id'))
		->where("subpartner_id = ?", $sfmArr[0])
		->where("partner_funder_option_id = ?", $sfmArr[1])
		->where("mechanism_option_id = ?", $sfmArr[2])
		->where("sfm.is_deleted = false");
		$result = $this->dbfunc()->fetchRow($select);
		return $result;
	}
	
	public function getPsfmId($psfmArr) {
		$select = $this->dbfunc()->select()
		->from(array("psfm" => "partner_to_subpartner_to_funder_to_mechanism"), array('id'))
		->where("partner_id = ?", $psfmArr[0])
		->where("subpartner_id = ?", $psfmArr[1])
		->where("partner_funder_option_id = ?", $psfmArr[2])
		->where("mechanism_option_id = ?", $psfmArr[3])
		->where("psfm.is_deleted = false");
		$result = $this->dbfunc()->fetchRow($select);
		return $result;
	}
	
	public function getPartner($pid) {
		$select = $this->dbfunc()->select()
		->from(array("p" => "partner"))
		->where("id = ?", $pid);
		$result = $this->dbfunc()->fetchAll($select);
		return $result;
	}
	
	public function getSubpartner($pid) {
		return ( $this->getPartner($pid) );
	}
	
	public function getFunder($pid) {
		$select = $this->dbfunc()->select()
		->from(array("po" => "partner_funder_option"))
		->where("id = ?", $pid);
		$result = $this->dbfunc()->fetchAll($select);
		return $result;
	}
	
	public function getMechanism($pid) {
		$select = $this->dbfunc()->select()
		->from(array("mo" => "mechanism_option"))
		->where("id = ?", $pid);
		$result = $this->dbfunc()->fetchAll($select);
		return $result;
	}
	
	# getPartnerSubpartner($pid)....
	public function getPartnerSubpartner($pid) {
		$select = $this->dbfunc()->select()->distinct()
		->from(array('psfm' => 'partner_to_subpartner_to_funder_to_mechanism'), array('partner_id', 'subpartner_id'))
		->join(array("p" => "partner"), "psfm.subpartner_id = p.id")
		->where("psfm.partner_id = ?", $pid)
		->where("psfm.is_deleted = false")
		->order('psfm.subpartner_id');
		$result = $this->dbfunc()->fetchAll($select);
		return $result;
	}
	
	public function getPartnerFunder($pid) {
		$select = $this->dbfunc()->select()->distinct()
		->from(array("psfm" => "partner_to_subpartner_to_funder_to_mechanism"), array('partner_id', 'subpartner_id', 'partner_funder_option_id'))
		->join(array("f" => "partner_funder_option"), "f.id = psfm.partner_funder_option_id")
		->where("partner_id = ?", $pid)
		->where("psfm.is_deleted = false")
		->order('psfm.subpartner_id', 'psfm.partner_funder_option_id');
		$result = $this->dbfunc()->fetchAll($select);
		return $result;
	}
	
	public function getPartnerMechanism($pid) {
		$select = $this->dbfunc()->select()->distinct()
		->from(array("psfm" => "partner_to_subpartner_to_funder_to_mechanism"), array('partner_id', 'subpartner_id', 'partner_funder_option_id', 'mechanism_option_id'))
		->join(array("m" => "mechanism_option"), "m.id = psfm.mechanism_option_id")
		->where("partner_id = ?", $pid)
		->where("psfm.is_deleted = false")
		->order('psfm.subpartner_id', 'psfm.partner_funder_option_id', 'psfm.mechanism_option_id');
		$result = $this->dbfunc()->fetchAll($select);
		return $result;
	}

	public function getEmployeeSubpartner($pid) {
		$select = $this->dbfunc()->select()->distinct()
		->from(array('epsfm' => 'employee_to_partner_to_subpartner_to_funder_to_mechanism'), array('employee_id', 'partner_id', 'subpartner_id'))
		->join(array("p" => "partner"), "p.id = epsfm.subpartner_id")
		->where("employee_id = ?", $pid)
		->where("epsfm.is_deleted = false")
		->order('epsfm.subpartner_id');
		$result = $this->dbfunc()->fetchAll($select);
		return $result;
	}
	
	public function getEmployeeFunder($pid) {
		$select = $this->dbfunc()->select()->distinct()
		->from(array("epsfm" => "employee_to_partner_to_subpartner_to_funder_to_mechanism"), array('employee_id', 'partner_id', 'subpartner_id', 'partner_funder_option_id'))
		->join(array("f" => "partner_funder_option"), "f.id = epsfm.partner_funder_option_id")
		->where("employee_id = ?", $pid)
		->where("epsfm.is_deleted = false")
		->order('epsfm.subpartner_id', 'epsfm.partner_funder_option_id');
		$result = $this->dbfunc()->fetchAll($select);
		return $result;
	}
	
	public function getEmployeeMechanism($pid) {
		$select = $this->dbfunc()->select()->distinct()
		->from(array("epsfm" => "employee_to_partner_to_subpartner_to_funder_to_mechanism"), array('employee_id', 'partner_id', 'subpartner_id', 'partner_funder_option_id', 'mechanism_option_id'))
		->join(array("m" => "mechanism_option"), "m.id = epsfm.mechanism_option_id")
		->where("employee_id = ?", $pid)
		->where("epsfm.is_deleted = false")
		->order('epsfm.subpartner_id', 'epsfm.partner_funder_option_id', 'epsfm.mechanism_option_id');
		$result = $this->dbfunc()->fetchAll($select);
		return $result;
	}
	
	public function getSfmSubPartner() {
		$select = $this->dbfunc()->select()->distinct()
		->from(array('sfm' => 'subpartner_to_funder_to_mechanism'), array('subpartner_id' ))
		->join(array("p" => "partner"), "subpartner_id = p.id")
		->where("sfm.is_deleted = false")
		->order('sfm.subpartner_id');
		$result = $this->dbfunc()->fetchAll($select);
		return $result;
	}
	
	public function getSfmFunder() {
		$select = $this->dbfunc()->select()->distinct()
		->from(array("sfm" => "subpartner_to_funder_to_mechanism"), array('subpartner_id', 'partner_funder_option_id'))
		->join(array("f" => "partner_funder_option"), "f.id = sfm.partner_funder_option_id")
		->where("sfm.is_deleted = false")
		->order('sfm.partner_funder_option_id');
		$result = $this->dbfunc()->fetchAll($select);
		return $result;
	}
	
	public function getSfmMechanism() {
		$select = $this->dbfunc()->select()->distinct()
		->from(array("sfm" => "subpartner_to_funder_to_mechanism"), array('subpartner_id', 'partner_funder_option_id', 'mechanism_option_id'))
		->join(array("m" => "mechanism_option"), "m.id = sfm.mechanism_option_id")
		->where("sfm.is_deleted = false")
		->order('sfm.mechanism_option_id');
		$result = $this->dbfunc()->fetchAll($select);
		return $result;
	}

	public function getSfmSubPartnerExclude($pid) {
		$select = $this->dbfunc()->select()->distinct()
		->from(array('sfm' => 'subpartner_to_funder_to_mechanism'), array('subpartner_id' ))
		->join(array("p" => "partner"), "subpartner_id = p.id")
		
		->where("sfm.id not in (select subpartner_to_funder_to_mechanism_id 
                 from partner_to_subpartner_to_funder_to_mechanism psfm
                 where psfm.is_deleted = false and psfm.partner_id = ?)", $pid)
		
        ->where("sfm.is_deleted = false")
		->order('sfm.subpartner_id');
		$result = $this->dbfunc()->fetchAll($select);
		return $result;
	}
	
	public function getSfmFunderExclude($pid) {
		$select = $this->dbfunc()->select()->distinct()
		->from(array("sfm" => "subpartner_to_funder_to_mechanism"), array('subpartner_id', 'partner_funder_option_id'))
		->join(array("f" => "partner_funder_option"), "f.id = sfm.partner_funder_option_id")
		
		->where("sfm.id not in (select subpartner_to_funder_to_mechanism_id
                 from partner_to_subpartner_to_funder_to_mechanism psfm
                 where psfm.is_deleted = false and psfm.partner_id = ?)", $pid)
		
        ->where("sfm.is_deleted = false")
		->order('sfm.partner_funder_option_id');
		$result = $this->dbfunc()->fetchAll($select);
		return $result;
	}
	
	public function getSfmMechanismExclude($pid) {
		$select = $this->dbfunc()->select()->distinct()
		->from(array("sfm" => "subpartner_to_funder_to_mechanism"), array('subpartner_id', 'partner_funder_option_id', 'mechanism_option_id'))
		->join(array("m" => "mechanism_option"), "m.id = sfm.mechanism_option_id")
		
		->where("sfm.id not in (select subpartner_to_funder_to_mechanism_id
                 from partner_to_subpartner_to_funder_to_mechanism psfm
                 where psfm.is_deleted = false and psfm.partner_id = ?)", $pid)
		
        ->where("sfm.is_deleted = false")
		->order('sfm.mechanism_option_id');
		$result = $this->dbfunc()->fetchAll($select);
		return $result;
	}
	
	//gnr psfm start
	public function getEmployee($pid) {
		$select = $this->dbfunc()->select()
		->from(array("e" => "employee"))
		->where("id = ?", $pid);
		$result = $this->dbfunc()->fetchAll($select);
		return $result;
	}
	
	/**
	 * get rows of the partner table that aren't already associated via mechanism with the employee id 
	 * @param integer $pid - employee id
	 * @return array of database rows
	 */
	
	public function getPsfmPartnerExclude($pid) {
		$select = $this->dbfunc()->select()->distinct()
		->from(array('psfm' => 'partner_to_subpartner_to_funder_to_mechanism'), array('partner_id' ))
		->join(array("p" => "partner"), "partner_id = p.id")
		->where("psfm.id not in (select partner_to_subpartner_to_funder_to_mechanism_id
                 from employee_to_partner_to_subpartner_to_funder_to_mechanism epsfm
                 where epsfm.is_deleted = false and epsfm.employee_id = ?)", $pid)
        ->where("psfm.is_deleted = false")
        ->order('psfm.partner_id');
		$result = $this->dbfunc()->fetchAll($select);
		return $result;
	}
	
	/**
	 * get entries from partner table based on subpartner id and not already associated via mechanism with employee id 
	 * 
	 * @param integer $eid - employee id
	 * @param integer $pid - partner id
	 * @return array of database rows
	 */
	public function getPsfmSubPartnerExclude($eid, $pid) {
		$select = $this->dbfunc()->select()->distinct()
		->from(array('psfm' => 'partner_to_subpartner_to_funder_to_mechanism'), array('partner_id', 'subpartner_id' ))
		->join(array("p" => "partner"), "subpartner_id = p.id")	
		->where("psfm.id not in (select partner_to_subpartner_to_funder_to_mechanism_id
                 from employee_to_partner_to_subpartner_to_funder_to_mechanism epsfm
                 where epsfm.is_deleted = false and epsfm.employee_id = ?)", $eid)
	    ->where("psfm.is_deleted = false")
	    ->where("psfm.partner_id = ?", $pid)
	    ->order('psfm.subpartner_id');
		$result = $this->dbfunc()->fetchAll($select);
		return $result;
	}
	
    /**
     * get rows from partner_funder_option table based on partner id and not already associated via mechanism with employee id
     * 
	 * @param integer $eid - employee id
	 * @param integer $pid - partner id
     * @return array of database rows
     */	
	public function getPsfmFunderExclude($eid, $pid) {
		$select = $this->dbfunc()->select()->distinct()
		->from(array("psfm" => "partner_to_subpartner_to_funder_to_mechanism"), array('partner_id', 'subpartner_id', 'partner_funder_option_id'))
		->join(array("f" => "partner_funder_option"), "f.id = psfm.partner_funder_option_id")
		->where("psfm.id not in (select partner_to_subpartner_to_funder_to_mechanism_id
                 from employee_to_partner_to_subpartner_to_funder_to_mechanism epsfm
                 where epsfm.is_deleted = false and epsfm.employee_id = ?)", $eid)
        ->where("psfm.is_deleted = false")
        ->where("psfm.partner_id = ?", $pid)
        ->order('psfm.partner_funder_option_id');
		$result = $this->dbfunc()->fetchAll($select);
		return $result;
	}
	
	/**
     * get rows from mechanism_option table based on partner id and not already associated via mechanism with employee id
	 * 
	 * @param integer $eid - employee id
	 * @param integer $pid - partner id
	 * @return array of database rows
	 */
	public function getPsfmMechanismExclude($eid, $pid) {
		$select = $this->dbfunc()->select()->distinct()
		->from(array("psfm" => "partner_to_subpartner_to_funder_to_mechanism"), array('partner_id', 'subpartner_id', 'partner_funder_option_id', 'mechanism_option_id'))
		->join(array("m" => "mechanism_option"), "m.id = psfm.mechanism_option_id")
		->where("psfm.id not in (select partner_to_subpartner_to_funder_to_mechanism_id
                 from employee_to_partner_to_subpartner_to_funder_to_mechanism epsfm
                 where epsfm.is_deleted = false and epsfm.employee_id = ?)", $eid)
	    ->where("psfm.is_deleted = false")
	    ->where("psfm.partner_id = ?", $pid)
	    ->order('psfm.mechanism_option_id');
		$result = $this->dbfunc()->fetchAll($select);
		return $result;
	}
	
	//gnr psfm end
	
	
	public function getPsfmPartner($pid) {
		$select = $this->dbfunc()->select()->distinct()
		->from(array('psfm' => 'partner_to_subpartner_to_funder_to_mechanism') , array(partner_id))
		->join(array("p" => "partner"), "partner_id = p.id")
		->where("psfm.partner_id = ?", $pid)
		->where("psfm.is_deleted = false")
		->order('psfm.partner_id');
		$result = $this->dbfunc()->fetchAll($select);
		return $result;
	}
	
	public function getPsfmSubPartner() {
		$select = $this->dbfunc()->select()->distinct()
		->from(array('psfm' => 'partner_to_subpartner_to_funder_to_mechanism'), array('partner_id', 'subpartner_id'))
		->join(array("p" => "partner"), "subpartner_id = p.id")
		->where("psfm.is_deleted = false")
		->order('psfm.subpartner_id');
		$result = $this->dbfunc()->fetchAll($select);
		return $result;
	}
	
	public function getPsfmFunder() {
		$select = $this->dbfunc()->select()->distinct()
		->from(array("psfm" => "partner_to_subpartner_to_funder_to_mechanism"), array('partner_id', 'subpartner_id', 'partner_funder_option_id'))
		->join(array("f" => "partner_funder_option"), "f.id = psfm.partner_funder_option_id")
		->where("psfm.is_deleted = false")
		->order('psfm.subpartner_id', 'psfm.partner_funder_option_id');
		$result = $this->dbfunc()->fetchAll($select);
		return $result;
	}
	
	public function getPsfmMechanism() {
		$select = $this->dbfunc()->select()->distinct()
		->from(array("psfm" => "partner_to_subpartner_to_funder_to_mechanism"), array('partner_id', 'subpartner_id', 'partner_funder_option_id', 'mechanism_option_id'))
		->join(array("m" => "mechanism_option"), "m.id = psfm.mechanism_option_id")
		->where("psfm.is_deleted = false")
		->order('psfm.subpartner_id', 'psfm.partner_funder_option_id', 'psfm.mechanism_option_id');
		$result = $this->dbfunc()->fetchAll($select);
		return $result;
	}
	

	public function getEpsfmEmployee() {
		$select = $this->dbfunc()->select()->distinct()
		->from(array('epsfm' => 'employee_to_partner_to_subpartner_to_funder_to_mechanism'), array('employee_id'))
		->join(array("e" => "employee"), "employee_id = e.id")
		->where("epsfm.is_deleted = false")
		->order('epsfm.employee_id');
		$result = $this->dbfunc()->fetchAll($select);
		return $result;
	}
	
	public function getEpsfmPartner() {
		$select = $this->dbfunc()->select()->distinct()
		->from(array('epsfm' => 'employee_to_partner_to_subpartner_to_funder_to_mechanism'), array('employee_id', 'partner_id'))
		->join(array("p" => "partner"), "partner_id = p.id")
		->where("epsfm.is_deleted = false")
		->order('epsfm.employee_id', 'epsfm.partner_id');
		$result = $this->dbfunc()->fetchAll($select);
		return $result;
	}
	
	public function getEpsfmSubPartner() {
		$select = $this->dbfunc()->select()->distinct()
		->from(array('epsfm' => 'employee_to_partner_to_subpartner_to_funder_to_mechanism'), array('employee_id', 'partner_id', 'subpartner_id'))
		->join(array("p" => "partner"), "subpartner_id = p.id")
		->where("epsfm.is_deleted = false")
		->order('epsfm.employee_id', 'epsfm.subpartner_id');
		$result = $this->dbfunc()->fetchAll($select);
		return $result;
	}
	
	public function getEpsfmFunder() {
		$select = $this->dbfunc()->select()->distinct()
		->from(array("epsfm" => "employee_to_partner_to_subpartner_to_funder_to_mechanism"), array('employee_id', 'partner_id', 'subpartner_id', 'partner_funder_option_id'))
		->join(array("f" => "partner_funder_option"), "f.id = epsfm.partner_funder_option_id")
		->where("epsfm.is_deleted = false")
		->order('epsfm.employee_id', 'epsfm.subpartner_id', 'epsfm.partner_funder_option_id');
		$result = $this->dbfunc()->fetchAll($select);
		return $result;
	}
	
	public function getEpsfmMechanism() {
		$select = $this->dbfunc()->select()->distinct()
		->from(array("epsfm" => "employee_to_partner_to_subpartner_to_funder_to_mechanism"), array('employee_id', 'partner_id', 'subpartner_id', 'partner_funder_option_id', 'mechanism_option_id'))
		->join(array("m" => "mechanism_option"), "m.id = epsfm.mechanism_option_id")
		->where("epsfm.is_deleted = false")
		->order('epsfm.employee_id', 'epsfm.subpartner_id', 'epsfm.partner_funder_option_id', 'epsfm.mechanism_option_id');
		$result = $this->dbfunc()->fetchAll($select);
		return $result;
	}
	
	public function getAllPartners() {
		$select = $this->dbfunc()->select()
		->from(array("p" => "partner"));
		$result = $this->dbfunc()->fetchAll($select);
		return $result;
	}
	
	public function getAllSubPartners() {
		return( $this->getAllPartners() );
	}
	
	public function getAllFunders() {
		$select = $this->dbfunc()->select()
		->from(array("f" => "partner_funder_option"));
		$result = $this->dbfunc()->fetchAll($select);
		return $result;
	}
	
	public function getAllMechanisms() {
		$select = $this->dbfunc()->select()
		->from(array("m" => "mechanism_option"));
		$result = $this->dbfunc()->fetchAll($select);
		return $result;
	}
	
	public function getAllSfms() {
		$select = $this->dbfunc()->select()
		->from(array("sfm" => "subpartner_to_funder_to_mechanism"));
		$result = $this->dbfunc()->fetchAll($select);
		return $result;
	}
	
	public function getSfm($pid) {
		$select = $this->dbfunc()->select()
		->from(array("sfm" => "subpartner_to_funder_to_mechanism"))
		->where("sfm.id = ?", $pid)
	    ->where("sfm.is_deleted = false");
		$result = $this->dbfunc()->fetchAll($select);
		return $result;
	}
	
	public function getOneSfmSubPartner($pid) {
		$select = $this->dbfunc()->select()
		->from(array("sfm" => "subpartner_to_funder_to_mechanism"), array( 'id', 'subpartner_id' ))
		->where("sfm.id = ?", $pid)
		->where("sfm.is_deleted = false");
		$result = $this->dbfunc()->fetchAll($select);
		return $result;
	}
	
	#################################
	#                               #
	#   DEGREE SPECIFIC FUNCTIONS   #
	#                               #
	#################################

	public function getDegrees() {
		$select = $this->dbfunc()->select()
			->from("lookup_degrees")
			->order('degree');
		$result = $this->dbfunc()->fetchAll($select);
		return $result;
	}
	
	//TA:#420
	public function getDegreeInst() {
	    $select = $this->dbfunc()->select()
	    ->from("lookup_degree_institution")
	    ->order('degree_institution');
	    $result = $this->dbfunc()->fetchAll($select);
	    return $result;
	}
	
	//TA:#362
	public function getDegree($id) {
	    $select = $this->dbfunc()->select()
	    ->from("lookup_degrees")
	    ->where("id = ?",$id);
	    $result = $this->dbfunc()->fetchAll($select);
	    return $result;
	}

	public function getDegreeTypes() {
		$select = $this->dbfunc()->select()
			->from("lookup_degreetypes")
			->order('title');
		$result = $this->dbfunc()->fetchAll($select);
		return $result;
	}

	public function setDegree($degree) {
		$insert = array(
			'degree' => $degree,
		);
		$rowArray = $this->dbfunc()->insert("lookup_degrees", $insert);
		$id = $this->dbfunc()->lastInsertId();
		return ($id);
	}

	public function getInstitutionDegrees($iid){
		$select = $this->dbfunc()->select()
			->from("link_institution_degrees",
				array("id" => "id_degree"))
			->where("id_institution = ?",$iid);
		$result = $this->dbfunc()->fetchAll($select);
		return $result;
	}

	#################################
	#                               #
	#   REASON SPECIFIC FUNCTIONS   #
	#                               #
	#################################

	public function getReasons($tp) {
		$select = $this->dbfunc()->select()
			->from("lookup_reasons")
			->where("reasontype = ?",$tp)
			->order('reason');
		$result = $this->dbfunc()->fetchAll($select);
		return $result;
	}

	public function setReasons($rsn,$tp) {
		$insert = array(
			'reason' => $rsn,
			'reasontype' => $tp,
		);
		$rowArray = $this->dbfunc()->insert("lookup_reasons", $insert);
		$id = $this->dbfunc()->lastInsertId();
		return ($id);
	}

	################################
	#                              #
	#   CADRE SPECIFIC FUNCTIONS   #
	#                              #
	################################

	public function getCadres(){
		// RETURNS A LIST OF ALL ACTIVE CADRES ORDERED BY CADRE NAME
		$select = $this->dbfunc()->select()
			->from("cadres")
			->where('status = 1')
			->order('cadrename');
		$result = $this->dbfunc()->fetchAll($select);
		return $result;
	}
	
	public function getUserAllowedCadres($uid){
	    
	    $select = $this->dbfunc()->select()
	    ->from("link_user_cadres")
	    ->where('userid = ' . $uid);
	    
	    $result = $this->dbfunc()->fetchAll($select);
	    
	    if (count($result)== 0) {
	        $select = $this->dbfunc()->select(
	        array('cad.cadrename'))
	        ->from(array('cad' => 'cadres'))
	        ->joinLeft(array('luc' => "link_user_cadres"), 'luc.cadreid = cad.id')
	        ->where('luc.userid = ' . $uid);
	    }
	    else {
	        $select = $this->dbfunc()->select(
	        array('cad.cadrename'))
	        ->from(array('cad' => 'cadres'))
	        ->join(array('luc' => "link_user_cadres"), 'luc.cadreid = cad.id')
	        ->where('luc.userid = ' . $uid);
	    }
	    
	    $result = $this->dbfunc()->fetchAll($select);
	    return $result;
	}

	public function getTutorCadres($tid){
		// RETURNS A LIST OF ALL CADRES TIED TO TUTOR WITH ID $tid ORDERED BY CADRE NAME
		$select = $this->dbfunc()->select()
			->from(array ("c" => "cadres"),
					array ("id","cadrename"))
			->join(array("l" => "link_cadre_tutor"),
					"l.id_cadre = c.id")
			->where('c.status = 1')
			->where('l.id_tutor = ?',$tid)
			->order('cadrename');
		$result = $this->dbfunc()->fetchAll($select);
		return $result;
	}

	public function getInstitutionCadres($iid){
		if ($iid){
			// RETURNS A LIST OF ALL CADRES TIED TO INSTITUTION WITH ID $iid ORDERED BY CADRE NAME
			$select = $this->dbfunc()->select()
				->from(array ("c" => "cadres"),
						array ("id","cadrename"))
				->join(array("l" => "link_cadre_institution"),
						"l.id_cadre = c.id")
				->where('c.status = 1')
				->where('l.id_institution = ?',$iid)
				->order('cadrename');
			$result = $this->dbfunc()->fetchAll($select);
			return $result;
		} else {
			# RETURNS A MULTI DIMENSION ARRAY OF ALL INSTITUTIONS AND THE CADRES TIED TO THEM
			$ins = $this->getInstitutions();
			$output = array();
			foreach ($ins as $i){
				$select = $this->dbfunc()->select()
					->from(array ("c" => "cadres"),
							array ("id","cadrename"))
					->join(array("l" => "link_cadre_institution"),
							"l.id_cadre = c.id")
					->where('c.status = 1')
					->where('l.id_institution = ?',$i['id'])
					->order('cadrename');
				$result = $this->dbfunc()->fetchAll($select);
				$output[] = array(
					"institution" => $i,
					"cadres" => $result
				);
			}
			return $output;
		}
	}


	#######################################
	#                                     #
	#   STUDENT TYPE SPECIFIC FUNCTIONS   #
	#                                     #
	#######################################

	public function getStudentTypes(){
		// RETURNS A LIST OF ALL STUDENT TYPES ORDERED BY NAME
		$select = $this->dbfunc()->select()
			->from("lookup_studenttype")
			->where('status = 1')
			->order('studenttype');
		$result = $this->dbfunc()->fetchAll($select);
		return $result;
	}

	#####################################
	#                                   #
	#   TUTOR TYPE SPECIFIC FUNCTIONS   #
	#                                   #
	#####################################

	public function getTutors(){
		// RETURNS A LIST OF ALL ACTIVE CADRES ORDERED BY CADRE NAME
		$select = $this->dbfunc()->select()
			->from(array ("t" => "tutor"),
					array ("id"))
			->join(array("p" => "person"),
					"t.personid = p.id",
					array("first_name","last_name"))
					->where("p.is_deleted=0") //TA:#337
			->order(array('first_name','last_name'));
		$result = $this->dbfunc()->fetchAll($select);
		return $result;
	}
	
	//TA:#507
	public function getTutorsForUser($uid){
	    $select = $this->dbfunc()
	    ->select()
	    ->from("link_user_institution")
	    ->where('userid = ' . $uid);
	    
	    $result = $this->dbfunc()->fetchAll($select);
	    
	    if (count($result) == 0)    {
	        $select = $this->dbfunc()->select()
	        ->from(array ("t" => "tutor"),
	            array ("id"))
	            ->join(array("p" => "person"),
	                "t.personid = p.id",
	                array("first_name","last_name"))
	                    ->where("p.is_deleted=0") 
	                    ->order(array('first_name','last_name'));
	    }   else    {
	        $select = $this->dbfunc()->select()
	        ->from(array ("t" => "tutor"),
	            array ("id"))
	            ->join(array("p" => "person"),
	                "t.personid = p.id",
	                array("first_name","last_name"))
	                ->join(array('lui' => "link_user_institution"), 'lui.institutionid = t.institutionid')
	                ->where("p.is_deleted=0")
	                ->where('lui.userid = ' . $uid)
	                ->order(array('first_name','last_name'));
	    }
	    
	    $result = $this->dbfunc()->fetchAll($select);
	   // print $select;
	    return $result;
	}

	public function getTutorTypes(){
		// RETURNS A LIST OF ALL ACTIVE CADRES ORDERED BY CADRE NAME
		$select = $this->dbfunc()->select()
			->from("lookup_tutortype")
			->where('status = 1')
			->order('typename');
		$result = $this->dbfunc()->fetchAll($select);
		return $result;
	}

	public function getLinkedTutorTypes($tid){
		// RETURNS A LIST OF ALL TUTOR TYPES TIED TO TUTOR WITH ID $iid ORDERED BY TUTOR TYPE NAME
		$select = $this->dbfunc()->select()
			->from(array ("t" => "lookup_tutortype"),
					array ("id","typename"))
			->join(array("l" => "link_tutor_tutortype"),
					"l.id_tutortype = t.id",
					array())
			->where('t.status = 1')
			->where('l.id_tutor = ?',$tid)
			->order('typename');
		$result = $this->dbfunc()->fetchAll($select);
		return $result;
	}

	public function getAllTutors(){
		// RETURNS A LIST OF ALL TUTORS ORDERED BY TUTOR NAME
		$select = $this->dbfunc()->select()
			->from(array ("p" => "person"),
					array ("first_name","middle_name","last_name"))
			->join(array("t" => "tutor"),
					"t.personid = p.id",
					array("id"))
			->order('first_name','last_name');
		$result = $this->dbfunc()->fetchAll($select);
		return $result;
	}

	public function getTutorStudents($tid){
		// RETURNS A LIST OF ALL TUTOR TYPES TIED TO TUTOR WITH ID $iid ORDERED BY TUTOR TYPE NAME
		$select = $this->dbfunc()->select()
			->from(array ("s" => "student"),
					array("id"))
			->join(array ("t" => "tutor"),
					"s.advisorid = t.personid", //TA:109 "s.advisorid = t.id",
					array())
			->join(array ("p2" => "person"),
					"s.personid = p2.id",
					array("first_name","last_name"))
			
			//TA:109
			->join(array ("lsc" => "link_student_cohort"),
			    "s.id = lsc.id_student",
			    array())
			 ->join(array ("c" => "cohort"),
			        "c.id = lsc.id_cohort",
			        array("cohortname", "graddate"))
			 //   
			->where('s.isgraduated = 0')
			->where('t.personid = ?',$tid)
			->order('first_name');
		$result = $this->dbfunc()->fetchAll($select);
		return $result;
	}

	public function getTutorClasses($tid){
		$query = "SELECT * FROM classes
				  LEFT JOIN link_cohorts_classes ON link_cohorts_classes.classid = classes.id
				  LEFT JOIN cohort ON cohort.id = link_cohorts_classes.cohortid
				  LEFT JOIN lookup_coursetype ON coursetypeid = lookup_coursetype.id
				  WHERE instructorid = (SELECT id FROM tutor WHERE personid = {$tid})";
		$select = $this->dbfunc()->query($query);
		$result = $select->fetchAll();
		return $result;
	}

	##################################
	#                                #
	#   SPONSOR SPECIFIC FUNCTIONS   #
	#                                #
	##################################

	public function getSponsors(){
		// RETURNS A LIST OF ALL ACTIVE CADRES ORDERED BY CADRE NAME
		$select = $this->dbfunc()->select()
			->from("lookup_sponsors")
			->where('status = 1')
			->order('sponsorname');
		$result = $this->dbfunc()->fetchAll($select);
		return $result;
	}

	public function getOldSponsors(){
		// RETURNS A LIST OF ALL ACTIVE CADRES ORDERED BY CADRE NAME
		$select = $this->dbfunc()->select()
			->from("facility_sponsor_option",array('id','sponsorname' => 'facility_sponsor_phrase'))
			->where('is_deleted = 0')
			->order('facility_sponsor_phrase');
		$result = $this->dbfunc()->fetchAll($select);
		return $result;
	}

	##################################
	#                                #
	#   FUNDING SPECIFIC FUNCTIONS   #
	#                                #
	##################################

	public function getFunding(){
		// RETURNS A LIST OF ALL ACTIVE CADRES ORDERED BY CADRE NAME
		$select = $this->dbfunc()->select()
			->from("lookup_fundingsources")
			->where('status = 1')
			->order('fundingname');
		$result = $this->dbfunc()->fetchAll($select);
		return $result;
	}
	
	//TA:#388
	public function getFundingPairs() {
	    $db = $this->dbfunc();
	    return $db->fetchPairs($db->select()->from('lookup_fundingsources', array('id', 'fundingname'))->where('status = 1'));
	}

	################################
	#                              #
	#   ADMIN SPECIFIC FUNCTIONS   #
	#                              #
	################################

	public function AdminLabels($labels){
		$select = $this->dbfunc()->select()
			->from("translation")
			->where("key_phrase IN ('" . implode("','",$labels) . "')")
			->order('key_phrase');
		$result = $this->dbfunc()->fetchAll($select);
		return $result;
	}

	public function AdminCadres(){
		// RETURNS A LIST OF ALL ACTIVE CADRES ORDERED BY CADRE NAME
		$select = $this->dbfunc()->select()
			->from("cadres")
			->order('cadrename');
		$result = $this->dbfunc()->fetchAll($select);
		return $result;
	}
	
	//TA:#402.3
	public function AdminGradeDescription(){
	    $select = $this->dbfunc()->select()
	    ->from("lookup_grade_description")
	    ->order('grade_description_name');
	    $result = $this->dbfunc()->fetchAll($select);
	    return $result;
	}

	//TA:35 
	public function AdminDegrees(){
		$select = "select lookup_degrees.id, lookup_degrees.degree,  link_institution_degrees.id as used from lookup_degrees
		left join link_institution_degrees on link_institution_degrees.id_degree = lookup_degrees.id group by lookup_degrees.id";
		$result = $this->dbfunc()->fetchAll($select);
		return $result;
	}
	
	//TA:#420
	public function AdminDegreeInst(){
	    $select = "SELECT lookup_degree_institution.*, tutor.id AS used FROM lookup_degree_institution 
	     		    LEFT JOIN tutor ON tutor.degreeinst = lookup_degree_institution.id GROUP BY lookup_degree_institution.id ORDER BY degree_institution ASC";
	    $result = $this->dbfunc()->fetchAll($select);
	    return $result;
	}

	//TA:#404
	public function AdminFunding(){
	    $select = $this->dbfunc()->select()
	     			->from("lookup_fundingsources")
	     			->joinLeft("link_student_funding", "link_student_funding.fundingsource = lookup_fundingsources.id")
	     			->columns("lookup_fundingsources.*")
	     			->columns("link_student_funding.id as used")
	     			->group("lookup_fundingsources.id")
	     			->order('fundingname');
		$result = $this->dbfunc()->fetchAll($select);
		return $result;
	}

	public function AdminInstitutionTypes(){
		$select = $this->dbfunc()->select()
			->from("lookup_institutiontype")
			->order('typename');
		$result = $this->dbfunc()->fetchAll($select);
		return $result;
	}

	public function AdminLanguages(){
		$select = $this->dbfunc()->select()
			->from("lookup_languages")
			->order('language');
		$result = $this->dbfunc()->fetchAll($select);
		return $result;
	}

	public function AdminNationalities(){
		$select = $this->dbfunc()->select()
			->from("lookup_nationalities")
			->order('nationality');
		$result = $this->dbfunc()->fetchAll($select);
		return $result;
	}

	public function AdminJoinDropReasons(){
		$select = $this->dbfunc()->select()
			->from("lookup_reasons")
			->order('reason');
		$result = $this->dbfunc()->fetchAll($select);
		return $result;
	}

	public function AdminSponsors(){
		$select = $this->dbfunc()->select()
			->from("lookup_sponsors")
			->where("status = 1")
			->order('sponsorname');
		$result = $this->dbfunc()->fetchAll($select);
		return $result;
	}

	public function AdminStudenttypes(){
		$select = $this->dbfunc()->select()
			->from("lookup_studenttype")
			->joinLeft("student", "student.studenttype = lookup_studenttype.id")
			->columns("lookup_studenttype.*")
			->columns("student.id as used")
			->group("lookup_studenttype.id")
			->where("status = 1")
			->order('lookup_studenttype.studenttype');
		$result = $this->dbfunc()->fetchAll($select);
		return $result;
	}

	public function AdminTutortypes(){
		$select = $this->dbfunc()->select()
			->from("lookup_tutortype")
			->where("status = 1")
			->order('typename');
		$result = $this->dbfunc()->fetchAll($select);
		return $result;
	}
	
	//TA:#504
	public function AdminRelationship(){
	    $select = $this->dbfunc()->select()
	    ->from("lookup_relationship")
	    ->joinLeft("link_student_addresses", "link_student_addresses.kin_relationship = lookup_relationship.id")
	    ->columns("lookup_relationship.*")
	    ->columns("link_student_addresses.id as used")
	    ->group("lookup_relationship.id")
	    ->order('relationship');
	    $result = $this->dbfunc()->fetchAll($select);
	    return $result;
	}
	
	//TA:#504
	public function addRelationship($params){
	    $linktable = "lookup_relationship";
	    $maincolumn = "relationship";
	    $id = $_POST["_id"];
	    $value = $_POST['_relationship'];
	    
	    $select = $this->dbfunc()->select()
	    ->from($linktable)
	    ->where('LOWER(TRIM(' . $maincolumn . ')) = ?', trim(strtolower($value)));
	    $result = $this->dbfunc()->fetchAll($select);
	    if (count ($result) == 0){
	        # LINK NOT FOUND - ADDING
	        $i_arr = array(
	            $maincolumn	=> $value
	        );
	        $instypeinsert = $this->dbfunc()->insert($linktable,$i_arr);
	    }
	}
	
	//TA:#504
	public function updateRelationship($params){
	    $linktable = "lookup_relationship";
	    $maincolumn = "relationship";
	    $id = $_POST["_id"];
	    $value = $_POST['_relationship'];
	    
	    $select = $this->dbfunc()->select()
	    ->from($linktable)
	    ->where('LOWER(TRIM(' . $maincolumn . ')) = ?', trim(strtolower($value)))
	    ->where('id <> ?', $id);
	    $result = $this->dbfunc()->fetchAll($select);
	    if (count ($result) == 0){
	        # LINK NOT FOUND - ADDING
	        $i_arr = array(
	            $maincolumn	=> $value
	        );
	        $instypeinsert = $this->dbfunc()->update($linktable,$i_arr,'id = ' . $id);
	    }
	}
	
	//TA:#504
	public function deleteRelationship($params){
	    $db = $this->dbfunc();
	    $query = "DELETE FROM lookup_relationship WHERE id = " . $_POST["_id"];
	    $db->query($query);
	}

	public function AdminCourseTypes(){
		// RETURNS A LIST OF ALL COURSE TYPES ORDERED BY NAME
		$select = $this->dbfunc()->select()
			->from("lookup_coursetype")
			->order('coursetype');
		$result = $this->dbfunc()->fetchAll($select);
		return $result;
	}

	public function AdminReligiousDenominations(){
		// RETURNS A LIST OF ALL DENOMINATION TYPES ORDERED BY NAME
		$select = $this->dbfunc()->select()
			->from("lookup_studenttype")
			->order('studenttype');
		$result = $this->dbfunc()->fetchAll($select);
		return $result;
	}


	############################################################################

	public function setExternalValues($linktable,$maincolumn,$linkcolumn,$originalvar,$id){

		#	$linktable		= PIVOT TABLE TO BE USED
		#	$maincolumn		= LINK TO 'MAIN' OBJECT - SHOULD BE SAME FOR ALL ENTRIES HERE
		#	$linkcolumn		= LINK TO 'EXTERNAL' OBJECT - SHOULD BE DIFFERENT FOR EVERY ENTRY
		#	$originalvar	= ORIGINAL POST VARIABLE, OR FALSE IF NOT SET
		#	$id				= ID OF 'MAIN' OBJECT

		if ((!$originalvar) || (!is_array ($originalvar)) || (count ($originalvar) == 0)){
			# REMOVING ALL INSTITUTION TYPE LINKS
			$query = "DELETE FROM " . $linktable . " WHERE " . $maincolumn . " = " . $id;
			$this->dbfunc()->query($query);
		} else {
			$languagesspoken = implode(",", $param['languagesspoken']);

			# REMOVING OLD LINKS NO LONGER SELECTED
      $implodedvar = implode(",", $originalvar);
			$query = "DELETE FROM " . $linktable . " WHERE " . $maincolumn . " = " . $id . " AND " . $linkcolumn . " NOT IN (" . $implodedvar . ")";
			$this->dbfunc()->query($query);

			# ADDING NEW LINKS THAT WERE ADDED
			foreach ($originalvar as $key=>$val){
				$select = $this->dbfunc()->select()
					->from($linktable)
					->where($maincolumn . ' = ?', $id)
					->where($linkcolumn . ' = ?', $val);
				$result = $this->dbfunc()->fetchAll($select);
				if (count ($result) == 0){
					# LINK NOT FOUND - ADDING
					$i_arr = array(
						$linkcolumn => $val,
						$maincolumn	=> $id
					);
					$instypeinsert = $this->dbfunc()->insert($linktable,$i_arr);
				}
			}
		}
	}

	########################
	#                      #
	#   UPDATE FUNCTIONS   #
	#                      #
	########################

    /**
     * update a course's info to the database
     * @param $params - an array with keys matching the table 'classes' column names
     */
	public function updateClasses($params){
		$linktable = "classes";

        if (array_key_exists('startdate', $params)) {
            $params['startdate'] = date("Y-m-d", strtotime($params['startdate']));
        }
        if (array_key_exists('enddate', $params)) {
            $params['enddate'] = date("Y-m-d", strtotime($params['enddate']));
        }
        $this->dbfunc()->update($linktable, $params, 'id = ' . $params['id']);
	}

	public function updateCadres($params){
		$linktable = "cadres";
		$maincolumn = "cadrename";
		$id = $_POST["_id"];
		$value = $_POST['_cadrename'];

		$select = $this->dbfunc()->select()
			->from($linktable)
			->where('LOWER(TRIM(' . $maincolumn . ')) = ?', trim(strtolower($value)))
			->where('id <> ?', $id);


		$result = $this->dbfunc()->fetchAll($select);
		if (count ($result) == 0){
			# LINK NOT FOUND - ADDING
			$i_arr = array(
				$maincolumn	=> $value,
			);
			$instypeinsert = $this->dbfunc()->update($linktable,$i_arr,'id = ' . $id);
		}
	}

	public function updateCoursetypes($params){
		$linktable = "lookup_coursetype";
		$maincolumn = "coursetype";
		$id = $_POST["_id"];
		$value = $_POST['_coursetype'];

		$select = $this->dbfunc()->select()
			->from($linktable)
			->where('LOWER(TRIM(' . $maincolumn . ')) = ?', trim(strtolower($value)))
			->where('id <> ?', $id);
		$result = $this->dbfunc()->fetchAll($select);
		if (count ($result) == 0){
			# LINK NOT FOUND - ADDING
			$i_arr = array(
				$maincolumn	=> $value
			);
			$instypeinsert = $this->dbfunc()->update($linktable,$i_arr,'id = ' . $id);
		}
	}

	public function updateDegrees($params){
		$linktable = "lookup_degrees";
		$maincolumn = "degree";
		$id = $_POST["_id"];
		$value = $_POST['_degree'];

		$select = $this->dbfunc()->select()
			->from($linktable)
			->where('LOWER(TRIM(' . $maincolumn . ')) = ?', trim(strtolower($value)))
			->where('id <> ?', $id);
		$result = $this->dbfunc()->fetchAll($select);
		if (count ($result) == 0){
			# LINK NOT FOUND - ADDING
			$i_arr = array(
				$maincolumn	=> $value
			);
			$instypeinsert = $this->dbfunc()->update($linktable,$i_arr,'id = ' . $id);
		}
	}
	
	//TA:#420
	public function updateDegreeInst($params){
	    $linktable = "lookup_degree_institution";
	    $maincolumn = "degree_institution";
	    $id = $_POST["id"];
	    $value = $_POST['degree_institution'];
	
	    $select = $this->dbfunc()->select()
	    ->from($linktable)
	    ->where('LOWER(TRIM(' . $maincolumn . ')) = ?', trim(strtolower($value)))
	    ->where('id <> ?', $id);
	    $result = $this->dbfunc()->fetchAll($select);
	    if (count ($result) == 0){
	        $i_arr = array(
	        $maincolumn	=> $value
	        );
	        $instypeinsert = $this->dbfunc()->update($linktable,$i_arr,'id = ' . $id);
	    }
	}
	
	//TA:35 able to delete a degree
	public function deleteDegrees($params){
		$db = $this->dbfunc();
		$query = "DELETE FROM lookup_degrees WHERE id = " . $_POST["_id"];
		$db->query($query);
	}

	//TA:#420
	public function deleteDegreeInst($params){
	    $db = $this->dbfunc();
	    $query = "DELETE FROM lookup_degree_institution WHERE id = " . $_POST["id"];
	    $db->query($query);
	}

	public function updateReligion($params){
		$linktable = "lookup_studenttype";
		$maincolumn = "studenttype";
		$id = $_POST["_id"];
		$value = $_POST['_denom'];

		$select = $this->dbfunc()->select()
			->from($linktable)
			->where('LOWER(TRIM(' . $maincolumn . ')) = ?', trim(strtolower($value)))
			->where('id <> ?', $id);
		$result = $this->dbfunc()->fetchAll($select);
		if (count ($result) == 0){
			# LINK NOT FOUND - ADDING
			$i_arr = array(
				$maincolumn	=> $value
			);
			$instypeinsert = $this->dbfunc()->update($linktable,$i_arr,'id = ' . $id);
		}
	}
	
	//TA:#503
	public function deleteReligion($params){
	    $db = $this->dbfunc();
	    $query = "DELETE FROM lookup_studenttype WHERE id = " . $_POST["_id"];
	    $db->query($query);
	}

	public function updateFunding($params){
		$linktable = "lookup_fundingsources";
		$maincolumn = "fundingname";
		$id = $_POST["_id"];
		$value = $_POST['_fundingname'];

		$select = $this->dbfunc()->select()
			->from($linktable)
			->where('LOWER(TRIM(' . $maincolumn . ')) = ?', trim(strtolower($value)))
			->where('id <> ?', $id);
		$result = $this->dbfunc()->fetchAll($select);
		if (count ($result) == 0){
			# LINK NOT FOUND - ADDING
			$i_arr = array(
				$maincolumn	=> $value
			);
			$instypeinsert = $this->dbfunc()->update($linktable,$i_arr,'id = ' . $id);
		}
	}
	
	//TA:#404
	public function deleteFunding($params){
	    $db = $this->dbfunc();
	    $query = "DELETE FROM lookup_fundingsources WHERE id = " . $_POST["_id"];
	    $db->query($query);
	}

	public function updateInstitutiontypes($params){
		$linktable = "lookup_institutiontype";
		$maincolumn = "typename";
		$id = $_POST["_id"];
		$value = $_POST['_typename'];

		$select = $this->dbfunc()->select()
			->from($linktable)
			->where('LOWER(TRIM(' . $maincolumn . ')) = ?', trim(strtolower($value)))
			->where('id <> ?', $id);
		$result = $this->dbfunc()->fetchAll($select);
		if (count ($result) == 0){
			# LINK NOT FOUND - ADDING
			$i_arr = array(
				$maincolumn	=> $value
			);
			$instypeinsert = $this->dbfunc()->update($linktable,$i_arr,'id = ' . $id);
		}
	}

	public function updateLanguages($params){
		$linktable = "lookup_languages";
		$maincolumn = "language";
		$secondcolumn = "abbreviation";
		$id = $_POST["_id"];
		$value = $_POST['_language'];
		$value2 = $_POST['_abbreviation'];

		$select = $this->dbfunc()->select()
			->from($linktable)
			->where('LOWER(TRIM(' . $maincolumn . ')) = ?', trim(strtolower($value)))
			->where('id <> ?', $id);
		$result = $this->dbfunc()->fetchAll($select);
		if (count ($result) == 0){
			# LINK NOT FOUND - ADDING
			$i_arr = array(
				$maincolumn	=> $value,
				$secondcolumn	=> $value2
			);
			$instypeinsert = $this->dbfunc()->update($linktable,$i_arr,'id = ' . $id);
		}
	}

	public function updateNationalities($params){
		$linktable = "lookup_nationalities";
		$maincolumn = "nationality";
		$id = $_POST["_id"];
		$value = $_POST['_nationality'];

		$select = $this->dbfunc()->select()
			->from($linktable)
			->where('LOWER(TRIM(' . $maincolumn . ')) = ?', trim(strtolower($value)))
			->where('id <> ?', $id);
		$result = $this->dbfunc()->fetchAll($select);
		if (count ($result) == 0){
			# LINK NOT FOUND - ADDING
			$i_arr = array(
				$maincolumn	=> $value
			);
			$instypeinsert = $this->dbfunc()->update($linktable,$i_arr,'id = ' . $id);
		}
	}

	public function updateJoindropreasons($params){
		$linktable = "lookup_reasons";
		$maincolumn = "reason";
		$secondcolumn = "reasontype";
		$id = $_POST["_id"];
		$value = $_POST['_reason'];
		$value2 = $_POST['_reasontype'];

		$select = $this->dbfunc()->select()
			->from($linktable)
			->where('LOWER(TRIM(' . $maincolumn . ')) = ?', trim(strtolower($value)))
			->where('id <> ?', $id);
		$result = $this->dbfunc()->fetchAll($select);
		if (count ($result) == 0){
			# LINK NOT FOUND - ADDING
			$i_arr = array(
				$maincolumn	=> $value,
				$secondcolumn	=> $value2
			);
			$instypeinsert = $this->dbfunc()->update($linktable,$i_arr,'id = ' . $id);
		}
	}

	public function updateSponsors($params){
		$linktable = "lookup_sponsors";
		$maincolumn = "sponsorname";
		$id = $_POST["_id"];
		$value = $_POST['_sponsor'];

		$select = $this->dbfunc()->select()
			->from($linktable)
			->where('LOWER(TRIM(' . $maincolumn . ')) = ?', trim(strtolower($value)))
			->where('id <> ?', $id);
		$result = $this->dbfunc()->fetchAll($select);
		if (count ($result) == 0){
			# LINK NOT FOUND - ADDING
			$i_arr = array(
				$maincolumn	=> $value
			);
			$instypeinsert = $this->dbfunc()->update($linktable,$i_arr,'id = ' . $id);
		}
	}

	public function updateStudenttypes($params){
		$linktable = "lookup_studenttype";
		$maincolumn = "studenttype";
		$id = $_POST["_id"];
		$value = $_POST['_studenttype'];

		$select = $this->dbfunc()->select()
			->from($linktable)
			->where('LOWER(TRIM(' . $maincolumn . ')) = ?', trim(strtolower($value)))
			->where('id <> ?', $id);
		$result = $this->dbfunc()->fetchAll($select);
		if (count ($result) == 0){
			# LINK NOT FOUND - ADDING
			$i_arr = array(
				$maincolumn	=> $value
			);
			$instypeinsert = $this->dbfunc()->update($linktable,$i_arr,'id = ' . $id);
		}
	}

	public function updateTutortypes($params){
		$linktable = "lookup_tutortype";
		$maincolumn = "typename";
		$id = $_POST["_id"];
		$value = $_POST['_tutortype'];

		$select = $this->dbfunc()->select()
			->from($linktable)
			->where('LOWER(TRIM(' . $maincolumn . ')) = ?', trim(strtolower($value)))
			->where('id <> ?', $id);
		$result = $this->dbfunc()->fetchAll($select);
		if (count ($result) == 0){
			# LINK NOT FOUND - ADDING
			$i_arr = array(
				$maincolumn	=> $value
			);
			$instypeinsert = $this->dbfunc()->update($linktable,$i_arr,'id = ' . $id);
		}
	}


	#####################
	#                   #
	#   ADD FUNCTIONS   #
	#                   #
	#####################

    /**
     * add a course's info to the database
     * @param $params - an array with keys matching the table 'classes' column names
     */
	public function addClasses($params){
		$linktable = "classes";

        if (array_key_exists('startdate', $params)) {
            $params['startdate'] = date("Y-m-d", strtotime($params['startdate']));
        }
        if (array_key_exists('enddate', $params)) {
            $params['enddate'] = date("Y-m-d", strtotime($params['enddate']));
        }
        $this->dbfunc()->insert($linktable, $params);
		return ($this->dbfunc()->lastInsertId($linktable));
	}

	public function addCadres($params){
		$linktable = "cadres";
		$maincolumn = "cadrename";
		$id = $_POST["_id"];
		$value = $_POST['_cadrename'];

		$select = $this->dbfunc()->select()
			->from($linktable)
			->where('LOWER(TRIM(' . $maincolumn . ')) = ?', trim(strtolower($value)));


		$result = $this->dbfunc()->fetchAll($select);
		if (count ($result) == 0){
			# LINK NOT FOUND - ADDING
			$i_arr = array(
				$maincolumn	=> $value,
			);
			$instypeinsert = $this->dbfunc()->insert($linktable,$i_arr);
		}
	}

	public function addDegrees($params){
		$linktable = "lookup_degrees";
		$maincolumn = "degree";
		$id = $_POST["_id"];
		$value = $_POST['_degree'];

		$select = $this->dbfunc()->select()
			->from($linktable)
			->where('LOWER(TRIM(' . $maincolumn . ')) = ?', trim(strtolower($value)));
		$result = $this->dbfunc()->fetchAll($select);
		if (count ($result) == 0){
			# LINK NOT FOUND - ADDING
			$i_arr = array(
				$maincolumn	=> $value
			);
			$instypeinsert = $this->dbfunc()->insert($linktable,$i_arr);
		}
	}
	
	//TA:#420
	public function addDegreeInst($params){
	    $linktable = "lookup_degree_institution";
	    $maincolumn = "degree_institution";
	    $id = $_POST["id"];
	    $value = $_POST['degree_institution'];
	
	    $select = $this->dbfunc()->select()
	    ->from($linktable)
	    ->where('LOWER(TRIM(' . $maincolumn . ')) = ?', trim(strtolower($value)));
	    $result = $this->dbfunc()->fetchAll($select);
	    if (count ($result) == 0){
	        $i_arr = array(
	        $maincolumn	=> $value
	        );
	        $instypeinsert = $this->dbfunc()->insert($linktable,$i_arr);
	    }
	}

	public function addCoursetypes($params){
		$linktable = "lookup_coursetype";
		$maincolumn = "coursetype";
		$id = $_POST["_id"];
		$value = $_POST['_coursetype'];

		$select = $this->dbfunc()->select()
			->from($linktable)
			->where('LOWER(TRIM(' . $maincolumn . ')) = ?', trim(strtolower($value)));
		$result = $this->dbfunc()->fetchAll($select);
		if (count ($result) == 0){
			# LINK NOT FOUND - ADDING
			$i_arr = array(
				$maincolumn	=> $value
			);
			$instypeinsert = $this->dbfunc()->insert($linktable,$i_arr);
		}
	}

	public function addReligion($params){
		$linktable = "lookup_studenttype";
		$maincolumn = "studenttype";
		$id = $_POST["_id"];
		$value = $_POST['_denom'];

		$select = $this->dbfunc()->select()
			->from($linktable)
			->where('LOWER(TRIM(' . $maincolumn . ')) = ?', trim(strtolower($value)));
		$result = $this->dbfunc()->fetchAll($select);
		if (count ($result) == 0){
			# LINK NOT FOUND - ADDING
			$i_arr = array(
				$maincolumn	=> $value
			);
			$instypeinsert = $this->dbfunc()->insert($linktable,$i_arr);
		}
	}

	public function addFunding($params){
		$linktable = "lookup_fundingsources";
		$maincolumn = "fundingname";
		$id = $_POST["_id"];
		$value = $_POST['_fundingname'];

		$select = $this->dbfunc()->select()
			->from($linktable)
			->where('LOWER(TRIM(' . $maincolumn . ')) = ?', trim(strtolower($value)));
		$result = $this->dbfunc()->fetchAll($select);
		if (count ($result) == 0){
			# LINK NOT FOUND - ADDING
			$i_arr = array(
				$maincolumn	=> $value
			);
			$instypeinsert = $this->dbfunc()->insert($linktable,$i_arr);
		}
	}

	public function addInstitutiontypes($params){
		$linktable = "lookup_institutiontype";
		$maincolumn = "typename";
		$id = $_POST["_id"];
		$value = $_POST['_typename'];

		$select = $this->dbfunc()->select()
			->from($linktable)
			->where('LOWER(TRIM(' . $maincolumn . ')) = ?', trim(strtolower($value)));
		$result = $this->dbfunc()->fetchAll($select);
		if (count ($result) == 0){
			# LINK NOT FOUND - ADDING
			$i_arr = array(
				$maincolumn	=> $value
			);
			$instypeinsert = $this->dbfunc()->insert($linktable,$i_arr);
		}
	}

	public function addLanguages($params){
		$linktable = "lookup_languages";
		$maincolumn = "language";
		$secondcolumn = "abbreviation";
		$id = $_POST["_id"];
		$value = $_POST['_language'];
		$value2 = $_POST['_abbreviation'];

		$select = $this->dbfunc()->select()
			->from($linktable)
			->where('LOWER(TRIM(' . $maincolumn . ')) = ?', trim(strtolower($value)));
		$result = $this->dbfunc()->fetchAll($select);
		if (count ($result) == 0){
			# LINK NOT FOUND - ADDING
			$i_arr = array(
				$maincolumn	=> $value,
				$secondcolumn	=> $value2
			);
			$instypeinsert = $this->dbfunc()->insert($linktable,$i_arr);
		}
	}

	public function addNationalities($params){
		$linktable = "lookup_nationalities";
		$maincolumn = "nationality";
		$id = $_POST["_id"];
		$value = $_POST['_nationality'];

		$select = $this->dbfunc()->select()
			->from($linktable)
			->where('LOWER(TRIM(' . $maincolumn . ')) = ?', trim(strtolower($value)));
		$result = $this->dbfunc()->fetchAll($select);
		if (count ($result) == 0){
			# LINK NOT FOUND - ADDING
			$i_arr = array(
				$maincolumn	=> $value
			);
			$instypeinsert = $this->dbfunc()->insert($linktable,$i_arr);
		}
	}

	public function addJoindropreasons($params){
		$linktable = "lookup_reasons";
		$maincolumn = "reason";
		$secondcolumn = "reasontype";
		$id = $_POST["_id"];
		$value = $_POST['_reason'];
		$value2 = $_POST['_reasontype'];

		$select = $this->dbfunc()->select()
			->from($linktable)
			->where('LOWER(TRIM(' . $maincolumn . ')) = ?', trim(strtolower($value)));
		$result = $this->dbfunc()->fetchAll($select);
		if (count ($result) == 0){
			# LINK NOT FOUND - ADDING
			$i_arr = array(
				$maincolumn	=> $value,
				$secondcolumn	=> $value2
			);
			$instypeinsert = $this->dbfunc()->insert($linktable,$i_arr);
		}
	}

	public function addSponsors($params){
		$linktable = "lookup_sponsors";
		$maincolumn = "sponsorname";
		$id = $_POST["_id"];
		$value = $_POST['_sponsor'];

		$select = $this->dbfunc()->select()
			->from($linktable)
			->where('LOWER(TRIM(' . $maincolumn . ')) = ?', trim(strtolower($value)));
		$result = $this->dbfunc()->fetchAll($select);
		if (count ($result) == 0){
			# LINK NOT FOUND - ADDING
			$i_arr = array(
				$maincolumn	=> $value
			);
			$instypeinsert = $this->dbfunc()->insert($linktable,$i_arr);
		}
	}

	public function addStudenttypes($params){
		$linktable = "lookup_studenttype";
		$maincolumn = "studenttype";
		$id = $_POST["_id"];
		$value = $_POST['_studenttype'];

		$select = $this->dbfunc()->select()
			->from($linktable)
			->where('LOWER(TRIM(' . $maincolumn . ')) = ?', trim(strtolower($value)));
		$result = $this->dbfunc()->fetchAll($select);
		if (count ($result) == 0){
			# LINK NOT FOUND - ADDING
			$i_arr = array(
				$maincolumn	=> $value
			);
			$instypeinsert = $this->dbfunc()->insert($linktable,$i_arr);
		}
	}

	public function addTutortypes($params){
		$linktable = "lookup_tutortype";
		$maincolumn = "typename";
		$id = $_POST["_id"];
		$value = $_POST['_tutortype'];

		$select = $this->dbfunc()->select()
			->from($linktable)
			->where('LOWER(TRIM(' . $maincolumn . ')) = ?', trim(strtolower($value)));
		$result = $this->dbfunc()->fetchAll($select);
		if (count ($result) == 0){
			# LINK NOT FOUND - ADDING
			$i_arr = array(
				$maincolumn	=> $value
			);
			$instypeinsert = $this->dbfunc()->insert($linktable,$i_arr);
		}
	}
	
	//TA:#402.3
	public function addGradeDescription($params){
	    $grade_description_name = $_POST['grade_description_name'];
	    $grade_description_type = $_POST['grade_description_type'];
	        $this->dbfunc()->insert('lookup_grade_description',array(
	        'grade_description_name'=>$grade_description_name,
	        'grade_description_type'=>$grade_description_type,
	        ));
	}
	
	//TA:#402.3
	public function deleteGradeDescription($params){
		$this->dbfunc()->delete('lookup_grade_description','id=' . $_POST['id']);
	}
	
	//TA:#402.3
	public function editGradeDescription($params){
	    $grade_description_name = $_POST['grade_description_name'];
	        $this->dbfunc()->update('lookup_grade_description', array(
	        'grade_description_name'=>$grade_description_name
	        ), "id=" . $_POST['id']);
	}

	public function updateCohortLicense($cid,$param){
		if ($param['licenseid'] == 0){
			$insert = array(
				'licensename'	=> $param['licensename'],
				'licensedate'	=> $param['licensedate'],
				'cohortid'		=> $cid,
			);
			$this->dbfunc()->insert("licenses", $insert);
		} else {
			$insert = array(
				'licensename'	=> $param['licensename'],
				'licensedate'	=> $param['licensedate'],
			);
			$this->dbfunc()->update("licenses",$insert,'id = ' . $param['licenseid']);
		}
	}

	public function updateCohortStudents($cid,$param){
		$db = $this->dbfunc();
		$ids = array();
		foreach ($param['students'] as $student){
			$id = $student;
			$ids[] = $id;

			$query = "SELECT * FROM link_student_cohort WHERE id_cohort = " . $cid . " AND id_student = " . $id;
			$select = $db->query($query);
			$result = $select->fetchAll();
			if (count ($result) == 0){
				# ADD
				$insert = array(
					'id_student'	=> $id,
					'id_cohort'		=> $cid,
					'joindate'		=> date("Y-m-d H:i:s"),
				);
				$this->dbfunc()->insert("link_student_cohort", $insert);
			}
			$this->updatePersonInstitution("student",$id,$cid);

			// update cadre in student record
			$cadre_sql = "UPDATE student SET cadre = (SELECT cadreid FROM cohort WHERE id = {$cid}) WHERE id = {$id}";
			$db->query($cadre_sql);
		}
		if (count ($ids) == 0){
			# DELETED ALL LINKS
			$query = "DELETE FROM link_student_cohort WHERE id_cohort = '" . $cid . "'";
			$db->query($query);
		} else {
			# REMOVE ANYTHING THAT'S NOT SELECTED
			$query = "DELETE FROM link_student_cohort WHERE id_cohort = " . $cid . " AND id_student NOT IN (" . implode(",", $ids) . ")";
			$select = $db->query($query);
		}
	}

	public function updateCohortClasses($cid,$param, $delete = true){
		$db = $this->dbfunc();
		$ids = array();
		foreach ($param['class'] as $class){
			$id = $class['id'];
			$ids[] = $id;

			$query = "SELECT * FROM link_cohorts_classes WHERE cohortid = " . $cid . " AND classid = " . $id;
			$select = $db->query($query);
			$result = $select->fetchAll();
			if (count ($result) == 0){
				# ADD
				$insert = array(
					'classid'	=> $id,
					'cohortid'	=> $cid,
				);
				$this->dbfunc()->insert("link_cohorts_classes", $insert);
			}
		}
		if ($delete) {
            if (count($ids) == 0) {
                # DELETED ALL LINKS
                $query = "DELETE FROM link_cohorts_classes WHERE cohortid = '" . $cid . "'";
                $db->query($query);
            } else {
                # REMOVE ANYTHING THAT'S NOT SELECTED
                $query = "DELETE FROM link_cohorts_classes WHERE cohortid = " . $cid . " AND classid NOT IN (" . implode(",", $ids) . ")";
                $select = $db->query($query);
            }
        }
	}

	public function updateCohortPracticums($cid,$param){
		if ($param['practicumid'] == 0){
			$insert = array(
				'practicumname'		=> $param['practicumname'],
				'practicumdate'		=> date("Y-m-d", strtotime($param['practicumdate'])),
				'facilityid'		=> $param['facilityid'],
				'advisorid'			=> $param['advisorid'],
				'hoursrequired'		=> is_numeric($param['hoursrequired']) ? $param['hoursrequired'] : "0.00",
				'cohortid'			=> $cid,
			);
			$this->dbfunc()->insert("practicum", $insert);
		} else {
			$insert = array(
				'practicumname'		=> $param['practicumname'],
				'practicumdate'		=> date("Y-m-d", strtotime($param['practicumdate'])),
				'facilityid'		=> $param['facilityid'],
				'advisorid'			=> $param['advisorid'],
				'hoursrequired'		=> is_numeric($param['hoursrequired']) ? $param['hoursrequired'] : "0.00",
			);
			$this->dbfunc()->update("practicum",$insert,'id = ' . $param['practicumid']);
		}
	}

	public function ListCurrentClasses($cid,$sid = false) {
		# SHOW ALL CLASSES BASED ON COHORT ID AND POTENTIALLY CROSS WITH STUDENT ID
		$db = $this->dbfunc();

		if (!$sid){
			$query = "
				SELECT c.*, p.first_name, p.last_name, lct.coursetype
				FROM classes c
				INNER JOIN link_cohorts_classes lcc ON lcc.classid = c.id
				LEFT JOIN tutor t ON t.id = c.instructorid
				LEFT JOIN person p ON t.personid = p.id
				LEFT JOIN lookup_coursetype lct ON lct.id = c.coursetypeid
				WHERE lcc.cohortid = '" . $cid . "'
				ORDER BY c.classname, p.first_name, p.last_name";
		} else {
			
			/* Sean: Had to redo. Was using personid in place of studentid
			 * Sean: Converted foreach loop (one query per iteration) to a single query
			 */ 
		    //TA:#402
			$query = "
				SELECT c.*, p.first_name, p.last_name, lct.coursetype,
					CASE WHEN sc.linkclasscohortid IS NULL THEN 0 ELSE sc.linkclasscohortid END linkid,
					CASE WHEN sc.classid IS NULL OR LENGTH(camark) = 0 OR camark IS NULL THEN 'N/A' ELSE camark END camark,
					CASE WHEN sc.classid IS NULL OR LENGTH(exammark) = 0 OR exammark IS NULL THEN 'N/A' ELSE exammark END exammark,
					CASE WHEN sc.classid IS NULL OR LENGTH(grade) = 0 OR grade IS NULL THEN 'N/A' ELSE grade END grade,
			    CASE WHEN sc.classid IS NULL OR LENGTH(grade_description) = 0 OR grade_description IS NULL THEN '' ELSE grade_description END grade_description,
					CASE WHEN sc.classid IS NULL OR LENGTH(credits) = 0 OR credits IS NULL THEN 'N/A' ELSE credits END credits

				FROM  classes c
				INNER JOIN link_cohorts_classes lcc ON lcc.classid = c.id
				LEFT JOIN tutor t ON t.id = c.instructorid
				LEFT JOIN person p ON t.personid = p.id
				LEFT JOIN lookup_coursetype lct ON lct.id = c.coursetypeid
				LEFT JOIN (
					SELECT classid, linkclasscohortid, camark, exammark, grade, credits, grade_description
					FROM   link_student_classes 
					WHERE	studentid = " . $sid . "
					AND		classid IN (SELECT classid FROM link_cohorts_classes WHERE cohortid = " . $cid . ")
				) AS sc ON sc.classid = c.id
				WHERE lcc.cohortid = " . $cid . "
				ORDER BY c.classname, p.first_name, p.last_name";		
		}
		$select = $db->query($query); 
		$result = $select->fetchAll();
		return $result;		
	}

	public function ListCurrentPracticum($cid,$sid = false){
		# SHOW ALL CLASSES BASED ON COHORT ID AND POTENTIALLY CROSS WITH STUDENT ID
		$db = $this->dbfunc();

		if (!$sid){
			$query = "
				SELECT p.*, f.facility_name, pe.first_name, pe.last_name, pe.id AS personid
				FROM practicum p
				LEFT JOIN facility f ON f.id = p.facilityid
				LEFT JOIN tutor t ON t.id = p.advisorid
				LEFT JOIN person pe ON pe.id = t.personid
				WHERE cohortid = " . $cid . "
				ORDER BY practicumdate DESC, practicumname";

		} else {
			/* Sean: Had to redo. Was using personid in place of studentid
			 * Sean: Converted foreach loop (one query per iteration) to a single query
			 */
		    //TA:#402.2 
			$query = "
				SELECT p.*, f.facility_name, pe.first_name, pe.last_name, pe.id AS personid,
					CASE WHEN sp.linkcohortpracticumid IS NULL THEN 0 ELSE sp.linkcohortpracticumid END linkid,
					CASE WHEN sp.practicumid IS NULL OR LENGTH(grade) = 0 OR grade IS NULL THEN 'N/A' ELSE grade END grade,
			        CASE WHEN sp.practicumid IS NULL OR LENGTH(grade_description) = 0 OR grade_description IS NULL THEN '' ELSE grade_description END grade_description,
					CASE WHEN sp.practicumid IS NULL OR LENGTH(hourscompleted) = 0 OR hourscompleted IS NULL THEN 'N/A' ELSE hourscompleted END hourscompleted
				FROM practicum p
				LEFT JOIN facility f ON f.id = p.facilityid
				LEFT JOIN tutor t ON t.id = p.advisorid
				LEFT JOIN person pe ON pe.id = t.personid
				LEFT JOIN (
					SELECT practicumid, linkcohortpracticumid, grade, hourscompleted, grade_description
					FROM   link_student_practicums 
					WHERE	studentid = " . $sid . "
					AND		practicumid IN (SELECT id FROM practicum WHERE cohortid = " . $cid . ")
				) AS sp ON sp.practicumid = p.id
				WHERE cohortid = " . $cid ."
				ORDER BY practicumdate DESC, practicumname";
		}
		$select = $db->query($query);
		$result = $select->fetchAll();
		return $result;
	}

	public function ListCurrentLicenses($cid,$sid = false){
		# SHOW ALL CLASSES BASED ON COHORT ID AND POTENTIALLY CROSS WITH STUDENT ID
		$db = $this->dbfunc();

		if (!$sid){
			$query = "
				SELECT id, licensename, licensedate
				FROM licenses
				WHERE cohortid = " . $cid . "
				ORDER BY licensedate, licensename";
		} else {
			/* Sean: Had to redo. Was using personid in place of studentid
			 * Sean: Converted foreach loop (one query per iteration) to a single query
			 */ 
		    //TA:#402.2
			$query = "
				SELECT l.id, licensename, licensedate,
					CASE WHEN sl.linkclasslicenseid IS NULL THEN 0 ELSE sl.linkclasslicenseid END linkid,
					CASE WHEN sl.licenseid IS NULL OR LENGTH(grade) = 0 OR grade IS NULL THEN 'N/A' ELSE grade END grade,
					CASE WHEN sl.licenseid IS NULL OR LENGTH(credits) = 0 OR credits IS NULL THEN 'N/A' ELSE credits END credits,
			        CASE WHEN sl.licenseid IS NULL OR LENGTH(grade_description) = 0 OR grade_description IS NULL THEN '' ELSE grade_description END grade_description
				FROM licenses l 
				LEFT JOIN (
					SELECT licenseid, linkclasslicenseid, grade, credits, grade_description
					FROM   link_student_licenses 
					WHERE  studentid = " . $sid . "
					AND    licenseid IN (SELECT id FROM licenses WHERE cohortid = " . $cid . ")
				) AS sl ON sl.licenseid = l.id
				WHERE cohortid = " . $cid . "
				ORDER BY licensedate, licensename";
		}
		$select = $db->query($query);
		$result = $select->fetchAll();
		return $result;
	}


	//TA:#402.2
	function updateStudentLicense($sid,$param){
		$db = $this->dbfunc();
		foreach ($param['license'] as $key=>$value){
			$query = "SELECT * FROM link_student_licenses WHERE
				studentid = " . $sid . " AND
				licenseid = " . $key . " AND
				cohortid = " . $param['cohortid'];
			$select = $db->query($query);
			$result = $select->fetchAll();
			if (count ($result) == 0){
				$insert = array(
					'studentid'		=> $sid, 
					'licenseid'		=> $key,
					'cohortid'		=> $param['cohortid'],
					'grade'			=> $value['grade'],
				    'grade_description'	=> $value['grade_desciption'],
				);
				$db->insert("link_student_licenses", $insert);
			} else {
				$row = $result[0];
				$insert = array(
					'grade'			=> $value['grade'],
				    'grade_description'	=> $value['grade_desciption'],
				);
				$db->update("link_student_licenses", $insert,'id = ' . $row['id']);
			}
		}
	}

	function updateStudentClass($sid,$param){
		$db = $this->dbfunc();
        //TA:#270 this way does not work to merge 4 arrays keys, each array must be defined before merging
		//$allclasses = array_unique(array_merge($allclasses, array_keys($param['camark']), array_keys($param['exammark']), array_keys($param['grade']), array_keys($param['credits'])));
		$allclasses = array();
		if($param['camark']){
		   $allclasses = array_unique(array_merge($allclasses, array_keys($param['camark'])));
		}
		if($param['exammark']){
		  $allclasses = array_unique(array_merge($allclasses, array_keys($param['exammark'])));
		}
 		if($param['grade']){
 		 $allclasses = array_unique(array_merge($allclasses, array_keys($param['grade'])));
 		}
 		//TA:#402
 		if($param['grade_description']){
 		 		 $allclasses = array_unique(array_merge($allclasses, array_keys($param['grade_description'])));
 		}
 		if($param['credits']){
 		 $allclasses = array_unique(array_merge($allclasses, array_keys($param['credits'])));
 		}
 		//TA:#270 remove all classes that student do not have grades
 		if(count($allclasses) === 0){
 		 $db->delete("link_student_classes", " studentid=" . $sid . " and  cohortid=" . $param['cohortid']);
 		}else{
 		    $db->delete("link_student_classes", " studentid=" . $sid . " and  cohortid=" . $param['cohortid'] . " and classid not in (" . implode(",", $allclasses) . ")");
 		}
		foreach ($allclasses as $cid) {
			$query = "SELECT * FROM link_student_classes WHERE
				studentid = " . $sid . " AND
				classid = " . $cid . " AND
				cohortid = " . $param['cohortid'];
			$select = $db->query($query);
			$result = $select->fetchAll();
			//TA:#402 allthose fields are required in 'link_student_classes' table 
			//TODO: just do nothing if any of the undefined
			if(!$param['camark'][$cid] || !$param['exammark'][$cid] || !$param['grade'][$cid]){
			    continue;
			}
			if (count ($result) == 0) {
				$insert = array(
					'studentid'		=> $sid, 
					'classid'		=> $cid,
					'cohortid'		=> $param['cohortid'],
					'camark'		=> $param['camark'][$cid],
					'exammark'		=> $param['exammark'][$cid],
					'grade'			=> $param['grade'][$cid],
				    'grade_description'			=> $param['grade_description'][$cid],//TA:#402
					'credits'	=> $param['credits'][$cid]
				);
				$db->insert("link_student_classes", $insert);
			} else {
				$row = $result[0];
				$insert = array(
					'camark'		=> $param['camark'][$cid],
					'exammark'		=> $param['exammark'][$cid],
					'grade'			=> $param['grade'][$cid],
				    'grade_description'			=> $param['grade_description'][$cid],//TA:#402
					'credits'	=> $param['credits'][$cid]
				);
				$db->update("link_student_classes", $insert,'id = ' . $row['id']);
			}
		}
	}

	//TA:#402.2
	function updateStudentPracticum($sid,$param){
		$db = $this->dbfunc();
		foreach ($param['practicum'] as $key=>$value){
		    if(!$value['grade']){
		        $value['grade'] = '';
		    }
			$query = "SELECT * FROM link_student_practicums WHERE
				studentid = " . $sid . " AND 
				practicumid = " . $key . " AND
				cohortid = " . $param['cohortid'];
			$select = $db->query($query);
			$result = $select->fetchAll();
			if (count ($result) == 0){
				$insert = array(
					'studentid'			=> $sid,
					'practicumid'		=> $key,
					'cohortid'			=> $param['cohortid'],
					'hourscompleted'	=> $value['completed'],
					'grade'				=> $value['grade'],
				    'grade_description'	=> $value['grade_desciption'],
				);
				$db->insert("link_student_practicums", $insert);
			} else {
				$row = $result[0];
				$insert = array(
					'hourscompleted'	=> $value['completed'],
					'grade'				=> $value['grade'],
				    'grade_description'	=> $value['grade_desciption'],
				);
			    $db->update("link_student_practicums", $insert,'id = ' . $row['id']);
		    }
		}
	}

	public function deleteCohortPracticum($pid,$param){
		$db = $this->dbfunc();
		$query = "DELETE FROM practicum WHERE id = " . addslashes($param['delpracticum']);
		$db->query($query);
	}

	public function deleteCohortLicense($pid,$param){
		$db = $this->dbfunc();
		$query = "DELETE FROM licenses WHERE id = " . addslashes($param['dellicense']);
		$db->query($query);
	}

	public function deleteClass($data) {
		if (isset($data['id']) && $data['id']) {
            $db = $this->dbfunc();
            $where = $db->quoteInto('classid = ?', $data['id']);
            $db->delete('link_cohorts_classes', $where);
            $db->delete('link_student_classes', $where);
            $where = $db->quoteInto('id = ?', $data['id']);
            $db->delete('classes', $where);
		}
	}

	public function saveLabels($param){

		$db = $this->dbfunc();
		foreach ($param['fields'] as $field=>$value){
			
			$select = $db->select()
				->from("translation")
				->where("key_phrase = ?",$field)
				->where("is_deleted = 0");
			$result = $db->fetchAll($select);
			if (count ($result) > 0){
				# UPDATE
				$row = $result[0];
				if (trim($value) != ""){
					# SETTING NEW VALUE
					$insert = array(
						'phrase'		=> $value,
					);
				} else {
					# DELETING
					$insert = array(
						'is_deleted'	=> 1,
					);
				}
				$db->update("translation", $insert,'id = ' . $row['id']);
			} else {
				# INSERT
				if (trim($value) != ""){
					$insert = array(
						'key_phrase'	=> $field,
						'phrase'		=> $value,
					);
					
				  $db->insert("translation", $insert);
				}
			}
#			echo $select->__toString() . "<br>";
#			$result = $this->dbfunc()->fetchAll($select);
#			return $result;

		}
	}

	public function getCohortInstitution($pid, $tp){
		$db = $this->dbfunc();
		$cohort = array();
		$institution = array();
		switch ($tp){
			case "student":

				// GETTING COHORT
				$select = $db->select()
					->from("link_student_cohort")
					->join(array("c" => "cohort"),
							"id_cohort = c.id")
					->where('id_student = ?', $pid);

				$result = $db->fetchAll($select);
				if (count ($result) > 0){
					$cohort = $result[0];

					// GETTING INSTITUTION
					$select = $db->select()
						->from("institution")
						->where('id = ?', $cohort['institutionid']);

					$result = $db->fetchAll($select);
					if (count ($result) > 0){
						$institution = $result[0];
					}
				}

				if( count($result) == 0 ){
					$select = $db->select()
						->from("institution")
						->join(array("s" => "student"),
								"s.institutionid = institution.id")
						->where('s.id = ?', $pid); 
					$result = $db->fetchAll($select);
					if (count ($result) > 0){
						$institution = $result[0];
					}
				}

			break;
			case "tutor":

				$select = $db->select()
					->from("institution")
					->join(array("l" => "link_tutor_institution"),
							"l.id_institution = institution.id")
					->where('l.id_tutor = ?', $pid);

				$result = $db->fetchAll($select);

				//echo '<pre>'; print_r($result); echo '</pre>';
				if (count ($result) > 0){
					$institution = $result[0];
				} else {
					$select = $db->select()
						->from("institution")
						->join(array("t" => "tutor"),
								"t.institutionid = institution.id")
						->where('t.id = ?', $pid);
					$result = $db->fetchAll($select);
					if (count ($result) > 0){
						$institution = $result[0];
					}
				}

			break;
		}

		return array($cohort, $institution);
	}

	public function getPersonTraining($pid){
		$db = $this->dbfunc();
		$select = $db->select()
			->from("link_person_training")
			->where('personid = ?', $pid);
		$result = $db->fetchAll($select);
		$return = array();
		if (count ($result) > 0){
			foreach ($result as $row){
				// ADDING GROUPING, IF NOT EXIST
				$return[] = array(
					"id"			=> $row['id'],
					"personid"		=> $row['personid'],
					"trainingid"	=> $row['trainingid'],
					"year"			=> $row['year'],
					"institution"	=> $row['institution'],
					"othername"		=> $row['othername'],
				);
			}
		}
		return $return;
	}


	public function getSkillSmartLookups(){
		$db = $this->dbfunc();
		$select = $db->select()
			->from("lookup_skillsmart")
			->where('status = ?', 1)
			->order('lookupgroup')
			->order('lookupvalue');
		$result = $db->fetchAll($select);
		$return = array();
		if (count ($result) > 0){
			foreach ($result as $row){
				// ADDING GROUPING, IF NOT EXIST
				if (!isset ($return[$row['lookupgroup']])){
					$return[$row['lookupgroup']] = array();
				}

				// ADDING GROUPING, IF NOT EXIST
				$return[$row['lookupgroup']][] = array(
					"id"	=> $row['id'],
					"label"	=> $row['lookupvalue'],
				);
			}
		}
		return $return;

	}

	public function addSkillsmartLookup($params,$group){
		$linktable		= "lookup_skillsmart";
		$maincolumn		= "lookupvalue";
		$groupcolumn	= "lookupgroup";
		$id				= $_POST["_id"];
		$value			= $_POST['_fieldtoupdate'];

		$select = $this->dbfunc()->select()
			->from($linktable)
			->where('LOWER(TRIM(' . $groupcolumn . ')) = ?', trim(strtolower($group)))
			->where('LOWER(TRIM(' . $maincolumn . ')) = ?', trim(strtolower($value)));
		$result = $this->dbfunc()->fetchAll($select);
		if (count ($result) == 0){
			# LINK NOT FOUND - ADDING
			$i_arr = array(
				$groupcolumn	=> $group,
				$maincolumn		=> $value
			);
			$instypeinsert = $this->dbfunc()->insert($linktable,$i_arr);
		}
	}

	public function updateSkillsmartLookup($params){
		$linktable		= "lookup_skillsmart";
		$maincolumn		= "lookupvalue";
		$groupcolumn	= "lookupgroup";
		$id				= $_POST["_id"];
		$value			= $_POST['_fieldtoupdate'];

		$select = $this->dbfunc()->select()
			->from($linktable)
			->where('LOWER(TRIM(' . $groupcolumn . ')) = ?', trim(strtolower($group)))
			->where('LOWER(TRIM(' . $maincolumn . ')) = ?', trim(strtolower($value)))
			->where('status = ?', 1)
			->where('id = ?', $id);
		$result = $this->dbfunc()->fetchAll($select);
		if (count ($result) == 0){
			# LINK NOT FOUND - ADDING
			$i_arr = array(
				$maincolumn	=> $value
			);
			$instypeinsert = $this->dbfunc()->update($linktable,$i_arr,'id = ' . $id);
		}
	}


	public function getSkillSmartCompetencies($cid = 0){
		if (!$cid){
			$db = $this->dbfunc();
			$select = $db->select()
				->from("competencies")
				->where('status = ?', 1)
				->order('competencyname');
			$result = $db->fetchAll($select);
			$return = array();
			if (count ($result) > 0){
				foreach ($result as $row){
					// ADDING GROUPING, IF NOT EXIST
					$return[] = array(
						"id"	=> $row['id'],
						"label"	=> $row['competencyname'],
					);
				}
			}
		} else {
			$db = $this->dbfunc();
			$select = $db->select()
				->from("competencies")
				->where('status = ?', 1)
				->where('id = ?', $cid)
				->order('competencyname');
			$result = $db->fetchAll($select);
			$return = array();
			if (count ($result) > 0){
				foreach ($result as $row){
					// ADDING GROUPING, IF NOT EXIST
					$return = array(
						"id"	=> $row['id'],
						"label"	=> $row['competencyname'],
					);
				}
			}
		}
		return $return;

	}

	public function addSkillsmartCompetency($params){
		$linktable		= "competencies";
		$maincolumn		= "competencyname";
		$id				= $_POST["_id"];
		$value			= $_POST['_fieldtoupdate'];

		$select = $this->dbfunc()->select()
			->from($linktable)
			->where('LOWER(TRIM(' . $maincolumn . ')) = ?', trim(strtolower($value)));
		$result = $this->dbfunc()->fetchAll($select);
		if (count ($result) == 0){
			# LINK NOT FOUND - ADDING
			$i_arr = array(
				$maincolumn		=> $value
			);
			$instypeinsert = $this->dbfunc()->insert($linktable,$i_arr);
		}
	}

	public function updateSkillsmartCompetency($params){
		$linktable		= "competencies";
		$maincolumn		= "competencyname";
		$id				= $_POST["_id"];
		$value			= $_POST['_fieldtoupdate'];

		$select = $this->dbfunc()->select()
			->from($linktable)
			->where('LOWER(TRIM(' . $maincolumn . ')) = ?', trim(strtolower($value)))
			->where('status = ?', 1)
			->where('id = ?', $id);
		$result = $this->dbfunc()->fetchAll($select);
		if (count ($result) == 0){
			# LINK NOT FOUND - ADDING
			$i_arr = array(
				$maincolumn	=> $value
			);
			$instypeinsert = $this->dbfunc()->update($linktable,$i_arr,'id = ' . $id);
		}
	}

	public function getSkillSmartCompetenciesQuestions($cid = 0){
		$db = $this->dbfunc();
		$select = $db->select()
			->from("competencies_questions")
			->where('status = ?', 1)
			->where('competencyid = ?', $cid)
			->order('itemorder')
			->order('question');
		$result = $db->fetchAll($select);
		$return = array();
		if (count ($result) > 0){
			foreach ($result as $row){
				// ADDING GROUPING, IF NOT EXIST
				$return[] = array(
					"id"			=> $row['id'],
					"competencyid"	=> $row['competencyid'],
					"question"		=> $row['question'],
					"itemorder"		=> $row['itemorder'],
					"itemtype"		=> $row['itemtype'],
					"status"		=> $row['status'],
					"questiontype"	=> $row['questiontype'],
				);
			}
		}
		return $return;

	}

	public function addSkillsmartCompetencyQuestion($params,$compid){
		$db = $this->dbfunc();

		$id = $_POST['_iddetail'];
		$question = $_POST['_fieldtoupdatedetail'];
		$itemorder = $_POST['_orderdetail'];
		$itemtype = $_POST['_qtypedetail'];

		$query = "SELECT * FROM competencies_questions WHERE competencyid = " . $compid . " AND itemorder >= " . $itemorder;
#die($query);
		$select = $db->query($query);
		$itemstomove = $select->fetchAll();

		foreach ($itemstomove as $itm){
			// MOVING ITEMS BACK BY ONE TO OPEN A SPOT
			$upd = "UPDATE competencies_questions SET itemorder = " . ($itm['itemorder'] + 1) . " WHERE id = " . $itm['id'];
#die($upd);
			$db->query($upd);
		}

		$query = "INSERT INTO competencies_questions SET
			competencyid = '" . addslashes($compid) . "',
			question = '" . addslashes($question) . "',
			itemorder = '" . addslashes($itemorder) . "',
			itemtype = '" . addslashes($itemtype) . "',
			status = 1";
#die($query);
		$db->query($query);
		$this->skillsmartCompetencyCloseGaps($compid);
	}

	public function updateSkillsmartCompetencyQuestion($params,$compid){
		$db = $this->dbfunc();

		$id = $_POST['_iddetail'];
		$question = $_POST['_fieldtoupdatedetail'];
		$itemorder = $_POST['_orderdetail'];
		$itemtype = $_POST['_qtypedetail'];

		// MAKING A GAP IN THE NUMBERING FOR THE NEW ITEM
		$query = "SELECT * FROM competencies_questions WHERE competencyid = " . $compid . " AND id <> " . $id . " AND itemorder >= " . $itemorder;

#die($query);

		$select = $db->query($query);
		$itemstomove = $select->fetchAll();

		foreach ($itemstomove as $itm){
			// MOVING ITEMS BACK BY ONE TO OPEN A SPOT
			$upd = "UPDATE competencies_questions SET itemorder = " . ($itm['itemorder'] + 1) . " WHERE id = " . $itm['id'];
#die($upd);
			$db->query($upd);
		}

		$query = "UPDATE competencies_questions SET
			question = '" . addslashes($question) . "',
			itemorder = '" . addslashes($itemorder) . "',
			itemtype = '" . addslashes($itemtype) . "'
			WHERE id = " . $id;
#die($query);
		$db->query($query);
		$this->skillsmartCompetencyCloseGaps($compid);
	}

	function skillsmartCompetencyCloseGaps($compid){
		$db = $this->dbfunc();
		// CLOSING ANY NUMBERING GAPS AFTER MOVING/ADDING/UPDATING/DELETING ACTIONS
		$query = "SELECT * FROM competencies_questions WHERE competencyid = " . $compid . " AND status = 1 ORDER BY itemorder";
		$select = $db->query($query);
		$itemstomove = $select->fetchAll();
		$counter = 1;

		foreach ($itemstomove as $itm){
			// MOVING ITEMS TO COUNTER POSITION
			$upd = "UPDATE competencies_questions SET itemorder = " . $counter . " WHERE id = " . $itm['id'];
			$db->query($upd);
			$counter++;
		}
	}

	function skillsmartGetQualifications(){
		$db = $this->dbfunc();
		$query = "SELECT * FROM person_qualification_option WHERE parent_id IS NULL AND is_deleted = 0 ORDER BY qualification_phrase";
		$result = $db->query($query);
		$parentitems = $result->fetchAll();
		$return = array();
		foreach ($parentitems as $parent){
			$return[] = array("id" => $parent['id'], "label" => $parent['qualification_phrase']);
/*

			$subquery = "SELECT * FROM person_qualification_option WHERE parent_id = " . $parent['id'] . " AND is_deleted = 0 ORDER BY qualification_phrase";
			$subresult = $db->query($subquery);
			$childitems = $subresult->fetchAll();
			foreach ($childitems as $child){
				$return[$parent['qualification_phrase']][] = array(
					"id" => $child['id'],
					"label" => $child['qualification_phrase'],
				);
			}
*/
		}
		return $return;
	}

	function skillsmartLinkQualComp($params){
		$db = $this->dbfunc();
		$compid = $params['compid'];
		$parsed = array();
		foreach ($params['qual'] as $k=>$v){
			$parsed[] = $v;
			$query = "SELECT * FROM link_qualification_competency WHERE
				competencyid = '" . addslashes($compid) . "' AND qualificationid = '" . addslashes($v) . "'";
			$result = $db->query($query);
			$rows = $result->fetchAll();
			if (count ($rows) == 0){
				$query = "INSERT INTO link_qualification_competency SET
					competencyid = '" . addslashes($compid) . "',
					qualificationid = '" . addslashes($v) . "'";
				$db->query($query);
			}
		}

		// Removing any remaining items if they are no longer checked
		$query = "DELETE FROM link_qualification_competency WHERE competencyid = '" . addslashes($compid) . "' AND qualificationid NOT IN (" . implode(",", $parsed) . ")";

		$db->query ($query);
	}

	function skillsmartGetCompetencyLinks($compid){
		$return = array();
		$db = $this->dbfunc();
		$query = "SELECT * FROM link_qualification_competency WHERE
			competencyid = '" . addslashes($compid) . "'";
		$result = $db->query($query);
		$rows = $result->fetchAll();
		if (count ($rows) != 0){
			foreach ($rows as $row){
				$return[] = $row['qualificationid'];
			}
		}
		return $return;
	}

	//TA:#515 need to be rewritten. Performance issue. it calls for each person the same query. It si not optimal. Also uses SELECT * to fetch uncesessary data that overloads memory
	function getPersonCompetencies($pid, $get_secondary = false){
		$db = $this->dbfunc();
		$query = "SELECT * FROM person WHERE id = " . $pid;
#echo $query . "<br>";
		$result = $db->query($query);
		$rows = $result->fetchAll();
		$return = false;
		if (count ($rows) > 0){
			$return = array();
			$personrow = $rows[0];
			$qualification = $personrow['primary_qualification_option_id'];

			$qualification_ids = array();
			$qualification_ids[] = $qualification;
			if($get_secondary){
				$secondary_sql = "SELECT id FROM person_qualification_option WHERE parent_id = {$qualification}";
				$res = $db->query($secondary_sql);
				$qualrows = $res->fetchAll();
				foreach($qualrows as $row){
					$qualification_ids[] = $row['id'];
				}
			}

			$qualquery = "SELECT * FROM link_qualification_competency WHERE qualificationid IN (".implode(',', $qualification_ids).")";
			#echo $qualquery . "<br>";
			$qualresult = $db->query($qualquery);
			$qualrows = $qualresult->fetchAll();
			if (count ($qualrows) > 0){
				$comps = array();
				foreach ($qualrows as $qr){
					$comps[] = $qr['competencyid'];
				}

				// GETTING QUESTIONS FOR THIS COMP
				if (count ($comps) > 0){
					$compids = implode(",", $comps);

					// Getting all questions
					//TA:#515
// 					$qquery = "SELECT * FROM competencies_questions WHERE competencyid IN (" . $compids . ") AND status = 1";
					$qquery = "SELECT id FROM competencies_questions WHERE competencyid IN (" . $compids . ") AND itemtype like 'question%' AND status = 1";
#echo $qquery . "<br>";
					$qresult = $db->query($qquery);
					$qrows = $qresult->fetchAll();
					$questions = array();
					$required = 0;
					$qids = array();
					foreach ($qrows as $q){
						$questions[] = $q;
						//if (substr($q['itemtype'],0,8) == "question"){//TA:#515
							$required++;
							$qids[] = $q['id'];
						//}
					}

					// Getting all answers
					$aquery = "SELECT * FROM comp WHERE `question` IN (" . (count($qids) > 0 ? implode(",", $qids) : 0) . ") AND `person` = " . $pid . " AND `active` = 'Y'";
					$aresult = $db->query($aquery);
					$arows = $aresult->fetchAll();
					$answers = array();
					$unanswered = 0;
					foreach ($arows as $a){
						$answers[] = $a;
						if ($a['answer'] == "F"){
							$unanswered++;
						}
					}

					$return['competencies'] = $comps;
					$return['questions'] = $questions;
					$return['answers'] = $answers;
					$return['required'] = $required;
					$return['unanswered'] = $unanswered;
				}
			}
		}
		return $return;
	}

	function getPersonCompetenciesDetailed($pid){
		$db = $this->dbfunc();
		$query = "SELECT * FROM person WHERE id = " . $pid;
		$result = $db->query($query);
		$rows = $result->fetchAll();
		$return = false;
		if (count ($rows) > 0){
			$return = array();
			$personrow = $rows[0];
			$qualification = $personrow['primary_qualification_option_id'];

			$qualquery = "SELECT * FROM link_qualification_competency WHERE qualificationid = " . $qualification;
			$qualresult = $db->query($qualquery);
			$qualrows = $qualresult->fetchAll();
			if (count ($qualrows) > 0){
				$comps = array();
				foreach ($qualrows as $qr){
					$comps[] = $qr['competencyid'];
				}

				$structure = array();

				// GETTING QUESTIONS FOR THIS COMP
				if (count ($comps) > 0){
					foreach ($comps as $compid){
						$topquery = "SELECT * FROM competencies WHERE id = " . $compid;
						$topresult = $db->query($topquery);
						$toprows = $topresult->fetchAll();
						$toprow = $toprows[0];
						$_str = array(
							"name" => $toprow['competencyname'],
							"questions" => array(),
						);

						// Get all questions
						$qids = array();
						$qquery = "SELECT * FROM competencies_questions WHERE competencyid = " . $compid . " AND status = 1 ORDER BY itemorder";
						$qresult = $db->query($qquery);
						$qrows = $qresult->fetchAll();
						$required = 0;
						foreach ($qrows as $q){
							$_str['questions'][] = $q;
							$qids[] = $q['id'];
							if (substr($q['itemtype'],0,8) == "question"){
								$required++;
							}
						}
						$_str['required'] = $required;

						// Getting all answers
						$aquery = "SELECT * FROM comp WHERE `question` IN (" . (count($qids) > 0 ? implode(",", $qids) : 0) . ") AND `person` = " . $pid . " AND `active` = 'Y'";
						$aresult = $db->query($aquery);
						$arows = $aresult->fetchAll();
						$_str['answers'] = array();
						$unanswered = 0;
						foreach ($arows as $a){
							$_str['answers'][] = $a;
							if ($a['answer'] == "F"){
								$unanswered++;
							}
						}
						$_str['unanswered'] = $unanswered;
						$structure[] = $_str;
					}
				}
				$return = $structure;
			}
		}
		return $return;
	}

	public function saveCompetencyAnswers($questions,$pid){
		$db = $this->dbfunc();
		$parsed = array();
		foreach ($questions as $k=>$v){
			if (trim ($v) != ""){
				$query = "SELECT * FROM comp WHERE
					`question` = '" . addslashes($k) . "' AND
					`person` = '" . addslashes($pid) . "'";
				$result = $db->query($query);
				$rows = $result->fetchAll();
				if (count ($rows) == 0){
					$query = "INSERT INTO comp SET
						`person` = '" . addslashes($pid) . "',
						`question` = '" . addslashes($k) . "',
						`option` = '" . addslashes($v) . "',
						`active` = 'Y'";
					$db->query($query);
					$parsed[] = $this->dbfunc()->lastInsertId();
				} else {
					$row = $rows[0];
					$query = "UPDATE comp SET
						`option` = '" . addslashes($v) . "'
						WHERE id = " . $row['id'];
					$db->query($query);
					$parsed[] = $row['id'];
				}
			}
		}

		// Deactivating any remaining items if they are no longer checked
		$query = "UPDATE comp SET active = 'N' WHERE person = " . $pid . " AND id NOT IN (" . implode(",", $parsed) . ")";
		$db->query ($query);
	}
	public function getCompetencies(){
		$db = $this->dbfunc();
		$query = "SELECT * FROM competencies WHERE
			status = 1
			ORDER BY competencyname";
		$select = $db->query($query);
		$result = $select->fetchAll();
		return $result;
	}

	public function getQualificationCompetencies($cid = false){
		$db = $this->dbfunc();
		if ($cid !== false){
			$query = "SELECT * FROM person_qualification_option WHERE
				parent_id IS NULL AND
				id = " . $cid . " AND
				is_deleted = 0
				ORDER BY qualification_phrase";
		} else {
			$query = "SELECT * FROM person_qualification_option WHERE
				parent_id IS NULL AND
				is_deleted = 0
				ORDER BY qualification_phrase";
		}

		$return = array();

		$select = $db->query($query);
		$result = $select->fetchAll();
		if (count ($result) > 0){
			foreach ($result as $row){
				$_arr = array(
					"id"			=> $row['id'],
					"name"			=> $row['qualification_phrase'],
					"competencies"	=> array(),
				);

				$subquery = "SELECT c.*, lqc.id AS linkid
					FROM competencies c
					INNER JOIN link_qualification_competency lqc ON lqc.competencyid = c.id
					WHERE lqc.qualificationid = " . $row['id'] . " ORDER BY competencyname";
				$subselect = $db->query($subquery);
				$subresult = $subselect->fetchAll();
				foreach ($subresult as $subrow){
					$_arr['competencies'][] = array(
						"id"		=> $subrow['id'],
						"name"		=> $subrow['competencyname'],
						"linkid"	=> $subrow['linkid'],
					);
				}

				$return[] = $_arr;
			}
		}
		return $return;
	}


	public function saveUserInstitutions($uid,$ins){
		$db = $this->dbfunc();
		if (count ($ins) == 0){
			$query = "DELETE FROM link_user_institution WHERE userid = " . $uid;
			$this->dbfunc()->query($query);
		} else {
			$parsed = array();
			foreach ($ins as $k=>$v){
				if (trim ($v) != ""){
					$query = "SELECT * FROM link_user_institution WHERE
						`userid` = '" . addslashes($uid) . "' AND
						`institutionid` = '" . addslashes($v) . "'";
					$result = $db->query($query);
					$rows = $result->fetchAll();
					if (count ($rows) == 0){
						$query = "INSERT INTO link_user_institution SET
							`userid` = '" . addslashes($uid) . "',
							`institutionid` = '" . addslashes($v) . "'";
						$db->query($query);
					}
					$parsed[] = $v;
				}
			}

			// Deactivating any remaining items if they are no longer checked
			$query = "DELETE FROM link_user_institution WHERE
				`userid` = '" . addslashes($uid) . "' AND
				`institutionid` NOT IN (" . implode(",", $parsed) . ")";
			$db->query ($query);
		}
	}

	public function addUserInstitutionRights($uid,$id){
		$db = $this->dbfunc();
		// Checking if any rights exist. If blank, show all institutions
		$query = "SELECT * FROM link_user_institution WHERE
			`userid` = '" . addslashes($uid) . "'";
		$result = $db->query($query);
		$rows = $result->fetchAll();
		if (count ($rows) > 0){
			// Check if this institution is already linked
			$query = "SELECT * FROM link_user_institution WHERE
				`userid` = '" . addslashes($uid) . "' AND
				`institutionid` = '" . addslashes($id) . "'";
			$result = $db->query($query);
			$rows = $result->fetchAll();
			if (count ($rows) == 0){
				// Not linked yet. Adding a link
				$query = "INSERT INTO link_user_institution SET
					`userid` = '" . addslashes($uid) . "',
					`institutionid` = '" . addslashes($id) . "'";
				$db->query($query);
			}
		}
	}

	public function myid(){
		// Getting current credentials
		$auth = Zend_Auth::getInstance ();
		$identity = $auth->getIdentity ();
		return $identity->id;
	}

	public function updatePersonInstitution($tp = "student",$pid,$objectid){
		$db = $this->dbfunc();
		switch ($tp){
			default:
				// GETTING INSTITUTION ID
				$query = "SELECT * FROM cohort WHERE id = " . $objectid;
				$result = $db->fetchAll($query);
				if (count($result) > 0){
					$id = $result[0]['institutionid'];
				} else {
					$id = 0;
				}

				// UPDATING STUDENT RECORD
				$query = "UPDATE student SET institutionid = " . $id . " WHERE id = " . $pid;
				$db->query($query);
			break;
		}
	}

	public function getCompQuestions($compids){
		$db = $this->dbfunc();
		$ret = array();
		$query = "SELECT id FROM competencies_questions WHERE competencyid IN (" . implode(",", $compids) . ")";
		$result = $db->fetchAll($query);
		if (count ($result) > 0){
			foreach ($result as $row){
				$ret[] = $row['id'];
			}
		}
		return $ret;
	}

	public function getCourseTypes() {
		$db = $this->dbfunc();
        $s = $db->select()->from('lookup_coursetype', array('id', 'coursetype'))->order('coursetype ASC');
        return ($db->fetchPairs($s));
	}

    public function saveUserPrograms($uid, $ins)
    {
        $db = $this->dbfunc();
        if (count($ins) == 0) {
            $query = "DELETE FROM link_user_cadres WHERE userid = " . $uid;
            $this->dbfunc()->query($query);
        } else {
            $parsed = array();
            foreach ($ins as $k => $v) {
                if (trim($v) != "") {
                    $query = "SELECT * FROM link_user_cadres WHERE
						`userid` = '" . addslashes($uid) . "' AND
						`cadreid` = '" . addslashes($v) . "'";
                    $result = $db->query($query);
                    $rows = $result->fetchAll();
                    if (count($rows) == 0) {
                        $query = "INSERT INTO link_user_cadres SET
							`userid` = '" . addslashes($uid) . "',
							`cadreid` = '" . addslashes($v) . "'";
                        $db->query($query);
                    }
                    $parsed[] = $v;
                }
            }
            
            // Deactivating any remaining items if they are no longer checked
            $query = "DELETE FROM link_user_cadres WHERE
				`userid` = '" . addslashes($uid) . "' AND
				`cadreid` NOT IN (" . implode(",", $parsed) . ")";
            $db->query($query);
        }
    }
    
    //TA:#224.2
    public function get3TiersLocationsParentIds(){
        $db = Zend_Db_Table_Abstract::getDefaultAdapter ();
        $sql = 'SELECT DISTINCT
       f.id as id,
       f.location_id AS "zone1",
       l2.id as "zone2",
       l2.parent_id AS "zone3"
      FROM facility f
      LEFT JOIN location l1 ON f.location_id = l1.id
      LEFT JOIN location l2 ON l1.parent_id = l2.id
      ';
        return $db->fetchAll($sql);
    }
    
    //TA:#224
    public function getFacility3TiersLocationsParentInfo($facility_id, $loc_id){
        $db = Zend_Db_Table_Abstract::getDefaultAdapter ();
        $sql = 'SELECT DISTINCT 
f.id as "f_id",  
l1.id AS "region_id", 
l1.location_name AS "region_name", 
l2.id AS "district_id", 
l2.location_name AS "district_name",
l3.id AS "province_id",
l3.location_name AS "province_name"
FROM facility f 
LEFT JOIN location l1 ON f.location_id = l1.id 
LEFT JOIN location l2 ON l1.parent_id = l2.id 
LEFT JOIN location l3 ON l2.parent_id = l3.id
where f.id = ' . $facility_id;
        return $db->fetchRow($sql);
    }
}


?>
