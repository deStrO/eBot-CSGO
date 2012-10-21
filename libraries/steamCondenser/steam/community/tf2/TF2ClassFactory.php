<?php
/**
 * This code is free software; you can redistribute it and/or modify it under
 * the terms of the new BSD License.
 *
 * Copyright (c) 2008-2011, Sebastian Staudt
 *
 * @license http://www.opensource.org/licenses/bsd-license.php New BSD License
 */

require_once STEAM_CONDENSER_PATH . 'steam/community/tf2/TF2Class.php';
require_once STEAM_CONDENSER_PATH . 'steam/community/tf2/TF2Engineer.php';
require_once STEAM_CONDENSER_PATH . 'steam/community/tf2/TF2Medic.php';
require_once STEAM_CONDENSER_PATH . 'steam/community/tf2/TF2Sniper.php';
require_once STEAM_CONDENSER_PATH . 'steam/community/tf2/TF2Spy.php';

/**
 * The <var>TF2ClassFactory</var> is used to created instances of
 * <var>TF2Class</var> based on the XML input data
 *
 * @author     Sebastian Staudt
 * @package    steam-condenser
 * @subpackage community
 */
abstract class TF2ClassFactory {

    /**
     * Creates a new instance of a TF2 class instance based on the given XML
     * data
     *
     * This returns an instance of <var>TF2Class</var> or its subclasses
     * <var>TF2Engineer</var>, <var>TF2Medic</var>, <var>TF2Sniper</var> or
     * <var>TF2Spy</var> depending on the given XML data.
     *
     * @param SimpleXMLElement $classData The XML data for the class
     * @return TF2Class The statistics for the given class data
     */
    public static function getTF2Class(SimpleXMLElement $classData) {
        switch($classData->className) {
            case 'Engineer':
                return new TF2Engineer($classData);
            case 'Medic':
                return new TF2Medic($classData);
            case 'Sniper':
                return new TF2Sniper($classData);
            case 'Spy':
                return new TF2Spy($classData);
            default:
                return new TF2Class($classData);
        }
    }
}
