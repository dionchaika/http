<?php

/**
 * The Psr Http Library.
 *
 * @package dionchaika/http
 * @version 1.0.0
 * @license MIT
 * @author Dion Chaika <dionchaika@gmail.com>
 */

namespace Dionchaika\Http\FormData;

use InvalidArgumentException;

class FormData
{
    /**
     * The form data boundary.
     *
     * @var string
     */
    protected $boundary;

    /**
     * The array of form data entries.
     *
     * @var array
     */
    protected $entries = [];

    /**
     * @param array $formData
     * @throws \InvalidArgumentException
     */
    public function __construct(array $formData = [])
    {
        foreach ($formData as $entry) {
            if (!isset($entry['name'])) {
                throw new InvalidArgumentException(
                    'Invalid form data! Form data field is not set: name!'
                );
            }

            if (!isset($entry['value'])) {
                throw new InvalidArgumentException(
                    'Invalid form data! Form data field is not set: value!'
                );
            }

            $this->append(
                $entry['name'],
                $entry['value'],
                isset($entry['filename']) ? $entry['filename'] : null,
                isset($entry['headers']) ? $entry['headers'] : []
            );
        }

        $this->boundary = $this->generateBoudary();
    }

    /**
     * Get the form data boundary.
     *
     * @return string
     */
    public function getBoundary(): string
    {
        return $this->boundary;
    }

    /**
     * Append a new form data field.
     *
     * @param string $name
     * @param mixed $value
     * @param string|null $filename
     * @param array $headers
     * @return \Dionchaika\Http\FormData\FormData
     * @throws \InvalidArgumentException
     */
    public function append(
        string $name,
        $value,
        ?string $filename = null,
        array $headers = []
    ): FormData {
        if (0 === strncmp($value, '@', 1)) {
            $filePath = substr($value, 1);

            if (!is_file($filePath)) {
                throw new InvalidArgumentException(
                    'File does not exists: '.$filePath.'!'
                );
            }

            $value = file_get_contents($filePath);
            if (false === $value) {
                throw new InvalidArgumentException(
                    'Unable to get the contents of the file: '.$filePath.'!'
                );
            }

            $filename = $filename ?? basename($filePath);

            if (!in_array('content-type', array_change_key_case($headers, \CASE_LOWER))) {
                $type = mime_content_type($filePath);
                if (false === $type) {
                    throw new InvalidArgumentException(
                        'Unable to get a MIME-type of the file: '.$filePath.'!'
                    );
                }

                $headers['Content-Type'] = $type;
            }
        }

        $this->entries[$name][] = [
            'value' => $value,
            'filename' => $filename,
            'headers' => $headers
        ];

        return $this;
    }

    /**
     * Generate the form data boundary.
     *
     * @return string
     */
    protected function generateBoudary(): string
    {
        $boundaryLen = 16;
        $boundaryChars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

        $randMin = 0;
        $randMax = strlen($boundaryChars) - 1;

        $boundary = '';
        for ($i = 0; $i < $boundaryLen; ++$i) {
            $boundary .= $boundaryChars[rand($randMin, $randMax)];
        }

        return '----DionchaikaFormBoundary'.$boundary;
    }

    /**
     * Get the string
     * representation of the form data.
     *
     * @return string
     */
    public function __toString(): string
    {
        $formData = '';

        foreach ($this->entries as $name => $entry) {
            foreach ($entry as $field) {
                $formData .= "--{$this->boundary}\r\n";
                $formData .= "Content-Disposition: form-data; name=\"{$name}\"";

                if (null !== $field['filename']) {
                    $formData .= "; filename=\"{$field['filename']}\"\r\n";
                } else {
                    $formData .= "\r\n";
                }

                foreach ($field['headers'] as $n => $v) {
                    $v = is_array($v) ? implode(', ', $v) : $v;
                    $formData .= "{$n}: {$v}\r\n";
                }

                $formData .= "\r\n{$field['value']}\r\n";
            }
        }

        return "{$formData}--{$this->boundary}";
    }
}
