<?php

// no direct access
defined('_JEXEC') or die('Restricted access');

class plgJ2StorePayment_jeebInstallerScript {

    /**
     * Check for minimum requirement
     * and abort if the current J2Store release is older
     *
     * @param $type
     * @param $parent
     * @return bool
     */
    function preflight( $type, $parent ) {

        $xmlfile = JPATH_ADMINISTRATOR.'/components/com_j2store/com_j2store.xml';

        $xml = JFactory::getXML($xmlfile);

        $version=(string)$xml->version;

        if( version_compare( $version, '2.6.7', 'lt' ) ) {
            Jerror::raiseWarning(null, 'You are using an old version of J2Store. Please upgrade to the latest version');
            return false;
        }

    }
}
