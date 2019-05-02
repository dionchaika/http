<?php

/**
 * The PSR HTTP Library.
 *
 * @package dionchaika/http
 * @version 1.0.0
 * @license MIT
 * @author Dion Chaika <dionchaika@gmail.com>
 */

namespace Dionchaika\Http\Utils;

class XMLBuilder
{
    /**
     * Create a new XML from array.
     *
     *      <code>
     *          XMLBuilder::createFromArray([
     *
     *              //
     *              // A root element.
     *              //
     *              'users' => [
     *
     *                  //
     *                  // Will create the following XML:
     *                  //
     *                  //      <user id="1">
     *                  //           <name>Max</name>
     *                  //           <age>Max</age>
     *                  //           <email>Max</email>
     *                  //           <married value="0" />
     *                  //           <friends></friends>
     *                  //      </user>
     *                  //
     *                  'user id="1"' => [
     *
     *                      'name'              => 'Max',
     *                      'age'               => 21,
     *                      'email'             => 'max@email.com',
     *                      'married value="0"' => [],
     *                      'friends' => ''
     *
     *                  ],
     *                  'user id="2"' => [
     *
     *                      'name'              => 'John',
     *                      'age'               => 23,
     *                      'email'             => 'john@email.com'
     *                      'married value="1"' => [],
     *                      'friends' => ''
     *
     *                  ],
     *                  'user id="3"' => [
     *
     *                      'name'              => 'Steve',
     *                      'age'               => 25,
     *                      'email'             => 'steve@email.com'
     *                      'married value="1"' => [],
     *                      'friends' => ''
     *
     *                  ],
     *
     *              ]
     *
     *          ]);
     *      </code>
     *
     * @param mixed[] $data
     * @param string  $encoding
     * @return string
     */
    public static function createFromArray(array $data, string $encoding = 'utf-8'): string
    {
        $xml = '<?xml version="1.0" encoding="'.$encoding.'"?>';
        $xml .= static::getXMLElement($data);

        return $xml;
    }

    /**
     * Get an XML element.
     *
     * @param mixed[] $data
     * @return string
     */
    protected static function getXMLElement(array $data): string
    {
        $xmlElement = '';

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                if (0 === count($value)) {
                    $xmlElement .= '<'.$key.' />';
                } else {
                    $xmlElement .= '<'.$key.'>';
                    $xmlElement .= static::getXMLElement($value);
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
