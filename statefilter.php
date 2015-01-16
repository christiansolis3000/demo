<?php
if(file_exists("../configuration/app.php"))
include("../configuration/app.php");
else
include("".$_SERVER["DOCUMENT_ROOT"]."/configuration/app.php");
//include("../configuration/app.php");



include($root."/connections/db.complylive.php");
include($root."/include/inc.functions.php");
include($root."/configuration/defines.php");
include($root."/include/inc.security.php");


//---------------- dynamic colors -------------------------
include($root."/connections/db.thinkhr.php");
include($root."/configuration/theme.php");
//----------------------------------------------------------

$conn = @ConnectToDb($dbServer, $dbUser, $dbPass, $dbName);

$_SESSION["firsttime"] = true;
// -------------------------------- //

$title = "ThinkHR | HR Comply | HR Live | HR Comply Resources | Human Resources Comply";
$keywords = "thinkhr comply, human resoures, hr live, hr comply, compliance, human resources compliance";
$description = "ThinkHR’s cloud-based resource center—supported by our helpdesk of HR specialists—is your solution.";
$url = "";
$fb_logo = "../imgs/logo_fb.gif";

if(strstr($_GET['id'],'/'))
{
	$sep = explode('.',$_GET['id']);
	//print_r($sep[0]);
	$_GET['id'] = $sep[0];
	$tabpath = str_replace("/","//",$_GET['id']);
}
$ids = $_GET['id'];
if(trim(substr($ids,strlen($ids)-1,1))=="/")
{
	$ids = substr($ids,0,strlen($ids)-1);	 
}
//echo $ids;
/*
$ids = str_replace("'","\'",$ids);
$ids = 259;
$row_tab_c["TabID"] = 259;*/

//print_r($_GET);
$tpath = "StateFilter";
$tpath1 = "StateLaws";

if($ids=="")
{
	//$ids = "StateFilter";	
}
else
{
	//$ids = "StateFilter/".$ids;	
	$tpath = "StateFilter/";
	$tpath1 = "StateLaws/";
}


$ids = str_replace("'","\'",$ids);


$tab_c= mysql_query("Select TabID,TabName,IsDeleted,IsVisible from tabs where (tabpath='//ReferenceHome//".str_replace("/","//",$tpath.$ids)."' 
								or tabpath='//ReferenceHome//".str_replace("/","//",$tpath1.$ids)."') and IsDeleted = 0");
								
							
$rstab_c = mysql_num_rows($tab_c);
$istab=0;
if($rstab_c > 0)
{ 
	$row_tab_c = mysql_fetch_array($tab_c,MYSQL_ASSOC);
	$TabIsDeleted = $row_tab_c["IsDeleted"];
	$TabIsVisible = $row_tab_c["IsVisible"];
	$istab = 1;
} 
else 
{
	$Path = "ReferenceHome//".str_replace("/","//",$ids);
	//echo $result;
	if($result!=""){
		//header('Location: http://thinkhrcomply.com'.str_replace("//","/",$result));
	}
}



/*$tpath = "StateFilter";

if($ids=="")
{
	//$ids = "StateFilter";	
}
else
{
	//$ids = "StateFilter/".$ids;	
	$tpath = "StateFilter/";
}



$tab_c= mysql_query("Select TabID,TabName,IsDeleted,IsVisible from tabs where tabpath='//ReferenceHome//".str_replace("/","//",$tpath.$ids)."' and IsDeleted = 0");

//echo "Select TabID,TabName,IsDeleted,IsVisible from tabs where tabpath='//ReferenceHome//".str_replace("/","//",$ids)."' and IsDeleted = 0";


$rstab_c = mysql_num_rows($tab_c);
$istab=0;
if($rstab_c > 0)
{ 
	$row_tab_c = mysql_fetch_array($tab_c,MYSQL_ASSOC);
	$TabIsDeleted = $row_tab_c["IsDeleted"];
	$TabIsVisible = $row_tab_c["IsVisible"];
	$istab = 1;
} 
*/


//print_r($_GET);
?>
<!DOCTYPE html>
<html lang="en">
    <head>
    	<?php 
		include($root."/administration/include/inc.head.php");?>
    </head>
    <body>
    <?php include($root."/administration/include/inc.header.php");?>
    <!--Container-->
    <div class="wrap-container bg-tabspage">
        <div class="container" id="thecontainer">
        <?php
		if($istab==1)
		{
			if ($_SESSION["thinkhrcomply_usr_sadmin"]=="1")
			{
				if(($row_tab_c["IsDeleted"] == "1") || ($row_tab_c["IsVisible"] == "0")){
					$allow = 2;
				} 
				else 
				{
					$allow = 1;
				}
			}
			else
			{
				$tabs_allowed_ = tabs_allowed(); 
				if((is_array($tabs_allowed_))&&(in_array($row_tab_c["TabID"],$tabs_allowed_)))
				{
					//$allow = 1;
					if( $row_tab_c["IsDeleted"] == 1 || $row_tab_c["IsVisible"] == 0){
						$allow = 4;
					} else {
						$allow = 1;
					}
				} else {
					if( $row_tab_c["IsDeleted"] == 1 || $row_tab_c["IsVisible"] == 0){
						$allow = 2;
					}
				}
			}
		}
		else
		{
			$allow = 3;	
		}
		$bnd = "";
		
		if($allow == 1)
		{
			include($root."/administration/include/page/inner-page-filter.php");
		}
		else
		{
			include($root."/administration/include/page/inner-novalid-page.php");	
			$bnd = 1;
		}
		 
		/*if($ids!="")
		{
			include($root."/administration/include/page/inner-page-filter.php");
		}
		else
		{
			//include($root."/administration/include/page/inner-page-states.php");
			include($root."/administration/include/page/inner-page-filter.php");
		}*/
		?>
        </div>
	</div>
	   <?php
		include($root."/administration/include/inc.footer.php");
		include($root."/administration/include/inc.scripts.php");
		if($bnd=="1"){}else{
		include($root."/administration/include/page/statefilter.scripts.php");
		}
?>	

	</body>       
</html>  		        