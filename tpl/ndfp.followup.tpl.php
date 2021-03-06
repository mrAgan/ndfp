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

/**	    \file       htdocs/ndfp/tpl/ndfp.followup.tpl.php
 *		\ingroup    ndfp
 *		\brief      Ndfp module followup view
 */

llxHeader('');


dol_fiche_head($head, $current_head, $langs->trans('Ndfp'));

?>

<table width="100%">
    <tr>
        <td>
            <?php echo $langs->trans('CreatedBy'); ?> : <?php echo $userstatic->getNomUrl(1); ?><br />
            <?php echo $langs->trans('CreationDate'); ?> : <?php echo dol_print_date($ndfp->datec, "dayhourtext"); ?><br />
            <?php echo $langs->trans('LastModificationDate'); ?> : <?php echo dol_print_date($ndfp->tms, "dayhourtext"); ?><br />
            <?php
	            if($ndfp->statut > 0) {
	            	echo $langs->trans('DateValidation'); ?> : <?php echo dol_print_date($ndfp->date_valid, "dayhourtext");
				}
            ?>
        </td>
    </tr>
</table>


<?php dol_fiche_end(); ?>



<?php llxFooter(''); ?>



