<?php
/* Copyright (C) 2012      Mikael Carlavan        <contact@mika-carl.fr>
 *                                                http://www.mikael-carlavan.fr
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */


/**
 *      \file       htdocs/ndfp/admin/config.php
 *		\ingroup    ndfp
 *		\brief      Page to setup ndfp module
 */

$res=@include("../../main.inc.php");				// For root directory
if (! $res) $res=@include("../../../main.inc.php");	// For "custom" directory

require_once(DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php");
dol_include_once("/ndfp/class/ndfp.class.php");
dol_include_once("/ndfp/lib/ndfp.lib.php");

$langs->load("admin");
$langs->load("companies");
$langs->load("ndfp@ndfp");
$langs->load("other");
$langs->load("errors");

if (!$user->admin)
{
   accessforbidden();
}

//Init error
$error = false;
$message = false;

$action = GETPOST('action');
$value = GETPOST('value');



/*
 * Actions
 */

if ($action == 'updateMask')
{
    $maskconst = GETPOST('maskconst');
    $mask = GETPOST('mask');

    if ($maskconst)
    {
        $result = dolibarr_set_const($db, $maskconst, $mask, 'chaine', 0, '', $conf->entity);

        if ($result > 0)
        {
            $message = $langs->trans('MaskUpdated');
        }
        else
        {
            $error = true;
            $message = $langs->trans('ErrorUpdatingMask');
        }
    }
}

if ($action == 'setautoclassify')
{
	dolibarr_set_const($db, 'NDFP_AUTO_CLASSIFY', 1, 'chaine', 0, '', $conf->entity);
}

if ($action == 'unsetautoclassify')
{
	dolibarr_set_const($db, 'NDFP_AUTO_CLASSIFY', '', 'chaine', 0, '', $conf->entity);
}

if ($action == 'setmulticurrencies')
{
	dolibarr_set_const($db, 'NDFP_MULTI_CURRENCIES', 1, 'chaine', 0, '', $conf->entity);
}

if ($action == 'unsetmulticurrencies')
{
	dolibarr_set_const($db, 'NDFP_MULTI_CURRENCIES', '', 'chaine', 0, '', $conf->entity);
}

if ($action == 'setdatesonbill')
{
	dolibarr_set_const($db, 'NDFP_DATES_ON_BILL', 1, 'chaine', 0, '', $conf->entity);
}

if ($action == 'unsetdatesonbill')
{
	dolibarr_set_const($db, 'NDFP_DATES_ON_BILL', '', 'chaine', 0, '', $conf->entity);
}

if ($action == 'specimen')
{
    $modele = GETPOST('module');

    $ndfp = new Ndfp($db);
    $ndfp->init_as_specimen();

    // Load template
    $dir = '../core/modules/ndfp/doc/';
    $file = "pdf_".$modele.".modules.php";

    if (file_exists($dir.$file))
    {
        $classname = "pdf_".$modele;
        require_once($dir.$file);

        $obj = new $classname($db);

        if ($obj->write_file($ndfp,$langs) > 0)
        {
            header("Location: ".DOL_URL_ROOT."/document.php?modulepart=ndfp&file=SPECIMEN.pdf");
            return;
        }
        else
        {
            $error = true;
            $message = $obj->error;

            dol_syslog($obj->error, LOG_ERR);
        }
    }
    else
    {
        $error = true;
        $message = $langs->trans("ErrorModuleNotFound");

        dol_syslog($langs->trans("ErrorModuleNotFound"), LOG_ERR);
    }
}

if ($action == 'set')
{
    $type = 'ndfp';

    $sql = "INSERT INTO ".MAIN_DB_PREFIX."document_model (nom, type, entity, libelle, description)";
    $sql.= " VALUES ('".$db->escape($value)."','".$type."',".$conf->entity.", NULL, NULL)";

    $result = $db->query($sql);

    if ($result > 0)
    {
        $message = $langs->trans('ModelSet');
    }
    else
    {
        $error = true;
        $message = $langs->trans('ErrorSettingModel');
    }
}

if ($action == 'del')
{
    $type = 'ndfp';

    $sql = "DELETE FROM ".MAIN_DB_PREFIX."document_model";
    $sql.= " WHERE nom = '".$db->escape($value)."'";
    $sql.= " AND type = '".$type."'";
    $sql.= " AND entity = ".$conf->entity;

    $result = $db->query($sql);

    if ($result > 0)
    {
        $message = $langs->trans('ModelDeleted');
    }
    else
    {
        $error = true;
        $message = $langs->trans('ErrorDeletingModel');
    }
}


if ($action == 'setdoc')
{
    $db->begin();

    if (dolibarr_set_const($db, "NDFP_ADDON_PDF",$value,'chaine',0,'',$conf->entity))
    {
        $conf->global->NDFP_ADDON_PDF = $value;
    }

    // On active le modele
    $type = 'ndfp';

    $sql_del = "DELETE FROM ".MAIN_DB_PREFIX."document_model";
    $sql_del.= " WHERE nom = '".$db->escape($value)."'";
    $sql_del.= " AND type = '".$type."'";
    $sql_del.= " AND entity = ".$conf->entity;
    dol_syslog("config.php ".$sql_del);

    $result1 = $db->query($sql_del);

    $sql = "INSERT INTO ".MAIN_DB_PREFIX."document_model (nom, type, entity, libelle, description)";
    $sql.= " VALUES ('".$value."', '".$type."', ".$conf->entity.", NULL, NULL)";

    dol_syslog("config.php ".$sql);

    $result2 = $db->query($sql);

    if ($result1 && $result2)
    {
        $db->commit();
        $message = $langs->trans('DocumentActivated');
    }
    else
    {
        dol_syslog("config.php ".$db->lasterror(), LOG_ERR);
        $db->rollback();

        $message = $langs->trans('ErrorActivatingDocument');
        $error = true;
    }
}

if ($action == 'setmod')
{
    $result = dolibarr_set_const($db, "NDFP_ADDON", $value, 'chaine', 0, '', $conf->entity);

    if ($result > 0)
    {
        $message = $langs->trans('ModelActivated');
    }
    else
    {
        $error = true;
        $message = $langs->trans('ErrorActivatingModel');
    }
}

if ($action == 'update')
{
	$validation_gid = GETPOST('validation_gid', 'int');
    $result = dolibarr_set_const($db, "NDFP_VALIDATION_GID", $validation_gid, 'chaine', 0, '', $conf->entity);

	$price_base_invoices = GETPOST('price_base_invoices');
    $result = dolibarr_set_const($db, "NDFP_PRICE_BASE_INVOICES", $price_base_invoices, 'chaine', 0, '', $conf->entity);


    if ($result > 0)
    {
        $message = $langs->trans('SetupUpdated');
    }
    else
    {
        $error = true;
        $message = $langs->trans('ErrorUpdatingSetup');
    }
}

$html = new Form($db);


$modules = array();
$modules2 = array();

clearstatcache();
$i = 0;

$dir = '../core/modules/ndfp/';
if (is_dir($dir))
{
	$handle = opendir($dir);
	if (is_resource($handle))
	{
		while (($file = readdir($handle))!==false)
		{
			if (! is_dir($dir.$file) || (substr($file, 0, 1) <> '.' && substr($file, 0, 3) <> 'CVS'))
			{
				$filebis = $file;
				$classname = preg_replace('/\.php$/','',$file);
				// For compatibility
				if (! is_file($dir.$filebis))
				{
					$filebis = $file."/".$file.".modules.php";
					$classname = "mod_ndfp_".$file;
				}
				//print "x".$dir."-".$filebis."-".$classname;
				if (! class_exists($classname) && is_readable($dir.$filebis) && (preg_match('/mod_/',$filebis) || preg_match('/mod_/',$classname)) && substr($filebis, dol_strlen($filebis)-3, 3) == 'php')
				{
					// Chargement de la classe de numerotation
					require_once($dir.$filebis);

					$module = new $classname($db);

					
					
					// Show modules according to features level
					if ($module->version == 'development'  && $conf->global->MAIN_FEATURES_LEVEL < 2) continue;
					if ($module->version == 'experimental' && $conf->global->MAIN_FEATURES_LEVEL < 1) continue;

					$modules[$i] = new StdClass();
					if ($module->isEnabled())
					{
						$modules[$i]->name = preg_replace('/mod_ndfp_/','',preg_replace('/\.php$/','',$file));
						$modules[$i]->info = $module->info();

						$tmp = $module->getExample();

						if (preg_match('/^Error/',$tmp)){
							$modules[$i]->example = $langs->trans($tmp);
						}else{
							$modules[$i]->example = $tmp;
						}

						if ($conf->global->NDFP_ADDON == $file || $conf->global->NDFP_ADDON.'.php' == $file){
							$modules[$i]->state = img_picto($langs->trans("Activated"),'on');
						}else{
							$modules[$i]->state = '<a href="'.$_SERVER["PHP_SELF"].'?action=setmod&amp;value='.preg_replace('/\.php$/','',$file).'" alt="'.$langs->trans("Default").'">'.img_picto($langs->trans("Disabled"),'off').'</a>';
						}


						$ndfp = new Ndfp($db);
						$ndfp->init_as_specimen();

						$htmltooltip = '';
						$htmltooltip .= ''.$langs->trans("Version").': <b>'.$module->getVersion().'</b><br>';

						$nextval = $module->getNextValue($mysoc, $ndfp);
						if ("$nextval" != $langs->trans("NotAvailable"))	// Keep " on nextval
						{
							$htmltooltip .= $langs->trans("NextValueForNdfp").': ';
							if ($nextval)
							{
								$htmltooltip.= $nextval.'<br />';
							}
							else
							{
								$htmltooltip.=$langs->trans($module->error).'<br />';
							}
						}

						$modules[$i]->tooltip = $html->textwithpicto('', $htmltooltip, 1, 0);
						$modules[$i]->error = '';

						if ($conf->global->NDFP_ADDON.'.php' == $file)  // If module is the one used, we show existing errors
						{
							$modules[$i]->error = $module->error;
						}

					}

					$i++;
				}

			}

		}

		closedir($handle);
	}
}


// Load array def with activated templates
$def = array();

$sql = "SELECT nom";
$sql.= " FROM ".MAIN_DB_PREFIX."document_model";
$sql.= " WHERE type = 'ndfp'";

$resql = $db->query($sql);

if ($resql)
{
    $i = 0;
    $num_rows = $db->num_rows($resql);
    while ($i < $num_rows)
    {
        $array = $db->fetch_array($resql);
        array_push($def, $array[0]);
        $i++;
    }
}
else
{
    $error = true;
    $message = $db->error;
}


clearstatcache();
$i = 0;

$dir = '../core/modules/ndfp/doc';
if (is_dir($dir))
{
	$handle = opendir($dir);
	if (is_resource($handle))
	{
		while (($file = readdir($handle))!==false)
		{
			$filelist[]=$file;
		}
		closedir($handle);


		foreach($filelist as $file)
		{
			if (preg_match('/\.modules\.php$/i',$file) && preg_match('/^(pdf_|doc_)/',$file))
			{
				if (file_exists($dir.'/'.$file))
				{
					$name = substr($file, 4, dol_strlen($file) -16);
					$classname = substr($file, 0, dol_strlen($file) -12);

					require_once($dir.'/'.$file);
					$module = new $classname($db);

					$modulequalified=1;
					if ($module->version == 'development'  && $conf->global->MAIN_FEATURES_LEVEL < 2) $modulequalified = 0;
					if ($module->version == 'experimental' && $conf->global->MAIN_FEATURES_LEVEL < 1) $modulequalified = 0;

					$modules2[$i] = new StdClass();
					
					if ($modulequalified)
					{

						$modules2[$i]->name = (empty($module->name)?$name:$module->name);

						if (method_exists($module,'info')){
							$modules2[$i]->desc = $module->info($langs);
						}else{
							$modules2[$i]->desc = $module->description;
						}

						$url = '';

						if (in_array($name, $def))
						{
							if ($conf->global->NDFP_ADDON_PDF != "$name")
							{
								$url .= '<a href="'.$_SERVER["PHP_SELF"].'?action=del&amp;value='.$name.'">';
								$url .= img_picto($langs->trans("Enabled"),'on');
								$url .= '</a>';
							}
							else
							{
								$url = img_picto($langs->trans("Enabled"),'on');
							}

						}
						else
						{

							$url .= '<a href="'.$_SERVER["PHP_SELF"].'?action=set&amp;value='.$name.'">'.img_picto($langs->trans("Disabled"),'off').'</a>';

						}

						$modules2[$i]->active = $url;

						$url = '';
						if ($conf->global->NDFP_ADDON_PDF == "$name")
						{
							$url .=  img_picto($langs->trans("Default"),'on');
						}
						else
						{
							$url .=  '<a href="'.$_SERVER["PHP_SELF"].'?action=setdoc&amp;value='.$name.'" alt="'.$langs->trans("Default").'">'.img_picto($langs->trans("Disabled"),'off').'</a>';
						}

						$modules2[$i]->default = $url;

						// Info
						$htmltooltip =    ''.$langs->trans("Name").': '.$module->name;
						$htmltooltip.='<br />'.$langs->trans("Type").': '.($module->type?$module->type:$langs->trans("Unknown"));
						if ($module->type == 'pdf')
						{
							$htmltooltip.='<br />'.$langs->trans("Height").'/'.$langs->trans("Width").': '.$module->page_hauteur.'/'.$module->page_largeur;
						}
						$htmltooltip.='<br /><br /><u>'.$langs->trans("FeaturesSupported").':</u>';
						$htmltooltip.='<br />'.$langs->trans("Logo").': '.yn($module->option_logo,1,1);
						$htmltooltip.='<br />'.$langs->trans("MultiLanguage").': '.yn($module->option_multilang,1,1);

						$modules2[$i]->info = $html->textwithpicto('', $htmltooltip, 1, 0);

						$url = '';
						if ($module->type == 'pdf')
						{
							$url .= '<a href="'.$_SERVER["PHP_SELF"].'?action=specimen&module='.$name.'">'.img_object($langs->trans("Preview"),'generic').'</a>';
						}
						else
						{
							$url .= img_object($langs->trans("PreviewNotAvailable"),'generic');
						}

						$modules2[$i]->preview = $url;

						$i++;
					}
				}
			}
		}
	}
}

/*
 * Triggers
 */
$triggers = array();
$triggers[] = array('code' => 'NDFP_VALIDATE', 'label' => $langs->trans('NDFP_AGENDA_ACTIONAUTO_NDFP_VALIDATE'));
$triggers[] = array('code' => 'NDFP_SENTBYMAIL', 'label' => $langs->trans('NDFP_AGENDA_ACTIONAUTO_NDFP_SENTBYMAIL'));
$triggers[] = array('code' => 'NDFP_PAID', 'label' => $langs->trans('NDFP_AGENDA_ACTIONAUTO_NDFP_PAID'));
$triggers[] = array('code' => 'NDFP_CANCEL', 'label' => $langs->trans('NDFP_AGENDA_ACTIONAUTO_NDFP_CANCEL'));

if($action == 'saveautoevents') {
	foreach ($triggers as $trigger)
	{
		$param='NDFP_AGENDA_ACTIONAUTO_'.$trigger['code'];
		$res = (GETPOST($param,'alpha')) ? dolibarr_set_const($db,$param,GETPOST($param,'alpha'),'chaine',0,'',$conf->entity) : dolibarr_del_const($db,$param,$conf->entity);
		if (! $res > 0) $error++;
	}
	
	if ($res > 0) {
		$message = $langs->trans('AutoActionsSaved');
	} else {
		$error = true;
		$message = $langs->trans('ErrorSavingAutoActions');
	}
}

$actionAutoClassifyNotes = ($conf->global->NDFP_AUTO_CLASSIFY ?  'unsetautoclassify' : 'setautoclassify');
$imgAutoClassifyNotes = ($conf->global->NDFP_AUTO_CLASSIFY ?  img_picto($langs->trans("Activated"),'switch_on') : img_picto($langs->trans("Disabled"),'switch_off'));
$linkAutoClassifyNotes	= '<a href="'.$_SERVER['PHP_SELF'].'?action='.$actionAutoClassifyNotes.'" >'.$imgAutoClassifyNotes.'</a>';

$actionActivateMultiCurrency = ($conf->global->NDFP_MULTI_CURRENCIES ?  'unsetmulticurrencies' : 'setmulticurrencies');
$imgActivateMultiCurrency = ($conf->global->NDFP_MULTI_CURRENCIES ?  img_picto($langs->trans("Activated"),'switch_on') : img_picto($langs->trans("Disabled"),'switch_off'));
$linkActivateMultiCurrency	= '<a href="'.$_SERVER['PHP_SELF'].'?action='.$actionActivateMultiCurrency.'" >'.$imgActivateMultiCurrency.'</a>';

$actionDisplayDatesOnBill = ($conf->global->NDFP_DATES_ON_BILL ?  'unsetdatesonbill' : 'setdatesonbill');
$imgDisplayDatesOnBill = ($conf->global->NDFP_DATES_ON_BILL ?  img_picto($langs->trans("Activated"),'switch_on') : img_picto($langs->trans("Disabled"),'switch_off'));
$linkDisplayDatesOnBill	= '<a href="'.$_SERVER['PHP_SELF'].'?action='.$actionDisplayDatesOnBill.'" >'.$imgDisplayDatesOnBill.'</a>';


/*
 * View
 */

$head = ndfpadmin_prepare_head();
$current_head = 'config';

$linkback = '<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';

require_once("../tpl/admin.config.tpl.php");

$db->close();

?>
