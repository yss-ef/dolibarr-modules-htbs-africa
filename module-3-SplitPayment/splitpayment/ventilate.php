<?php

require '../../main.inc.php';

require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT . '/compta/paiement/class/paiement.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/functions.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';

// --- Permissions check
if (empty($user->rights->facture->paiement)) {
    accessforbidden();
}

$langs->loadLangs(array("bills", "banks", "splitpayment@splitpayment"));

$facid = GETPOST('id', 'int');
$action = GETPOST('action', 'alpha');

// --- Load invoice
$facture = new Facture($db);
if ($facid) {
    $facture->fetch($facid);
}

// ==============================================================================
//  FORM PROCESSING (Action "addpayment")
// ==============================================================================

if ($action == 'addpayment' && !empty($facid)) {
    $error = 0;

    $amount1 = price2num(GETPOST('amount1', 'alpha'));
    $amount2 = price2num(GETPOST('amount2', 'alpha'));
    $total_amount = price2num(GETPOST('total_amount', 'alpha'));
    $datep = dol_mktime(12, 0, 0, GETPOST('pday', 'int'), GETPOST('pmonth', 'int'), GETPOST('pyear', 'int'));
    $payment_mode_id = GETPOST('mode_reglement_id', 'int');
    $label = GETPOST('label', 'alpha');
    $fk_bank1 = GETPOST('fk_bank1', 'int');
    $fk_bank2 = GETPOST('fk_bank2', 'int');

    if (empty($fk_bank1) || empty($fk_bank2)) {
        setEventMessage($langs->trans("ErrorFieldRequired", $langs->transnoentities("BankAccounts")), 'errors');
        $error++;
    }
    if ($fk_bank1 == $fk_bank2) {
        setEventMessage('Erreur : Les deux comptes bancaires doivent être différents.', 'errors');
        $error++;
    }
    if (abs(($amount1 + $amount2) - $total_amount) > 0.001) {
        setEventMessage('Erreur : La somme des deux montants ne correspond pas au montant total reçu.', 'errors');
        $error++;
    }
    if ($payment_mode_id <= 0) {
        setEventMessage($langs->trans("ErrorFieldRequired", $langs->transnoentities("PaymentMode")), 'errors');
        $error++;
    }

    if ($error == 0) {
        $db->begin();
        $batch_ref = 'SPLIT-'.dol_print_date(dol_now(), '%y%m%d%H%M%S').'-'.$facture->ref;
        $global_result = 1;

        // --- Paiement 1 ---
        if ($amount1 > 0) {
            $paiement1 = new Paiement($db);
            $paiement1->datep = $datep;
            $paiement1->paiementid = $payment_mode_id;
            $paiement1->num_paiement = $label;
            $paiement1->fk_bank = $fk_bank1;
            $paiement1->amounts[$facture->id] = $amount1;
            
            // LA SOLUTION FINALE : On utilise array_options
            $paiement1->array_options['options_batch_ref'] = $batch_ref;
            
            $payment1_id = $paiement1->create($user);
            
            if ($payment1_id <= 0) {
                setEventMessage("Erreur lors de l'enregistrement du premier paiement : " . $paiement1->error, 'errors');
                $global_result = -1;
            }
        }
        
        // --- Paiement 2 ---
        if ($amount2 > 0 && $global_result > 0) {
            $paiement2 = new Paiement($db);
            $paiement2->datep = $datep;
            $paiement2->paiementid = $payment_mode_id;
            $paiement2->num_paiement = $label;
            $paiement2->fk_bank = $fk_bank2;
            $paiement2->amounts[$facture->id] = $amount2;

            // LA SOLUTION FINALE : On utilise array_options
            $paiement2->array_options['options_batch_ref'] = $batch_ref;
            
            $payment2_id = $paiement2->create($user);

            if ($payment2_id <= 0) {
                setEventMessage("Erreur lors de l'enregistrement du second paiement : " . $paiement2->error, 'errors');
                $global_result = -1;
            }
        }

        if ($global_result > 0) {
            $db->commit();
            setEventMessage("Paiement ventilé enregistré avec succès.", 'mesgs');
            header('Location: '.DOL_URL_ROOT.'/compta/facture/card.php?id='.$facid);
            exit;
        } else {
            setEventMessage("Echec de l'enregistrement, l'opération a été annulée.", 'errors');
            $db->rollback();
        }
    }
}

// ==============================================================================
//  FORM DISPLAY (Aucun changement ici)
// ==============================================================================

llxHeader('', 'Saisir un règlement ventilé');
print load_fiche_titre('Saisir un règlement ventilé sur la facture ' . $facture->ref);
dol_htmloutput_errors();

$bank_accounts = array();
$sql = "SELECT rowid, label, ref FROM " . MAIN_DB_PREFIX . "bank_account WHERE clos = 0 ORDER BY label";
$resql = $db->query($sql);
if ($resql) {
    if ($db->num_rows($resql) > 0) {
        while ($obj = $db->fetch_object($resql)) {
            $bank_accounts[$obj->rowid] = $obj->label . (!empty($obj->ref) ? ' ('.$obj->ref.')' : '');
        }
    }
}

$payment_modes = array();
$sql_pm = "SELECT id, libelle FROM " . MAIN_DB_PREFIX . "c_paiement WHERE active = 1 ORDER BY libelle ASC";
$resql_pm = $db->query($sql_pm);
if ($resql_pm) {
    while ($obj_pm = $db->fetch_object($resql_pm)) {
        $payment_modes[$obj_pm->id] = $langs->trans($obj_pm->libelle);
    }
}

print '<form name="splitpaymentform" action="'.$_SERVER["PHP_SELF"].'?id='.$facid.'" method="POST">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="addpayment">';
print '<input type="hidden" name="id" value="'.$facid.'">';
print '<table class="border" width="100%">';
$form = new Form($db);

print '<tr><td class="titlefieldcreate">'.$langs->trans("Date").'</td><td>';
$date_to_show = ($action == 'addpayment' && isset($_POST['pday'])) ? dol_mktime(12, 0, 0, GETPOST('pday', 'int'), GETPOST('pmonth', 'int'), GETPOST('pyear', 'int')) : dol_now();
print $form->select_date($date_to_show, 'p', '', '', '', "splitpaymentform");
print '</td></tr>';
print '<tr><td class="titlefieldcreate">'.$langs->trans("PaymentMode").'</td><td>';
print '<select class="flat" name="mode_reglement_id">';
print '<option value="0">&nbsp;</option>';
$selected_pm = GETPOST('mode_reglement_id', 'int');
foreach ($payment_modes as $id => $label) {
    $selected = ($id == $selected_pm) ? ' selected="selected"' : '';
    print '<option value="'.$id.'"'.$selected.'>'.dol_escape_htmltag($label).'</option>';
}
print '</select></td></tr>';
print '<tr><td class="titlefieldcreate">Montant total reçu</td>';
$total_amount_val = GETPOST('total_amount', 'alpha'); 
if ($action != 'addpayment') {
    $total_amount_val = price($facture->total_ttc - $facture->getSommePaiement());
}
print '<td><input type="text" name="total_amount" id="total_amount" value="'.dol_escape_htmltag($total_amount_val).'"></td></tr>';
print '<tr><td class="titlefieldcreate">Libellé / Référence</td>';
print '<td><input type="text" name="label" class="maxwidth" value="'.dol_escape_htmltag(GETPOST('label', 'alpha')).'"></td></tr>';
print '<tr class="liste_titre"><td colspan="2">Ventilation sur les comptes</td></tr>';
print '<tr><td class="titlefieldcreate">Montant 1</td><td>';
print '<input type="text" name="amount1" id="amount1" style="width: 100px;" value="'.dol_escape_htmltag(GETPOST('amount1', 'alpha')).'"> &nbsp; sur le compte &nbsp; ';
print $form->selectarray('fk_bank1', $bank_accounts, GETPOST('fk_bank1', 'int'), 1);
print '</td></tr>';
print '<tr><td class="titlefieldcreate">Montant 2</td><td>';
print '<input type="text" name="amount2" id="amount2" style="width: 100px;" value="'.dol_escape_htmltag(GETPOST('amount2', 'alpha')).'"> &nbsp; sur le compte &nbsp; ';
print $form->selectarray('fk_bank2', $bank_accounts, GETPOST('fk_bank2', 'int'), 1);
print '</td></tr>';
print '<tr><td class="titlefieldcreate">Reste à ventiler</td>';
print '<td><span id="remaining" style="font-weight: bold; color: red;"></span></td></tr>';
print '</table>';

print '<div class="center">';
print '<br><input type="submit" class="button" value="Enregistrer le règlement">';
print '&nbsp;&nbsp;&nbsp;';
print '<a href="'.DOL_URL_ROOT.'/compta/facture/card.php?id='.$facid.'" class="button">Annuler</a>';
print '</div>';
print '</form>';
?>
<script type="text/javascript">
    jQuery(document).ready(function() {
        function parseFrenchFloat(numStr) {
            if (!numStr) return 0;
            var cleanedStr = String(numStr).replace(/\s/g, '').replace(',', '.');
            var number = parseFloat(cleanedStr);
            return isNaN(number) ? 0 : number;
        }
        function updateRemaining() {
            var total = parseFrenchFloat(jQuery('#total_amount').val());
            var amount1 = parseFrenchFloat(jQuery('#amount1').val());
            var amount2 = parseFrenchFloat(jQuery('#amount2').val());
            var remaining = total - amount1 - amount2;
            var remainingFormatted = new Intl.NumberFormat('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(remaining);
            jQuery('#remaining').text(remainingFormatted); 
            if (remaining.toFixed(2) == 0.00) {
                jQuery('#remaining').css('color', 'green');
            } else {
                jQuery('#remaining').css('color', 'red');
            }
        }
        updateRemaining();
        jQuery('#total_amount, #amount1, #amount2').on('keyup change', function() {
            updateRemaining();
        });
    });
</script>
<?php
llxFooter();
$db->close();
?>