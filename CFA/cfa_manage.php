<?php
/*
Gibbon, Flexible & Open School System
Copyright (C) 2010, Ross Parker

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program. If not, see <http://www.gnu.org/licenses/>.
*/

@session_start() ;

//Module includes
include "./modules/" . $_SESSION[$guid]["module"] . "/moduleFunctions.php" ;

if (isActionAccessible($guid, $connection2, "/modules/CFA/cfa_manage.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print __($guid, "Your request failed because you do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Get action with highest precendence
	$highestAction=getHighestGroupedAction($guid, $_GET["q"], $connection2) ;
	if ($highestAction==FALSE) {
		print "<div class='error'>" ;
		print __($guid, "The highest grouped action cannot be determined.") ;
		print "</div>" ;
	}
	else {
		//Get class variable
		$gibbonCourseClassID=NULL ;
		if (isset($_GET["gibbonCourseClassID"])) {
			$gibbonCourseClassID=$_GET["gibbonCourseClassID"] ;
		}
		else {
			try {
				if ($highestAction=="Manage CFAs_all") { //Full manage
					$data=array("gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"]); 
					$sql="SELECT gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonCourseClass.gibbonCourseClassID FROM gibbonCourse, gibbonCourseClass, gibbonCourseClassPerson WHERE gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID AND gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID AND gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND gibbonCourse.gibbonSchoolYearID=(SELECT gibbonSchoolYearID FROM gibbonSchoolYear WHERE status='Current') AND gibbonCourseClass.reportable='Y' ORDER BY course, class" ;
				}
				else { //Departmental tweaking
					$data=array("gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"], "gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"]); 
					$sql="SELECT gibbonCourseClass.gibbonCourseClassID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class FROM gibbonCourseClass JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) JOIN gibbonDepartment ON (gibbonCourse.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) JOIN gibbonDepartmentStaff ON (gibbonDepartmentStaff.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) WHERE gibbonDepartmentStaff.gibbonPersonID=:gibbonPersonID AND gibbonDepartmentStaff.role='Coordinator' AND gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonCourseClass.reportable='Y' ORDER BY course, class" ;
				}
				$result=$connection2->prepare($sql);
				$result->execute($data);
			}
			catch(PDOException $e) { 
				print "<div class='error'>" . $e->getMessage() . "</div>" ; 
			}
			if ($result->rowCount()>0) {
				$row=$result->fetch() ;
				$gibbonCourseClassID=$row["gibbonCourseClassID"] ;
			}
		}
		if ($gibbonCourseClassID=="") {
			print "<h1>" ;
				print "Manage CFA" ;
			print "</h1>" ;
			print "<div class='warning'>" ;
				print __($guid, "Use the class listing on the right to choose a CFA to edit.") ;
			print "</div>" ;
		}
		//Check existence of and access to this class.
		else {
			try {
				if ($highestAction=="Manage CFAs_all") { //Full manage
					$data=array("gibbonCourseClassID"=>$gibbonCourseClassID); 
					$sql="SELECT gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonCourseClass.gibbonCourseClassID FROM gibbonCourse, gibbonCourseClass WHERE gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID AND gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID ORDER BY course, class" ;
				}
				else {
					$data=array("gibbonCourseClassID"=>$gibbonCourseClassID, "gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"], "gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"]); 
					$sql="SELECT gibbonCourseClass.gibbonCourseClassID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class FROM gibbonCourseClass JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) JOIN gibbonDepartment ON (gibbonCourse.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) JOIN gibbonDepartmentStaff ON (gibbonDepartmentStaff.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) WHERE gibbonDepartmentStaff.gibbonPersonID=:gibbonPersonID AND gibbonDepartmentStaff.role='Coordinator' AND gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID ORDER BY course, class" ;
				}
				$result=$connection2->prepare($sql);
				$result->execute($data);
			}
			catch(PDOException $e) { 
				print "<div class='error'>" . $e->getMessage() . "</div>" ; 
			}

			if ($result->rowCount()!=1) {
				print "<h1>" ;
					print __($guid, "Manage CFA") ;
				print "</h1>" ;
				print "<div class='error'>" ;
					print __($guid, "The selected record does not exist, or you do not have access to it.") ;
				print "</div>" ;	
			}
			else {
				$row=$result->fetch() ;
				print "<div class='trail'>" ;
				print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . __($guid, "Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . __($guid, getModuleName($_GET["q"])) . "</a> > </div><div class='trailEnd'>" . __($guid, 'Manage') . " " . $row["course"] . "." . $row["class"] . " " . __($guid, 'CFAs') . "</div>" ;
				print "</div>" ;
			
				if (isset($_GET["deleteReturn"])) { $deleteReturn=$_GET["deleteReturn"] ; } else { $deleteReturn="" ; }
				$deleteReturnMessage="" ;
				$class="error" ;
				if (!($deleteReturn=="")) {
					if ($deleteReturn=="success0") {
						$deleteReturnMessage=__($guid, "Your request was completed successfully.") ;		
						$class="success" ;
					}
					print "<div class='$class'>" ;
						print $deleteReturnMessage;
					print "</div>" ;
				} 
		
				//Add multiple columns
				print "<div class='linkTop'>" ;
					print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/cfa_manage_add.php&gibbonCourseClassID=$gibbonCourseClassID'>" . __($guid, 'Add Multiple Columns') . "<img style='margin-left: 5px' title='" . __($guid, 'Add Multiple Columns') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/page_new_multi.png'/></a>" ;
				print "</div>" ;
		
				//Get teacher list
				$teaching=FALSE ;
				try {
					$data=array("gibbonCourseClassID"=>$gibbonCourseClassID); 
					$sql="SELECT gibbonPerson.gibbonPersonID, title, surname, preferredName FROM gibbonCourseClassPerson JOIN gibbonPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE role='Teacher' AND gibbonCourseClassID=:gibbonCourseClassID ORDER BY surname, preferredName" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { 
					print "<div class='error'>" . $e->getMessage() . "</div>" ; 
				}
				if ($result->rowCount()>0) {
					print "<h3>" ;
						print __($guid, "Teachers") ;
					print "</h3>" ;	
					print "<ul>" ;
						while ($row=$result->fetch()) {
							print "<li>" . formatName($row["title"], $row["preferredName"], $row["surname"], "Staff") . "</li>" ;
							if ($row["gibbonPersonID"]==$_SESSION[$guid]["gibbonPersonID"]) {
								$teaching=TRUE ;
							}
						}
					print "</ul>" ;
				}
		
				//Print mark
				print "<h3>" ;
					print __($guid, "CFA Columns") ;
				print "</h3>" ;	
		
				//Set pagination variable
				$page=1 ; if (isset($_GET["page"])) { $page=$_GET["page"] ; }
				if ((!is_numeric($page)) OR $page<1) {
					$page=1 ;
				}
		
				try {
					$data=array("gibbonCourseClassID"=>$gibbonCourseClassID); 
					$sql="SELECT * FROM cfaColumn WHERE gibbonCourseClassID=:gibbonCourseClassID ORDER BY completeDate DESC, name" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { 
					print "<div class='error'>" . $e->getMessage() . "</div>" ; 
				}

				if ($result->rowCount()<1) {
					print "<div class='error'>" ;
					print __($guid, "There are no records to display.") ;
					print "</div>" ;
				}
				else {
					print "<table cellspacing='0' style='width: 100%'>" ;
						print "<tr class='head'>" ;
							print "<th>" ;
								print __($guid, "Name") ;
							print "</th>" ;
							print "<th>" ;
								print __($guid, "Date<br/>Complete") ;
							print "</th>" ;
							print "<th>" ;
								print __($guid, "Actions") ;
							print "</th>" ;
						print "</tr>" ;
				
						$count=0;
						$rowNum="odd" ;
						while ($row=$result->fetch()) {
							if ($count%2==0) {
								$rowNum="even" ;
							}
							else {
								$rowNum="odd" ;
							}
					
							//COLOR ROW BY STATUS!
							print "<tr class=$rowNum>" ;
								print "<td>" ;
									print "<b>" . $row["name"] . "</b><br/>" ;
								print "</td>" ;
								print "<td>" ;
									if ($row["complete"]=="Y") {
										print dateConvertBack($guid, $row["completeDate"]) ;
									}
								print "</td>" ;
								print "<td>" ;
									print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/cfa_manage_edit.php&gibbonCourseClassID=$gibbonCourseClassID&cfaColumnID=" . $row["cfaColumnID"] . "'><img title='" . __($guid, 'Edit') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/config.png'/></a> " ;
									if ($highestAction=="Manage CFAs_all") { //Full manage
										print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/cfa_manage_delete.php&gibbonCourseClassID=$gibbonCourseClassID&cfaColumnID=" . $row["cfaColumnID"] . "'><img title='" . __($guid, 'Delete') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/garbage.png'/></a> " ;
									}
									print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/cfa_write_data.php&gibbonCourseClassID=$gibbonCourseClassID&cfaColumnID=" . $row["cfaColumnID"] . "'><img title='" . __($guid, 'Enter Data') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/markbook.png'/></a> " ;
								print "</td>" ;
							print "</tr>" ;
					
							$count++ ;
						}
					print "</table>" ;
				}
			}
		}
	}
	
	//Print sidebar
	$_SESSION[$guid]["sidebarExtra"]=sidebarExtra($guid, $connection2, $gibbonCourseClassID, "manage", $highestAction) ;
}		
?>