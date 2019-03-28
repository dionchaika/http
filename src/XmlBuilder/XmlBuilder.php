<?php

/**
 * The Psr Http Library.
 *
 * @package dionchaika/http
 * @version 1.0.0
 * @license MIT
 * @author Dion Chaika <dionchaika@gmail.com>
 */

namespace Dionchaika\Http\XmlBuilder;

class XmlBuilder
{
    /**
     * Create a new XML from array.
     *
     * @param array $data
     * @param string $encoding
     * @return string
     */
    public static function createFromArray(array $data, string $encoding = 'utf-8'): string
    {
        $xml = '<?xml version="1.0" encoding="'.$encoding.'"?>';
        $xml .= static::getXmlElement($data);

        return $xml;
    }

    /**
     * Get an XML element.
     *
     * @param array $data
     * @return string
     */
    protected static function getXmlElement(array $data): string
    {
        $xmlElement = '';

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                if (0 === count($value)) {
                    $xmlElement .= '<'.$key.' />';
                } else {
                    $xmlElement .= '<'.$key.'>';
                    $xmlElement .= static::getXmlElement($value);
                    $xmlElement .= '</'.explode(' ', $key, 2)[0].'>';
                }
            } else {
                $xmlElement .= '<'.$key.'>';
                $xmlElement .= $value;
                $xmlElement .= '</'.explode(' ', $key, 2)[0].'>';
            }
        }

        return $xmlElement;
    }
}
