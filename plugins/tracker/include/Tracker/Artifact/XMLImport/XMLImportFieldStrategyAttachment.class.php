<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

class Tracker_Artifact_XMLImport_XMLImportFieldStrategyAttachment implements Tracker_Artifact_XMLImport_XMLImportFieldStrategy {


    const FILE_INFO_COPY_OPTION = 'is_migrated';

    /** @var string */
    private $extraction_path;

    /** @var Logger */
    private $logger;

    /** @var Tracker_Artifact_XMLImport_CollectionOfFilesToImportInArtifact */
    private $files_importer;

    public function __construct($extraction_path, Tracker_Artifact_XMLImport_CollectionOfFilesToImportInArtifact $files_importer, Logger $logger) {
        $this->extraction_path = $extraction_path;
        $this->files_importer  = $files_importer;
        $this->logger          = $logger;
    }

    /**
     * Extract Field data from XML input
     *
     * @param Tracker_FormElement_Field $field
     * @param SimpleXMLElement $field_change
     *
     * @return mixed
     */
    public function getFieldData(Tracker_FormElement_Field $field, SimpleXMLElement $field_change) {
        $values      = $field_change->value;
        $files_infos = array();

        foreach ($values as $value) {
            try {
                $attributes = $value->attributes();
                $file_id    = (string) $attributes['ref'];
                $file       = $this->files_importer->getFileXML($file_id);

                if (! $this->files_importer->fileIsAlreadyImported($file_id)) {
                    $files_infos[] = $this->getFileInfoForAttachment($file);
                    $this->files_importer->markAsImported($file_id);
                }
            } catch (Tracker_Artifact_XMLImport_Exception_FileNotFoundException $exception) {
                $this->logger->warn('Skipped attachment field: ' . $exception->getMessage());
            }
        }

        if (count($files_infos) === 0) {
            throw new Tracker_Artifact_XMLImport_Exception_NoAttachementsException();
        }

        return $files_infos;
    }

    private function getFileInfoForAttachment(SimpleXMLElement $file_xml) {
        $file_path =  $this->extraction_path .'/'. (string) $file_xml->path;
        if (! is_file($file_path)) {
            throw new Tracker_Artifact_XMLImport_Exception_FileNotFoundException($file_path);
        }
        return array(
            self::FILE_INFO_COPY_OPTION => true,
            'name'                      => (string) $file_xml->filename,
            'type'                      => (string) $file_xml->filetype,
            'description'               => (string) $file_xml->description,
            'size'                      => (int) $file_xml->filesize,
            'tmp_name'                  => $file_path,
            'error'                     => UPLOAD_ERR_OK,
        );
    }
}