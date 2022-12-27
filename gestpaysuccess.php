<?php
/**
 * Payments Module
 *
 * @package modules
 * @subpackage payments
 * @category Third Party Xaraya Module
 * @version 1.0.0
 * @copyright (C) 2016 Luetolf-Carroll GmbH
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @author Marc Lutolf <marc@luetolf-carroll.com>
 */
/**
* PSSPL : Added the file to redirect the GestPay Payment Success Response to the required URL.
*/

//We are in <root>/html/modules/payments and we want <root>/lib
include '../../../lib/bootstrap.php';

//Import the server class to use the call xarServer::getCurrentURL()
sys::import('xaraya.server');

$url = xarServer::getCurrentURL();

$url = str_replace("code/modules/payments/gestpaysuccess.php?a", "index.php?module=payments&func=phase3&a", $url);

xarController::redirect($url);
