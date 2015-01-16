<?php
header('Access-Control-Allow-Origin: *');
header("Content-Type: application/json", true);
header('Content-Type: text/html; charset=utf-8');

function encrypt_decrypt($action, $string) {
    $output = false;

    $encrypt_method = "AES-256-CBC";
    $secret_key = 'comply2014';
    $secret_iv = 'comply2014';

    // hash
    $key = hash('sha256', $secret_key);
    
    // iv - encrypt method AES-256-CBC expects 16 bytes - else you will get a warning
    $iv = substr(hash('sha256', $secret_iv), 0, 16);

    if( $action == 'encrypt' ) {
        $output = openssl_encrypt($string, $encrypt_method, $key, 0, $iv);
        $output = base64_encode($output);
    }
    else if( $action == 'decrypt' ){
        $output = openssl_decrypt(base64_decode($string), $encrypt_method, $key, 0, $iv);
    }

    return $output;
}
if ($_POST['login'] == 'Y') { 

	$username = 	$_POST['username'];
	$password = 	$_POST['passkey'];
	$attempt = 		'y';
	$referrer = 	$_SERVER['HTTP_REFERER'];
	$sentBroker = 	$_POST['brokerID'];
	$redirect_page = "";
	if(isset($_POST["hdnredirectpage"]) && $_POST["hdnredirectpage"] !=""){
		$redirect_page = $_POST["hdnredirectpage"];
	}
	
	require('../Connections/portal_connect.php');
	mysql_select_db($database_portal_connect, $portal_connect);
	
	// do many emails exist for this email?
	// if many emails are there (over 1), then we check the Broker match
	// we also need the sentBroker to be known as well. 
	
	$query = "SELECT contactID FROM contacts WHERE Email = '$username' AND active = '1'";
	$countEmail = mysql_query($query, $portal_connect);
	$row_countEmail = mysql_fetch_assoc($countEmail);
	if (mysql_num_rows($countEmail) > '1' && $sentBroker >0) $brokerCheck = "AND clients.Broker = '$sentBroker'";
	
	//	no blanks...

	if  ($username == "") { $username = "none"; }
	if  ($password == "") { $password = "none"; }

	// okay. look in the DB for this user and password...

	$query_getLogin = "
		SELECT 
			Email, First_Name, Last_Name, Password, contacts.contactID, Title, Phone, 
			DATE_FORMAT(contacts.terms, '%Y-%m-%d') as lastDate, contacts.master, contacts.accountID,
			CONCAT_WS(' ', First_Name, Last_Name) AS client_contact, contacts.master AS masterUser, 
			Email as username, Email as email, Password AS passkey, First_Name as firstName, Last_Name as lastName, 

			clients.clientID, clients.clientID AS Client_ID,
			clients.Client_Name AS clientName, clients.Client_Name AS Client_Name,
			clients.Client_Type AS clientType,
			clients.Client_Since AS Client_Since,
			clients.industry AS industry,
			clients.companySize AS companySize,
			clients.avoidTerms AS avoidTerms,
			clients.Broker AS Broker,
		
			brokers.newLook, 
			brokers.Client_Type AS brokerType,
			brokers.Client_Name AS brokerName, 
			brokers.clientID AS brokerID, 

			(SELECT state FROM locations WHERE locations.Client_ID = clients.clientID LIMIT 1) as state,
			(SELECT zip FROM locations WHERE locations.Client_ID = clients.clientID LIMIT 1) as zip
		FROM contacts 
		INNER JOIN clients ON clients.clientID = contacts.Client_ID
		INNER JOIN clients AS brokers ON brokers.clientID = clients.Broker
		WHERE contacts.Email='$username' 
			AND contacts.active = '1' 
			AND contacts.Password='$password'
			$brokerCheck
		";
		
	$getLogin 			= mysql_query($query_getLogin, $portal_connect) or die(mysql_error());
	$row_getLogin 		= mysql_fetch_assoc($getLogin);
	$totalRows_getLogin = mysql_num_rows($getLogin);
	
	// match found if total rows > 0 ...

	if ($totalRows_getLogin > 0 && $row_getLogin['brokerType'] != "content_partner") {
		extract($row_getLogin);
		$destination 	= "hrhotline_client_profile.php";
		session_start();
		setcookie("brokerID", $row_getLogin['Broker']);
		$sessionNeeds 	= array("username", "email", "firstName", "Client_Name", "lastName", "passkey", "clientID", "contactID", "brokerID", "clientID", "masterUser", "accountID", "newLook");
		foreach ($sessionNeeds as $sneed) $_SESSION[$sneed] = $$sneed;
		
		$q = "
			SELECT SUM(Monthly_Amount) * 12 AS totalIncome
			FROM clients 
			LEFT JOIN clients_contracts ON clients_contracts.Client_ID = clients.clientID
			LEFT JOIN clients_products ON clients_products.contractID = clients_contracts.relID
			WHERE clientID = '$brokerID' AND clients_contracts.active = '1'
			";
		$r = mysql_query($q, $portal_connect); $rs = mysql_fetch_assoc($r); $_SESSION['totalIncome'] = $rs['totalIncome'];
	
		if ($row_getLogin['newLook'] == '1') $destination = "index.php";

		// =======================================================================================================
		// --- the next section is for redirecting (heading to comply). Since the totango info is tracked here, we need the extra client info (sales rep, contract value, etc.) 
		// =======================================================================================================

		if($redirect_page !=""){
			$destination = $redirect_page;

			// =======================================================================================================
			// --- totango code
			// =======================================================================================================
			?>			
			<!DOCTYPE html>
			<html lang="en">
			<head>
				<meta charset="utf-8">
			<script>
              // Totango initial tracker.
              var tracker_name = "totango";
              window.totango_tmp_stack = [];
              window[tracker_name] = {
                  track: function(activity, module, org, user) {
                      window.totango_tmp_stack.push({
                          activity:activity, module:module, org:org, user:user
                      });
                      return -1;
                  }
              };
            </script>
			</head>
			<body>
            <!-- Totango Tracking Code -->
            <? $partnerUser = ($brokerID == $clientID) ? "1" : "0";
            if ($_SESSION['brokerID'] == $_SESSION['clientID']) {

				require_once($_SERVER['DOCUMENT_ROOT']."/portal/profiles/support/support_menu.php");	// for obtaining the user name from the user number (userID) 
				$q = "SELECT customerSuccessManager, Sales_Person
						FROM clients_contracts
						INNER JOIN clients ON clients.clientID = '$brokerID'
						LEFT JOIN clients_products ON clients_products.contractID = clients_contracts.relID
						WHERE clients_contracts.Client_ID = '$brokerID' and active = '1'";
				$r = mysql_query($q, $portal_connect) or die(mysql_error()); $rs = mysql_fetch_assoc($r);
				do { 
					if ($rs['customerSuccessManager'] != "") $_SESSION['customerSuccessManager'] = getSalesPerson($rs['customerSuccessManager'], $portal_connect);
					if ($rs['Sales_Person'] != "") $_SESSION['Sales_Person'] =  getSalesPerson($rs['Sales_Person'], $portal_connect);
				} while ($rs = mysql_fetch_assoc($r));	// store these values (since more than one)

                $partnerValues = 	",\n\t\"Contract Value\": \"".$_SESSION['totalIncome']."\",";
                $partnerValues.= 	"\n\t \"Sales Rep\": \"".$_SESSION['Sales_Person']."\",";
                $partnerValues.= 	"\n\t \"Customer Success Manager\": \"".$_SESSION['customerSuccessManager']."\"";
            }
            ?>
            <script>
            
            var totango_options = {
              service_id: "SP-7929-01",           		// Your unique Totango service id. Do not change this.
              user: {
                id: "<?=$contactID;?>",             	// replace with the tracked user's email or unique id. 
                name: "<?=$client_contact;?>",			// full name of user logged in
                masterRole: "<?=$_SESSION['masterUser'];?>",			// master user? 0=No, 1=Yes
                title: "<?=$Title;?>",
                partnerUser: "<?=$partnerUser;?>",
                clientType: "<?=$clientType;?>",
                userAgent: "<?=$_SERVER['HTTP_USER_AGENT'];?>",
                phone: "<?=$Phone;?>"
              },
              
              account:  
              {
                partnerID: 	"<?=$brokerID;?>",
                broker: "<?=$brokerName?>" ,        
                companySize:"<?=$companySize;?>", 
                id: 		"<?=$clientID;?>",         	// replace with the tracked account's unique id.
                name: 		"<?=$_SESSION['Client_Name'];?>",  	// replace with the tracked account's account display name.
                status: 	"Paying",                 	// or 'paying'.
                clientType: "<?=$clientType;?>",
                "Create Date": "<?=$Client_Since;?>",   	// Optional - The date that the tracked account was created.
                "industry": "<?=$industry;?>",
                "state":	"<?=$state;?>",
                "zip":		"<?=$zip;?>"<?=$partnerValues;?>
              }
              // For more options and examples see http://help.totango.com/developers/
            };
            // from anywhere within your code.
            (function() {var e = document.createElement('script'); e.type = 'text/javascript'; e.async = true; e.src = 'https://s3.amazonaws.com/totango-cdn/totango3.js'; var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(e, s);})();
            </script>
            <!-- End Totango Tracking Code -->
            <? if ($_SESSION['trackedWorkspace'] != '4') {
            
                $theDate = date("Y-m-d H:i:s");
                $browser = $_SERVER['HTTP_USER_AGENT'];
                $q = "INSERT INTO temp_trackWorkplace 
                        SET theDate = '$theDate', contactID = '$contactID', brokerID = '$brokerID', client_name = '$Client_Name', user_name = '$client_contact', broker_name = '$brokerName', browser = '$browser',
                            clientID = '$clientID', description = 'primary login', comply = '1'";
                $r1 = mysql_query($q, $portal_connect) || die(mysql_error());
             ?>
            <script>totango.track("Workspace Login", "Workplace"); </script>
            <? $_SESSION['trackedWorkspace'] = '4';
             }
 			?>
			<a class='autologin' id='autologin_1' style='display:none;'>Autologin</a>
            <form name="frmautologin" id="frmautologin" method="post" action="<?php echo $destination; ?>">
				<input type="hidden" name="trialtimes" id="trialtimes" value="<?= encrypt_decrypt('encrypt', $_SESSION["username"]."|".$_SESSION['brokerID']."|".$_SESSION['contactID']."|".$_SESSION['clientID']); ?>"/>
                <input type="hidden" name="trialredirecturl" id="trialredirecturl" value="<?php echo $destination; ?>"/>
			</form>
			</body></html>
		 <?php
		}
		
		// customClient

			$query_getCustom = "SELECT customClient, Client_Type, partnerAdmin FROM clients WHERE clientID = '$brokerID'";
			$getCustom = mysql_query($query_getCustom, $portal_connect) or die(mysql_error());
			$row_getCustom = mysql_fetch_assoc($getCustom);
			$_SESSION['customClient'] = $row_getCustom['customClient'];
			$_SESSION['brokerType'] = $row_getCustom['Client_Type'];

		// is client a broker? 
		
			if ($row_getLogin['clientType'] == 'broker_partner' || $row_getLogin['partnerAdmin'] == '1') {
				$_SESSION['broker'] = 'y';
			}
			if ($row_getLogin['partnerAdmin'] == '1') $_SESSION['master_admin'] = 'Y';
			
		// exceptions for clients
		
			if ($clientID == '32780') $_SESSION['broker'] = 'y'; 		// broker access for KTiffany
		
		// special routine to update the counter and tell it that this user logged in today...
		
			$query_update= "update clients set login = login + 1 where clientID = '$clientID'";
			$rs_update = mysql_query($query_update);

		// stamp the login in the client_accessLog table
			$browser = addslashes($_SERVER['HTTP_USER_AGENT']);
			$dateTime = date("Y-m-d H:i:s");
			$query = "INSERT INTO clients_accessLog (
				clientID, contactID, brokerID, email, theDate, browser
			) VALUES (
				'$clientID', '$contactID', '$brokerID', '$email', '$dateTime', '$browser'
			)";
			$rs_insert = mysql_query($query, $portal_connect);
			
		// terms agreed to and required? 
		
		if ($avoidTerms == '2') { 
			$_SESSION['termsRequired'] = "Y";				// tell server/site that terms are required.
			$terms = $row_getLogin['lastDate'];				// current terms date...
			if ($terms == "") $terms = "1900-01-01";
			//if ($clientID == '10894') die("terms $terms");
			$minimumDate =  mktime(0, 0, 0, 1, 1, 1910);	// minimum date required.
			if ($terms != "") {								// only matters if present at all
				list($year, $month, $day) = explode("-", $terms);	// break up last date
				$longDate = mktime(0,0,0, $month, $day, $year);	// new date
				if ($longDate >= $minimumDate) {
					 $terms = "";		// agreement FAIL! wipe it out!
					 $_SESSION['termsRequired'] = "";
				}
			}
			if ($terms == "") $_SESSION['terms'] = "N";
			if ($clientID == "10894") { 
				$_SESSION['terms'] = "N";			// remove me. 
				$_SESSION['termsRequired'] = "Y";	// remove me.
			}
		}
			
		// finally: authorize the session variable...
			//---------------------- 20-09-2014 -----------------//
			require('../Connections/comply_live.php');
			mysql_select_db($database_comply_live, $comply_live);
			
			$cookiecomply = encrypt_decrypt('encrypt', $_SESSION["username"]."|".$_SESSION['brokerID']."|".$_SESSION['contactID']."|".$_SESSION['clientID']);
			mysql_query("insert into thinkhr_autologin(`contactid`, `cookie`, `createofdate`) values('".$_SESSION['contactID']."','".$cookiecomply."','".date("Y-m-d H:i:s")."');");
			
			require('../Connections/portal_connect.php');
			mysql_select_db($database_portal_connect, $portal_connect);
			//---------------------------------------------------//
			
			$_SESSION['authorized'] = 'y';
			$authorized = 'y';
			if ($_GET['show'] == "Y") echo $authorized;

			//---------------------------------------------
			//saving url reference when loging to workplace
			//---------------------------------------------
			$url_destination = $destination;
			include("inc.save-outgoinglog.php");
			//---------------------------------------------
			
			if($redirect_page !=""){
				//echo "destination->".$destination;
				//print_r($_SESSION);
				//exit;
			?>
				<script type="text/javascript" language="javascript" src="/assets/js/jquery/jquery-1.11.0.min.js"></script>
				<script>
				var lnk = document.getElementById("autologin_1");
				lnk.href = "javascript:ProcessLogin();";
				lnk.click();
				
				function ProcessLogin()
				{
					document.frmautologin.submit();
				}
				</script>
				<?php
				exit;
			}else{
				header("Location:$destination"); 
				
			}
		
	} elseif($totalRows_getLogin > 0 && $row_getLogin['brokerType'] == "content_partner") {
		// oops! UNSUCCESSFUL LOGIN!
		session_start();
		$_SESSION['authorized'] = 'n';
		$authorized = 'n';
		$problem = "COMPLY";
	} else {
	
		// oops! UNSUCCESSFUL LOGIN!
			session_start();
			$_SESSION['authorized'] = 'n';
			$authorized = 'n';
	}
}
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <!-- <meta name="viewport" content="width=device-width, initial-scale=1" />-->
        <meta name="viewport" content="width=device-width initial-scale=1.0 maximum-scale=1.0 user-scalable=yes" />
        <title>Login | ThinkHR | Call Now: 925.225.1100 | Pleasanton, California</title>
        
        <meta name="keywords" content="thinkhr, hr, hr hotline, human resources hotline, human resources,  human resource consulting, human resources questions, human resources guide, hr consultant, hr consulting, hr help, hr best practices, best practices in human resources, hr library, hr training, human resources alternatives, hr alternatives" />
        <meta name="description" content="Thinkhr provides on-the-spot expert hr advice and answers to businesses and hr professionals through its national hr hotline and hr answers portals" />
        <meta name="SKYPE_TOOLBAR" content="SKYPE_TOOLBAR_PARSER_COMPATIBLE" />
        <link rel="shortcut icon" href="/assets/imgs/favicon.png">

        <!--CSS -->
        <link href="/assets/css/bootstrap/bootstrap.min.css" rel="stylesheet">
        <link href="/assets/css/inc.styles.css" rel="stylesheet">
        <link href="/assets/css/inc.media-query.css" rel="stylesheet">
        <link href="/assets/fonts/neris/stylesheet.css" rel="stylesheet">
        <link rel="stylesheet" href="/assets/js/fancybox/css/fancybox/jquery.fancybox-buttons.css">
        <link rel="stylesheet" href="/assets/js/fancybox/css/fancybox/jquery.fancybox-thumbs.css">
        <link rel="stylesheet" href="/assets/js/fancybox/css/fancybox/jquery.fancybox.css">
        <link href="/assets/css/font-awesome.min.css" rel="stylesheet" type="text/css">
        <link href="/assets/js/bootstrap-select/bootstrap-select.css" rel="stylesheet">
        <link type="text/css" rel="stylesheet" href="/assets/js/mmenu/css/jquery.mmenu.css" />
        <link href="/assets/js/mmenu/css/extensions/jquery.mmenu.positioning.css" type="text/css" rel="stylesheet" />
        <link href="/assets/js/mmenu/css/addons/jquery.mmenu.header.css" type="text/css" rel="stylesheet" />
        <link href="/assets/js/bxslider/jquery.bxslider.css" type="text/css" rel="stylesheet" />
        
        <!--[if lt IE 7]>
            <script src="/assets/js/IE7.js"></script>
        <![endif]-->

        <!--[if lt IE 8]>
            <link href="/assets/css/bootstrap-ie7.css" rel="stylesheet">
        <![endif]-->

        <!-- HTML5 shim and Respond.js IE8 support of HTML5 elements and media queries -->
        <!--[if lt IE 9]>
            <script src="/assets/js/html5shiv.js"></script>
            <script src="/assets/js/respond.min.js"></script>
            <link href="/assets/css/ie8-fix.css" rel="stylesheet">
        <![endif]-->
    </head>
    <body class="cs-bg-login">

        
        <!-- login box -->
        <div class="container-fluid container-col-xs-12">
            <div class="row-fluid bg-login TAC">
                <div class="col-xs-12">
                    <div class="want-demo-row">
                        <div class="row TAC cs-login-row">
                            <form role="form" class="want-demo-form" id="form" name="form" method="post" action="login.php">
                                <input name="login" type="hidden" id="login" value="Y" />
								<input name="hdnredirectpage" type="hidden" id="hdnredirectpage" value="<?php echo $_GET["redirect"];?>" />
                                <input name="brokerID" type="hidden" id="brokerID" value="<?php echo $sentBroker; ?>" />
                                <div class="panel panel-default">
                                    <div class="panel-heading">
                                        <img src="/assets/imgs/logo-thinkhr.png" alt="ThinkHR Login Panel">
                                    </div>
                                    <div class="panel-body TAR ie-8-fix-2">
                                        <div class="form-group">
                                            
                                                <?php
                                                    if ($attempt == 'y') {
                                                        echo '
                                                            <div class="alert alert-warning alert-dismissible" role="alert">
                                                                <button type="button" class="close" data-dismiss="alert"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>
                                                                <p class="TAC">
                                                        ';
                                                        if ($problem == "COMPLY") {
                                                            echo "<strong>Incorrect Login Location</strong> For ThinkHR Comply users, please <a href='https://thinkhrcomply.com/login/'>visit here</a> to log in.";
                                                        } else {
                                                            echo "User name and/or password not found. Please try again.";
                                                        }
                                                        echo "</p></div>";
                                                    }
                                                ?>
                                                <?php
												if($_GET["e"]>0)
												{
													$msg = "Error.";
													switch($_GET["e"])
													{
														case "1":
															$msg = "Unsuccessful Login. 1";
															break;
														case "2":
															$msg = "Unsuccessful Login. 2";
														break;
														case "3":
															$msg = "Token Not Valid.";
														break;
														case "4":
															$msg = "AuthCode Not Valid.";
														break;
														case "5":
															$msg = " Missing Parameters.";
														break;
														case "6":
															$msg = " There is a conflict with the script or it has not been properly setup.";
														break;	
														
													}
													echo '<div class="alert alert-warning alert-dismissible" role="alert">
                                                                <button type="button" class="close" data-dismiss="alert"><span aria-hidden="true">×</span><span class="sr-only">Close</span></button>
                                                                <p class="TAC">'.$msg.'</p></div>';	
												}
                                                ?>
                                                <div class="col-xs-12">
                                                    <div class="input-group">
                                                        <div class="input-group-addon"><i class="glyphicon glyphicon-user"></i></div>
                                                        <input class="form-control" type="text" placeholder="Username:" name="username" id='username' onFocus="clearMeName('username');">
                                                    </div>
                                                    <div class="clearfix height-1"></div>
                                                </div>
                                                <div class="col-xs-12">
                                                    <div class="input-group">
                                                        <div class="input-group-addon"><i class="glyphicon glyphicon-lock"></i></div>
                                                        <input name="passkey" value="" id="passkey" class="form-control" type="password" placeholder="Password:" onClick="this.value='';" autocomplete="off">
                                                    </div>
                                                    <div class="clearfix height-1"></div>
                                                </div>
                                                <div class="col-xs-12">
                                                    <span class="cs-forgot-password">Forgot your password? <a href="password_email.php">Click here</a></span>
                                                    <button type="submit" class="btn btn-default btn-cs-size btn-cs-blue btn-cs-large btn-center btn-no-float">Login</button>
                                                    <div class="clearfix"></div>
                                                </div>
                                                <div class="clearfix"></div>
                                            </div>
                                        <div class="clearfix"></div>
                                    </div>
                                    <div class="clearfix"></div>
                                </div>

                            </form>
                        </div>
                        <div class="clearfix"></div>
                    </div>
                </div>
                <div class="clearfix"></div>
            </div>
        </div>
        
<!--
<?php
    //print_r($_POST);
?>
-->


        <!--Scripts-->
        <script type="text/javascript" language="javascript" src="/assets/js/jquery/jquery-1.11.0.min.js"></script>
        <script type="text/javascript" language="javascript" src="/assets/js/bootstrap/bootstrap.js"></script>
        <script type="text/javascript" src="/assets/js/placeholder/jquery.placeholder.js"></script>
        
        
        <script type="text/javascript">
            $(document).ready(function() {
                $('input, textarea').placeholder();
            });
            
            var nameCleared = "";
            var passCleared = "";

            document.getElementById("passkey").value = "";

            function clearMeName(thisCell) {
                current = document.getElementById(thisCell).value;
                if (current == 'User name') document.getElementById(thisCell).value = '';	
                nameCleared = "Y";
            }
            function clearMePass(thisCell) {
                if (!passCleared) document.getElementById(thisCell).value = '';	
                //document.getElementById(thisCell).style.backgroundImage = "none";	
                passCleared = "Y";
            }
            
            function setfocus() {
                //document.form.username.focus()
            }
            window.onload = function(){ setfocus(); }
            
        </script>

        <!-- Google Code for Remarketing Tag -->
        <script type="text/javascript">
            /* <![CDATA[ */
            var google_conversion_id = 1016405643;
            var google_custom_params = window.google_tag_params;
            var google_remarketing_only = true;
            /* ]]> */
        </script>
        <script type="text/javascript" src="//www.googleadservices.com/pagead/conversion.js"></script>
        <noscript><div style="display:inline;"><img height="1" width="1" style="border-style:none;" alt="" src="//googleads.g.doubleclick.net/pagead/viewthroughconversion/1016405643/?value=0&amp;guid=ON&amp;script=0"/></div></noscript>
        

        <!---LuckyOrange Code Start--->
        <script type='text/javascript'>
        window.__wtw_lucky_site_id = 17311;

            (function() {
                var wa = document.createElement('script'); wa.type = 'text/javascript'; wa.async = true;
                wa.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://ca17311') + '.luckyorange.com/w.js';
                var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(wa, s);
              })();
            </script>      
        <!---LuckyOrange Code End--->
    </body>
</html>