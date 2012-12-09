<?php
/**
 * This code is free software; you can redistribute it and/or modify it under
 * the terms of the new BSD License.
 *
 * Copyright (c) 2009-2011, Sebastian Staudt
 *
 * @license http://www.opensource.org/licenses/bsd-license.php New BSD License
 */

/**
 * This class is used to streamline access to XML-based data resources
 *
 * @author     Sebastian Staudt
 * @package    steam-condenser
 * @subpackage community
 */
abstract class XMLData {

    /**
     * Loads XML data from the given URL and returns it parsed into a
     * <var>SimpleXMLElement</var>
     *
     * @param string $url The URL to load the data from
     * @return SimpleXMLElement The parsed XML data
     */
    protected function getData($url) {
        return new SimpleXMLElement(file_get_contents($url));
    }

}
