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
 *	\file       htdocs/ndfp/payment.php
 *	\ingroup    ndfp
 *	\brief      Page to create/see a credit note payment
 */

$res=@include("../main.inc.php");					// For root directory
if (! $res) $res=@include("../../main.inc.php");	// For "custom" directory

dol_include_once("/ndfp/class/ndfp.class.php");
dol_include_once("/ndfp/class/ndfp.payment.class.php");
dol_include_once("/ndfp/lib/ndfp.lib.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/html.form.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/html.formother.class.php");
require_once(DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php');
require_once(DOL_DOCUMENT_ROOT.'/fourn/class/paiementfourn.class.php');
require_once(DOL_DOCUMENT_ROOT."/core/lib/functions2.lib.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/functions.lib.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/date.lib.php");


$langs->load('main');
$langs->load('ndfp@ndfp');
$langs->load('banks');

$fk_user = GETPOST('fk_user');
$action = GETPOST('action');
//$source = GETPOST('source') ? GETPOST('source') : 'ndfp';
$id = GETPOST("id");

// Security check
$socid = 0;
if ($user->societe_id > 0)
{
    $socid = $user->societe_id;
}

if (!$user->rights->ndfp->myactions->read && !$user->rights->ndfp->allactions->read)
{
    accessforbidden();
}

//Retrieve error
$error = false;
$message = false;

$payment = new NdfpPayment($db);


$html = new Form($db);
$htmlother = new FormOther($db);

$payments = array();


// Get all payments

$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');
$filter = GETPOST("filter",'alpha');

$page = GETPOST("page",'int');
//

$search_ref = GETPOST("search_ref",'alpha');
$search_year = GETPOST("search_year",'int');
$search_month = GETPOST("search_month",'int');
$search_payment_label = GETPOST("search_payment_label",'alpha');
$search_user = GETPOST("search_user",'alpha');
$search_account = GETPOST("search_account",'int');
$search_amount = GETPOST("search_amount");

if ($page == -1 || empty($page))
{
    $page = 0;
}

$offset = $conf->liste_limit * $page;

if (! $sortorder) $sortorder = 'DESC';
if (! $sortfield) $sortfield = 'p.rowid';

$limit = $conf->liste_limit;

$pageprev = $page - 1;
$pagenext = $page + 1;




if ($id > 0)
{
    $result = $payment->fetch($id);

    if ($result < 0)
    {
	    header("Location: ".$_SERVER['PHP_SELF']);
    }
}
else
{
    if (empty($action) && ($user->rights->ndfp->myactions->read  || $user->rights->ndfp->allactions->read))
    {
        // Get all payments

        $sortfield = GETPOST("sortfield",'alpha');
        $sortorder = GETPOST("sortorder",'alpha');
        $filter = GETPOST("filter",'alpha');

        $page = GETPOST("page",'int');
        //

        $search_ref = GETPOST("search_ref",'alpha');
        $search_year = GETPOST("search_year",'int');
        $search_month = GETPOST("search_month",'int');
        $search_payment_label = GETPOST("search_payment_label",'alpha');
        $search_user = GETPOST("search_user",'alpha');
        $search_account = GETPOST("search_account",'int');
        $search_amount = GETPOST("search_amount");

        if ($page == -1 || empty($page))
        {
            $page = 0;
        }

        $page = intval($page);

        $offset = (int)$conf->liste_limit * $page;

        if (! $sortorder) $sortorder = 'DESC';
        if (! $sortfield) $sortfield = 'p.rowid';

        $limit = (int)$conf->liste_limit;

        $pageprev = $page - 1;
        $pagenext = $page + 1;



        $sql = "SELECT p.datep as dp, p.tms, p.num_paiement as payment_number, p.rowid, p.fk_bank, ";
        $sql.= " c.id as paiement_type, c.code as payment_code, c.libelle as payment_label, ";
        $sql.= " pf.amount, u.rowid as uid, u.lastname, u.firstname, ";
        $sql.= " ba.rowid as baid, ba.ref, ba.label";
        $sql.= " FROM ".MAIN_DB_PREFIX."paiementfourn as p";
        $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."bank as b ON p.fk_bank = b.rowid";
        $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."bank_account as ba ON b.fk_account = ba.rowid";
        $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_paiement as c ON p.fk_paiement = c.id";
        $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."paiementfourn_facturefourn as pf ON pf.fk_paiementfourn = p.rowid";
        $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."user as u ON p.fk_user_author = u.rowid";
        $sql.= " WHERE pf.fk_facturefourn IN (SELECT fk_target FROM ".MAIN_DB_PREFIX."element_element";
        $sql.= " WHERE sourcetype = 'ndfp' AND targettype = 'invoice_supplier')";   
        $sql.= ' ORDER BY p.datep, p.tms';


        if ($user->rights->ndfp->myactions->read && !$user->rights->ndfp->allactions->read)
        {
            $sql .= " AND p.fk_user_author = ".$user->id;
        }

        if ($search_ref)
        {
            $sql.= ' AND p.rowid LIKE \'%'.$db->escape(trim($search_ref)).'%\'';
        }
        if ($search_payment_label)
        {
            $sql.= ' AND c.code LIKE \'%'.$db->escape(trim($search_payment_label)).'%\'';
        }
        if ($search_user)
        {
            $sql.= ' AND u.lastname LIKE \'%'.$db->escape(trim($search_user)).'%\' OR u.firstname LIKE \'%'.$db->escape(trim($search_user)).'%\'';
        }

        if ($search_account)
        {
            $sql.= ' AND ba.rowid = '.$db->escape($search_account);
        }
        if ($search_amount)
        {
            $sql.= ' AND amount = '.$db->escape(price2num(trim($search_amount)));
        }

        if ($search_month > 0)
        {
            if ($search_year > 0)
            $sql.= " AND p.datep BETWEEN '".$db->idate(dol_get_first_day($search_year, $search_month, false))."' AND '".$db->idate(dol_get_last_day($search_year, $search_month, false))."'";
            else
            $sql.= " AND date_format(p.datep, '%m') = '".$search_month."'";
        }
        else if ($search_year > 0)
        {
            $sql.= " AND p.datep BETWEEN '".$db->idate(dol_get_first_day($search_year, 1, false))."' AND '".$db->idate(dol_get_last_day($search_year, 12, false))."'";
        }
        $sql.= $db->plimit($limit+1, $offset);

        dol_syslog("Payment sql=".$sql, LOG_DEBUG);
        $result = $db->query($sql);

        $j = 0;
        if ($result)
        {
            $num = $db->num_rows($result);
            $i = 0;

            if ($num)
            {

                while ($i < $num)
                {
                    $obj = $db->fetch_object($result);
                    
                    $tms = $db->jdate($obj->tms);
                    $userstatic = new User($db);
                    $accountstatic = new Account($db);                                                     

                    $userstatic->lastname  = $obj->lastname;
                    $userstatic->firstname = $obj->firstname;
                    $userstatic->id = $obj->uid;

                    $accountstatic->id = $obj->baid;
                    $accountstatic->label = $obj->label;
                    $accountstatic->ref = $obj->ref;

                    // To avoid payments with exact same date to be overwrite, just shift timestamp
                    while (isset($payments[$tms]))
                    {
                        $tms += 1;
                    }

                    $payments[$tms] = $obj;
                    $payments[$tms]->pdate = $db->jdate($obj->dp);     
                    $payments[$tms]->url = DOL_URL_ROOT.'/fourn/paiement/card.php?id='.$obj->rowid;
                    $payments[$tms]->username = $userstatic->getNomUrl(1);
                    $payments[$tms]->account = $accountstatic->getNomUrl(1);

                    $i++;
                    $j++;
                }
            }
        }
        else
        {
            $error = true;
            $message = $db->error()." sql=".$sql;
        }

        $sql = "SELECT p.rowid, p.fk_payment, p.tms, p.amount, p.fk_user, p.datep as dp, p.payment_number, p.fk_bank,";
        $sql.= " c.code as payment_code, c.libelle as payment_label, ba.rowid as baid, ba.ref, ba.label, u.rowid as uid,";
        $sql.= " u.lastname, u.firstname";
        $sql.= " FROM ".MAIN_DB_PREFIX."ndfp_pay as p";
        $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."bank as b ON p.fk_bank = b.rowid";
        $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."bank_account as ba ON b.fk_account = ba.rowid";
        $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_paiement as c ON p.fk_payment = c.id";
        $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."user as u ON p.fk_user = u.rowid WHERE 1";

        if ($user->rights->ndfp->myactions->read && !$user->rights->ndfp->allactions->read)
        {
            $sql .= " AND p.fk_user = ".$user->id;
        }
        if ($search_ref)
        {
            $sql.= ' AND p.rowid LIKE \'%'.$db->escape(trim($search_ref)).'%\'';
        }
        if ($search_payment_label)
        {
            $sql.= ' AND c.code LIKE \'%'.$db->escape(trim($search_payment_label)).'%\'';
        }
        if ($search_user)
        {
            $sql.= ' AND u.lastname LIKE \'%'.$db->escape(trim($search_user)).'%\' OR u.firstname LIKE \'%'.$db->escape(trim($search_user)).'%\'';
        }

        if ($search_account)
        {
            $sql.= ' AND ba.rowid = '.$db->escape($search_account);
        }
        if ($search_amount)
        {
            $sql.= ' AND amount = '.$db->escape(price2num(trim($search_amount)));
        }

        if ($search_month > 0)
        {
            if ($search_year > 0)
            $sql.= " AND p.datep BETWEEN '".$db->idate(dol_get_first_day($search_year, $search_month, false))."' AND '".$db->idate(dol_get_last_day($search_year, $search_month, false))."'";
            else
            $sql.= " AND date_format(p.datep, '%m') = '".$search_month."'";
        }
        else if ($search_year > 0)
        {
            $sql.= " AND p.datep BETWEEN '".$db->idate(dol_get_first_day($search_year, 1, false))."' AND '".$db->idate(dol_get_last_day($search_year, 12, false))."'";
        }
        $sql.= $db->plimit($limit+1-count($payments), $offset);

        dol_syslog("Payment sql=".$sql, LOG_DEBUG);
        $result = $db->query($sql);

        if ($result)
        {
            $num = $db->num_rows($result);
            $i = 0;

            if ($num)
            {

                while ($i < $num)
                {
                	$obj = $db->fetch_object($result);
                	
                    $tms = $db->jdate($obj->tms);

                    $userstatic = new User($db);
                    $accountstatic = new Account($db);


                    $userstatic->lastname  = $obj->lastname;
                    $userstatic->firstname = $obj->firstname;
                    $userstatic->id = $obj->uid;

                    $accountstatic->id = $obj->baid;
                    $accountstatic->label = $obj->label;
                    $accountstatic->ref = $obj->ref;

                    // To avoid payments with exact same date to be overwrite, just shift timestamp
                    while (isset($payments[$tms]))
                    {
                        $tms += 1;
                    }

                    $payments[$tms] = $obj;
                    $payments[$tms]->pdate = $db->jdate($obj->dp);     
                    $payments[$tms]->url =  dol_buildpath('/ndfp/payment.php', 1).'?id='.$obj->rowid;
                    $payments[$tms]->username = $userstatic->getNomUrl(1);
                    $payments[$tms]->account = $accountstatic->getNomUrl(1);

                    $j++;
                    $i++;
                }
            }
        }
    }
}

ksort($payments);

if ($action != 'create' && $action != 'add' && $action != 'delete')
{
    $result = $payment->call($action, array($user));

    if ($result > 0)
    {
        if ($action == 'confirm_delete')
        {
            header("Location: ".$_SERVER['PHP_SELF']);
        }
        else
        {
            $message = $payment->error;
        }

    }
    else
    {
        if ($action == 'confirm_add')
        {
            $action = 'create';
        }

        $message = $payment->error;
        $error = true;
    }
}


if ($action == 'delete')
{
    $formconfirm = $html->formconfirm($_SERVER['PHP_SELF'].'?id='.$payment->id, $langs->trans("DeletePayment"), $langs->trans("ConfirmDeletePayment"), 'confirm_delete','','no',0);
}

$amounts = array();
$total_payment = 0;

foreach ($_POST as $key => $value)
{
    if (substr($key,0,7) == 'amount_')
    {
        $fk_ndfp = substr($key, 7);
        $amounts[$fk_ndfp] = $_POST[$key];

        $total_payment += price2num($amounts[$fk_ndfp]);
    }
}

if ($action == 'add')
{


    $formquestion = array();


    $formquestion[] = array('type' => 'hidden','name' => 'fk_user', 'value' => $fk_user);
    $formquestion[] = array('type' => 'hidden','name' => 'closepaidndfp', 'value' => GETPOST('closepaidndfp'));

    $formquestion[] = array('type' => 'hidden','name' => 'reday', 'value' => $_POST['reday']);
    $formquestion[] = array('type' => 'hidden','name' => 'remonth', 'value' => $_POST['remonth']);
    $formquestion[] = array('type' => 'hidden','name' => 'reyear', 'value' => $_POST['reyear']);

    $formquestion[] = array('type' => 'hidden','name' => 'note', 'value' => trim(GETPOST('note', 'chaine')));
    $formquestion[] = array('type' => 'hidden','name' => 'fk_account', 'value' => GETPOST('fk_account', 'int'));
    $formquestion[] = array('type' => 'hidden','name' => 'fk_payment', 'value' => GETPOST('fk_payment', 'int'));
    $formquestion[] = array('type' => 'hidden','name' => 'payment_number', 'value' => GETPOST('payment_number'));

    foreach ($_POST as $key => $value)
    {
        if (substr($key,0,7) == 'amount_')
        {
            $amount = price2num($_POST[$key]);

            if ($amount > 0)
            {
                $formquestion[] = array('type' => 'hidden','name' => $key, 'value' => $amount);
            }
        }
    }

    $text = $langs->trans('ConfirmPayment', $total_payment, $langs->trans("Currency".$conf->currency));

    if (GETPOST('closepaidndfp'))
    {
        $text .= '<br />'.$langs->trans("AllCompletelyPayedNdfpWillBeClosed");
    }

    $formconfirm = $html->formconfirm($_SERVER['PHP_SELF'], $langs->trans('UserPayments'), $text, 'confirm_add', $formquestion, 'yes', 0);
}


if ($payment->id > 0 || $action == 'create' || $action == 'add')
{
    if ($action == 'create' || $action == 'add')
    {
        $fk_account = GETPOST('fk_account');
        $payment_number = GETPOST('payment_number');
        $fk_payment = GETPOST('fk_payment');

        $datep = dol_mktime(12, 0 , 0, $_POST['remonth'], $_POST['reday'], $_POST['reyear']);
        $note = trim(GETPOST('note', 'chaine'));

        $userstatic = new User($db);
        $societestatic = new Societe($db);
        $ndfpstatic = new Ndfp($db);

        $result = $userstatic->fetch($fk_user);

        if ($result < 0)
        {
            header("Location: ".$_SERVER['PHP_SELF']);
        }

        $total_paid = 0;
        $total_ttc = 0;

        $sql  = ' SELECT n.rowid, n.ref, n.total_ttc, n.datef, n.fk_soc, n.description, SUM(np.amount) AS total_paid';
        $sql .= ' FROM '.MAIN_DB_PREFIX.'ndfp AS n';
        $sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'ndfp_pay_det AS np ON np.fk_ndfp = n.rowid';
        $sql .= ' WHERE n.statut = 1 AND n.fk_user = '.$fk_user;
        $sql .= ' GROUP BY n.rowid';

        dol_syslog("Payment sql=".$sql, LOG_DEBUG);

        $result = $db->query($sql);

        if ($result)
        {
            $num = $db->num_rows($result);
            $i = 0;

            if ($num)
            {
                while($i < $num)
                {
                    $obj = $db->fetch_object($result);

                    $total_paid += $obj->total_paid;
                    $total_ttc += $obj->total_ttc;

                    $societestatic->fetch($obj->fk_soc);

                    $obj->client = ($obj->fk_soc > 0 ? $societestatic->getNomUrl(1) : '');

                    $payments[$i] = $obj;
                    $i++;
                }
            }

        }
        else
        {
            $error = true;
            $message = $db->error()." sql=".$sql;
        }


        include 'tpl/payment.create.tpl.php';
    }
    else if ($action == 'followup')
    {
       // Prepare head
    	$head = ndfppayment_prepare_head($payment->id);    
        $current_head = 'followup';

        $userstatic = new User($db);
        $userstatic->fetch($payment->fk_user_author);

        include 'tpl/payment.followup.tpl.php';

    }
    else
    {

       // Prepare head
    	$head = ndfppayment_prepare_head($payment->id);
        $current_head = 'payment';

        // Payment type (VIR, LIQ, ...)
        if ($langs->trans("PaymentType".$payment->payment_code) != ("PaymentType".$payment->payment_code))
        {
            $payment_label = $langs->trans("PaymentType".$payment->payment_code);
        }
        else
        {
            $payment_label = $langs->trans("PaymentType".$payment->payment_label);
        }


        if ($payment->fk_bank)
        {
        	$bank_line = new AccountLine($db);
        	$bank_line->fetch($payment->fk_bank);

            $writing = $bank_line->getNomUrl(1, 0, 'showall');
        }
        else
        {
            $writing = '';
        }

        $ndfps = $payment->get_ndfps();

        $can_delete = false;

        if ($user->rights->ndfp->myactions->bill && $user->id == $payment->fk_user_author)
        {
            $can_delete = true;
        }

        if ($user->rights->ndfp->allactions->bill)
        {
            $can_delete = true;
        }

        $nb = count($ndfps); // use count instead count and not in the loop
        for($i=0; $i<$nb; $i++)
        {
            if ($ndfps[$i]->remain_to_pay == 0)//If one note of this payment is payed, we do not delete
            {
                $can_delete = false;
            }
        }

        include 'tpl/payment.default.tpl.php';
    }
}
else
{
	
    include 'tpl/payment.list.tpl.php';

}


llxFooter();

$db->close();
?>