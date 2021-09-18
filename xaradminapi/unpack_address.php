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
 * Handle getconfig hook calls
 *
 */

function payments_adminapi_unpack_address($args)
{
    // Make sure we have a serialized address
    if (!isset($args['address']) || !is_string($args['address'])) {
        throw new Exception(xarML('Missing address parameter'));
    }

    // Get an address property
    $address = DataPropertyMaster::getProperty(['name' => 'address']);
    // Give it our address value
    $address->value = $args['address'];
    // Get the address array elements back
    $addressfields = $address->getValueArray();

    // Rework them to have an element for each one we need
    $newfields = [];
    foreach ($addressfields as $field) {
        $newfields[$field['id']] = $field['value'];
    }
    $street = $newfields['street'] ?? '';
    $city = $newfields['city'] ?? '';
    $postal_code = $newfields['postal_code'] ?? '';

    // The country part needs special treatment
    $country = $newfields['country'] ?? '';
    if (!empty($country)) {
        $countryobject = DataPropertyMaster::getProperty(['name' => 'countrylisting']);
        $countryobject->value = $country;
        $country = $countryobject->getValue();
    }

    // Rearrange the fields into lines, starting with line 1
    $lines = [
        1 => $street,
        2 => $city,
        3 => $postal_code,
        4 => $country,
        5 => strtoupper($countryobject->value),     // Country code
    ];
    return $lines;
}
