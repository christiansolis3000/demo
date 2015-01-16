<?php
if(file_exists("../../../configuration/app.php"))
include("../../../configuration/app.php");
else
include("../../configuration/app.php");
include($root."/connections/db.complylive.php");
include($root."/include/inc.functions.php");
include($root."/configuration/defines.php");
include($root."/configuration/defines-array.php");

extract($_POST);
$cond1 = "";
$cond2 = "";
$cond3 = "";
$datenow = "curdate()";
/*print_r($_GET);
print_r($_SESSION);*/

//print_r($_POST);




if (!isset($_POST["sort"])){
$orderby = "tabname";
} else {
$orderby = $_POST["sort"];
}

$tabid = $_GET["tabid"];
$flagmodule = "";


$tabid = "6940";
$s = "Alabama";



$sqllevel = mysql_query("select level, tabname, ShowTopics, ShowJurisdiction, ShowQuestions,TabPath,ParentId,Title from tabs where tabid='".$tabid."'");
$rowlevel = mysql_fetch_array($sqllevel,MYSQL_ASSOC);



$tpathurl = "";
if($rowlevel["level"]=="1")
{
	$tpathurl = $rowlevel["TabPath"];
	$tpathurl = str_replace("//","/",$tpathurl);
} 
else 
{
	$sqlurl = mysql_query("select TabPath from tabs where tabID='".$rowlevel["ParentId"]."'");
	$rowurl = mysql_fetch_array($sqlurl,MYSQL_ASSOC);
	$tpathurl = $rowurl["TabPath"];
	$tpathurl = str_replace("//","/",$tpathurl);
	
}


//---------------------------------------------------------
if (!isset($_POST["view"])){
$max_results = 20;
} else { 
$max_results = $_POST["view"];
}

if(!isset($_GET['p']))
{
	$page = 1;
}
else 
{
	$page = $_GET['p'];	
} 
if($page<=0)
{
	$page = 1;	
}
//echo $page;
$from = (($page * $max_results) - $max_results);
if($from<0)
{
	$from = 0;
}
$alias = "";
$nterm1 = "";

//print_r($_GET);
$arraycontentype = "";

$s = $_POST["state"]; 

if($s=="")
{
	$s = $_GET["thestate"];	
}
$s = str_replace("-"," ",$s);


//print_r($_SESSION["f_tabsids"]);
//print_r($_POST);
//print_r($_GET);

$bnd = 0;
$s1 = "";

if($_GET["refclick"]==0)
{
   //echo "si";
  // echo "*".$s."*";
  //$_SESSION["f_tabsids"]="";
   if($s!="")
   {
	   $sqltermstate = mysql_query("select termid,Name from taxonomy_terms where Name='".$s."' or (SELECT replace( Name, ' ', '' ))='".$s."'");
	   $rowtermstate = mysql_fetch_array($sqltermstate,MYSQL_ASSOC);
	   $s1 = $rowtermstate["Name"];
	   $_SESSION["f_termsid_j"] = "";

	   if(is_array($_SESSION["f_termsid_j"]))
	   {
			if(!in_array($rowtermstate["termid"],$_SESSION["f_termsid_j"]))
			{
				$_SESSION["f_termsid_j"][] = $rowtermstate["termid"];
			}			   
	   }
	   else
	   {
	   	   $_SESSION["f_termsid_j"] = "";
		   $_SESSION["f_termsid_j"][0] = $rowtermstate["termid"];
	   }
	   $bnd=1;
   }
   else
   {
		$_SESSION["f_termsid_j"] = "";   
	}
   
   $arraycontentype = $_SESSION["f_termsid_c"];
   $arrayjurisdiction = $_SESSION["f_termsid_j"];	
   $arraypolicy = $_SESSION["f_termsid_p"];
   $arraytopic = $_SESSION["f_tabsids"];	
   $arraythistime = $_SESSION["f_thistimes"]; 	
   
   if($arrayjurisdiction[0]=="")
   {
	  $arrayjurisdiction = "";
   }
   
}
else
{
	//$arraycontentype = $_POST["contenttype"];		
	$arrayjurisdiction = $_POST["jurisdiction"];
	
	$arraypolicy = $_POST["contentpolicy"];
	$arraytopic = $_POST["topics"];	
	$arraythistime = $_POST["thistime"]; 	
}

if(is_array($arrayjurisdiction) && ( sizeof($arrayjurisdiction) > 0 ) )
{
	if(!in_array("179",$arrayjurisdiction))
	{
		// ---------------------------------------------------- $arrayjurisdiction[] = "179";	
		//-----------------------   --------------------------- $_SESSION["f_termsid_j"][] = "179";	
	}
	if ( count($arrayjurisdiction) === 1 && in_array("179",$arrayjurisdiction) ) {
		$arrayjurisdiction = "";	
		$_SESSION["f_termsid_j"] = "";
	}
}


/*
if(is_array($arraycontentype))
{
   $k=0;
   $alias = "vwc.";
   $term1 = " INNER JOIN tbl_content_type vwc ON tmp.contentitemid = vwc.contentitemid AND (";
   $nterm1 = " (t.contentitemid in (select vwc.contentitemid from tbl_content_type vwc where vwc.contentitemid = t.contentitemid and (";
   //$_SESSION["termsid"] = $arraycontentype;
   $_SESSION["f_termsid_c"] = $arraycontentype;
   while($k < sizeof($arraycontentype))
   {
		$term1 .= " vwc.TermID=".$arraycontentype[$k]." ";
		$nterm1 .= " vwc.TermID=".$arraycontentype[$k]." ";
		if(($k+1)<sizeof($arraycontentype))
		{
			$term1 .= " or ";
			$nterm1 .= " or ";
		}
		if(is_array($_SESSION["f_termsid"]))
		{
			if(!in_array($arraycontentype[$k],$_SESSION["f_termsid"]))
			{
				$_SESSION["f_termsid"][] = $arraycontentype[$k];
				//$_SESSION["termsid_c"][] = $_POST["contenttype"][$k];
			}
		}
		else
		{
			$_SESSION["f_termsid"][0] = $arraycontentype[$k];
			//$_SESSION["termsid_c"][0] = $_POST["contenttype"][$k];
		}
		$k++; 
   }
   $term1 .= ")";   
   $nterm1 .= ")))";   
   
}
else
{
	$term1 = " LEFT OUTER JOIN tbl_content_type vwc ON t.contentitemid = vwc.contentitemid ";
	$alias = "vwc.";
}
if(!is_array($_POST["contenttype"]))
{
	if($_GET["refclick"]=="1")
	{
		$_SESSION["f_termsid"]="";
		$_SESSION["f_termsid_c"]="";
	}
}
*/



if( is_array($arrayjurisdiction) && ( sizeof($arrayjurisdiction) > 0 ) )
{
   $k=0;
   //$alias = "vwj";
   $nterm2 = "";
   $term2 = " INNER JOIN tbl_jurisdiction vwj ON tmp.contentitemid = vwj.contentitemid and ( vwj.TermID!=113 and vwj.TermID!=179  ) AND (";
   $nterm2 = " (t.contentitemid in (select vwj.contentitemid from tbl_jurisdiction vwj where vwj.contentitemid = t.contentitemid and ( vwj.TermID!=113 and vwj.TermID!=179  ) and (";
   // $_SESSION["termsid"] = $arraycontentype;
  
   $arrayjurisdiction = array_filter($arrayjurisdiction);
   $arrayjurisdiction = array_merge($arrayjurisdiction);
   
   $_SESSION["f_termsid_j"] = $arrayjurisdiction;   
   //print_r($arrayjurisdiction);
   
   
   $xterm2 = " ( ";
   
   while($k < sizeof($arrayjurisdiction))
   {
		if ($arrayjurisdiction[$k] != "") {
		
			$term2 .= " vwj.TermID=".$arrayjurisdiction[$k]." ";
			$nterm2 .= " vwj.TermID=".$arrayjurisdiction[$k]." ";
			$xterm2 .= " ct.TermID=".$arrayjurisdiction[$k]." ";
		}
		if(($k+1)<sizeof($arrayjurisdiction))
		{
			if ($arrayjurisdiction[$k] != "") {
				$term2 .= " or ";
				$nterm2 .= " or ";
				$xterm2 .= " or ";
			}
		}
		if(is_array($_SESSION["f_termsid"]))
		{
			if(!in_array($arrayjurisdiction[$k],$_SESSION["f_termsid"]))
			{
				$_SESSION["f_termsid"][] = $arrayjurisdiction[$k];
			}
		}
		else
		{
			$_SESSION["f_termsid"][0] = $arrayjurisdiction[$k];
		}		
		$k++; 
   }
   $term2 .= ")";
   $xterm2 .= ")";
   $nterm2 .= ")))";
}
else
{	
   $nterm2 = "";
   $term2 = " INNER JOIN tbl_jurisdiction vwj ON tmp.contentitemid = vwj.contentitemid and ( vwj.TermID!=113 and vwj.TermID!=179  ) ";
   $nterm2 = " (t.contentitemid in (select vwj.contentitemid from tbl_jurisdiction vwj where vwj.contentitemid = t.contentitemid and ( vwj.TermID!=113 and vwj.TermID!=179  )  ))";
}
/*
echo $nterm2;
echo "<br>".$term2;
print_r($arrayjurisdiction);
*/
if(!is_array($_POST["jurisdiction"]))
{
	if($_GET["refclick"]=="1")
	{
		$_SESSION["f_termsid"]="";
		$_SESSION["f_termsid_j"]="";
	}
}


//==========================================================================
$condrows = "";
if(($nterm1!="")||($nterm2!="")||($nterm3!=""))
{
	$condrows = " ( ";
	if($nterm1!="")
	{
		$condrows.= $nterm1;
		if($nterm2!="")
		{
			$condrows.= " and ";
			$condrows.= $nterm2;
			if($nterm3!="")
			{
				$condrows.= " and ";
				$condrows.= $nterm3;		
			}
		}
		else if($nterm3!="")
		{
			$condrows.= " and ";
			$condrows.= $nterm3;	
		}
	}
	else if($nterm2!="")
	{	
		$condrows.= $nterm2;
		if($nterm3!="")
		{
			$condrows.= " and ";
			$condrows.= $nterm3;
		}
	}
	else
	{
		$condrows.= $nterm3;		
	}
	$condrows .= " ) ";
}

$wherec = "";

if(!is_array($_POST["topics"]))
{
	if($_GET["refclick"]=="1")
	{
		$_SESSION["f_tabsids"]="";
		unset($_SESSION["f_tabsids"]);
	}
}
else
{
	$_SESSION["showcontent"]="";	
}

if(is_array($arraytopic))
{
	$_SESSION["f_tabsids"] = $arraytopic;
	$t = 0;
	while($t < sizeof($arraytopic))
	{
		$wherec .= "categoryid = ".$arraytopic[$t]."";
		if(($t+1) < sizeof($arraytopic))
		{
			$wherec .= " or ";	
		}	
		$t++;
	}
}
else
{
	if($bnd==1)
	{
		$bnd = 2;	
	}	
}
if($wherec!="")
{
	$wherec = " and ( ".$wherec."  ) ";	
}



$alias = "vwc.";

//echo "-".$wherec."-";

$query = "SELECT tmp.x AS tabid,tmp.tabname,t.contentitemid,t.tabpath as 'path',".$alias."termid as 'termid'
FROM ( SELECT distinct SQL_BIG_RESULT tr.tabid as 'x', tr.tabname, tr.contentitemid from tbl_resume tr WHERE tr.tabid > 0 ";

if($wherec=="")
{
	$query.= " and tr.categoryid in 
	(SELECT children.TabID idchildren FROM tabs parent, tabs children WHERE parent.TabID = children.ParentID AND parent.TabName = 'Reference Home' 
		AND parent.IsDeleted =0 AND children.IsVisible = '-1' AND children.IsDeleted =0 AND children.TabID!=5712)
 ";	
}
$query.= $wherec." ".$condtime." 
    
   $newcondition
GROUP BY tr.tabname,tr.tabid ORDER BY tr.tabname ) AS tmp";

$querytaxonomyterms = 't.termid = tt.termid and';
$fieldtaxonomyterms = ', tt.termord';


//echo "*".$wherec."*";
/*if($wherec=="")
{
	$wherec .= " and t.categoryid = 5712 "; 	
}*/
$queryrows = "select distinct SQL_BIG_RESULT t.* from tbl_resume t, taxonomy_terms tt where ".$querytaxonomyterms."  t.tabid > 0 ".$wherec." ".$condtime."";



$querytot = $query." LEFT OUTER JOIN tbl_content_type vwc ON tmp.contentitemid = vwc.contentitemid  ".$term2.$term3;
$querytotj = $query." LEFT OUTER JOIN tbl_jurisdiction vwj ON tmp.contentitemid = vwj.contentitemid  ";

$querytot = str_replace("tmp.x AS tabid,tmp.tabname,t.contentitemid,t.tabpath as 'path',","",$querytot);
$querytotj = str_replace("tmp.x AS tabid,tmp.tabname,t.contentitemid,t.tabpath as 'path',","",$querytotj);

$querytot = str_replace("vwc.termid as 'termid'","vwc.termid,count(vwc.termid) as 'num',(select tt.Name from taxonomy_terms tt where vwc.termid=tt.termid) as 'Name', (select tt1.TermOrd from taxonomy_terms tt1 where vwc.termid=tt1.termid) as 'TermOrd' ",$querytot);
$querytotj = str_replace("vwc.termid as 'termid'","vwj.termid,count(vwj.termid) as 'num',(select tt.Name from taxonomy_terms tt where vwj.termid=tt.termid) as 'Name'",$querytotj);


$querytot .= "GROUP BY termid ".$having." order by TermOrd";
$querytotj .= " GROUP BY termid ".$having." order by IF(name = 'Federal', 0, 1), Name ASC";


if($condrows!="")
{
	$queryrows .= " and ".$condrows."";
	
}


$condcontenttype3first = "and (tt.termid = 198 or tt.termid = 119 or tt.termid = 105)";
$condcontenttype4 = "and (tt.termid = 106 or tt.termid = 107 or tt.termid = 108 or tt.termid = 109)";
$cond_onefilter = "(". str_replace("t.*","t.*, tt.ntermord, 1 as q", $queryrows). "and level = 3 ". $condcontenttype3first.$having ." order by t.level desc, tt.ntermord asc, t.tabname)";
$cond_twofilter = "(". str_replace("t.*","t.*, tt.ntermord, 2 as q", $queryrows). "and level = 2 ". $condcontenttype3first.$having ." order by t.level desc, tt.ntermord asc, t.tabname)";
$cond_threefilter = "(". str_replace("t.*","t.*, tt.ntermord, 3 as q", $queryrows. "and level = 1 ". $condcontenttype3first.$having ." order by t.level desc, tt.ntermord asc, t.tabname)");
$cond_fourfilter = "(". str_replace("t.*","t.*, tt.ntermord, 4 as q", $queryrows). "and (level = 3 or level = 2 or level = 1 or level = 0)". $condcontenttype4.$having . "order by t.level desc, tt.ntermord asc, t.tabname)";



$param_month = date("m")-2;
$param_year = date("Y");

$queryrows = $cond_onefilter ." UNION ". $cond_twofilter . " UNION " . $cond_threefilter . " UNION " . $cond_fourfilter . " order by 
	CASE q WHEN '1' THEN level END DESC,
	CASE q WHEN '2' THEN level END DESC,
	CASE q WHEN '3' THEN level END DESC,
	CASE q WHEN '4' THEN level END DESC,
	ntermord asc, $orderby ";
	
if($bnd==2)
{
	$queryrows = "select distinct SQL_BIG_RESULT t.* from tbl_resume t,tabs ta,contentitems_tags ct
					where  t.tabid > 0 and t.categoryid='5712' ".$wherec." ".$condtime." and t.contentitemid=ct.ContentItemID and ".$xterm2."
						and t.tabid= ta.TabID and ta.Year = '".$param_year."' order by ta.Month desc
					";
					//and ta.Month >= '".$param_month."'

}	
	
	

//echo $orderby;
//echo $queryrows;

//select inside topics level 2 ================================

$querytmp = "select * from ( ";
$queryleveltopics = " ) as tmp 
where tmp.categoryid 
in (SELECT children.TabID idchildren
FROM tabs parent, tabs children
WHERE parent.TabID = children.ParentID
AND parent.TabName = 'Reference Home'
AND parent.IsDeleted =0
AND children.IsVisible = '-1'
AND children.IsDeleted =0) 
";
if((is_array($arraytopic))&&(sizeof($arraytopic)> 0))
{
	
}
else
{
	$queryrows = $querytmp.$queryrows.$queryleveltopics;
}
//echo $queryrows;

//echo $queryrows;

$querylimit = $queryrows. " LIMIT $from, $max_results";



$sqlrowssearch = mysql_query($queryrows);
try{
//mysql_query("INSERT INTO queryslog(`Query`, `Line`, `UserID`, `DateExecute`) VALUES('".addslashes($queryrows)."','623','".$_SESSION["thinkhrcomply_usr_idn"]."','".date("Y-m-d H:i:s")."')");
$nrows = mysql_num_rows($sqlrowssearch);
}
catch(Exception $e){
}
$numreg = $nrows;







        $sqlparents = mysql_query("SELECT children.TabID idchildren,children.TabName
												FROM tabs parent, tabs children
												WHERE parent.TabID = children.ParentID
												AND parent.TabName = 'Reference Home'
												AND parent.IsDeleted =0
												AND children.IsVisible = '-1'
												AND children.IsDeleted =0 and children.TabID!=5712 and children.TabName not in('State Filter','State Law','State Laws')
												ORDER BY children.TabOrder");
		while($rowparents = mysql_fetch_array($sqlparents,MYSQL_ASSOC))
		{										
		?>
        <?php
		$cc = 0;
		$arraytopicsorder = "";
		/*while($rowparents = mysql_fetch_array($sqlparents, MYSQL_ASSOC))
		{*/
			$sqlitems = mysql_query("SELECT t.TabID, t.TabName
								FROM tabs t
								WHERE t.IsVisible = '-1'
								AND t.Level =2
								AND t.IsDeleted =0
								AND t.ParentId='".$rowparents["idchildren"]."'");
			while($rowitems = mysql_fetch_array($sqlitems, MYSQL_ASSOC))
			{
				$sqldocs = mysql_query("select tmp.tabid as 'TabID',tmp.tabname as 'TabName',tmp.tabpath as 'TabPath' from tbl_resume tmp ".$term2.", tabs t  where tmp.categoryid='".$rowitems["TabID"]."'
														and tmp.tabid=t.TabID and t.IsDeleted =0 AND t.IsVisible = '-1' and t.tabid in (select distinct tm.TabID from tabmodules tm where tm.TabID=t.TabID )");
				/*$sqldocs = mysql_query("select tmp.tabid as 'TabID',tmp.tabname as 'TabName',tmp.tabpath as 'TabPath' from tbl_resume tmp ".$term2.", tabs t  where tmp.categoryid='".$rowitems["TabID"]."'
														and tmp.tabid=t.TabID and t.IsDeleted =0 AND t.IsVisible = '-1'");*/
				
				
				while($rowdocs = mysql_fetch_array($sqldocs, MYSQL_ASSOC))
				{
					$sqlmod = mysql_query("select distinct TabID from tabmodules where TabID='".$rowdocs["TabID"]."' and IsDeleted='0'");
					$nrowsmod = mysql_num_rows($sqlmod);
					if(($rowdocs["TabID"] > 0)&&($nrowsmod>0))
					{
						$arraytopicsorder[stripslashes($rowdocs["TabName"])]["TabID"] = $rowdocs["TabID"];	
						$arraytopicsorder[stripslashes($rowdocs["TabName"])]["TabPath"] = $rowdocs["TabPath"];
					}
				}
			}
		//}
		
		//print_r($arraytopicsorder);
		
		$arrayorder = ksort($arraytopicsorder);
		//print_r($arraytopicsorder);
		$cc = 0;
		if(is_array($arraytopicsorder))
		{
		?>
       <div class="widget">
        <div class="head">
            <h2><?php echo $rowparents["TabName"]; ?></h2> 
            <ul class="buttons tab-button">                                                        
                <li><a href="javascript:;" class="cblock"><span class="icos-menu"></span></a></li>
            </ul>
        </div> 
        <div class="block">
            <ul class="questions-list">
            <?php
            foreach($arraytopicsorder as $camp => $val)
            {
                $lnk = "";
                $lnk = $val['TabPath'];
                $lnk = str_replace("///","/",$lnk);
                $lnk = str_replace("//","/",$lnk);
                $name = stripslashes($camp);
                //$name = str_replace($s,"",$name);
				//$name = str_replace($s1,"",$name);
				?>
                <li class="row<?php if(($cc%2)==0){ echo "a";}else{ echo "b";}?>" style="padding:7px 10px;">
                	<?php /*<a href="<?php echo $lnk; ?>" style="font-size:14px;"><?php echo $name; ?></a>*/?>
                    <a href="javascript:;" onclick="OpenPage('<?php echo $val["TabID"]; ?>','<?php echo $lnk; ?>')" style="font-size:14px;"><?php echo $name; ?></a>
                    <?php /*
                    <div class="checkbox">
                        <label class="inpt-mrg" style="font-size:14px;">
                            <input type="checkbox" class="topics" id="chk_<?php echo $val["TabID"]; ?>" value="<?php echo $val["TabID"]; ?>" name="topics[]" 
                            <?php if(is_array($_SESSION["f_tabsids"])){ if(in_array($val["TabID"],$_SESSION["f_tabsids"])){ echo "checked";}	} ?>><?php echo stripslashes($camp); ?>
                            <span id="num_<?php echo $val["TabID"]; ?>"> <?php echo $val["numitems"]; ?> </span>
                        </label>
                    </div>*/?>
                </li>
                <?php
                $cc++;
            }
            ?>
            </ul>
        </div>   
    	</div>
        <?php
		}
	}
		?>