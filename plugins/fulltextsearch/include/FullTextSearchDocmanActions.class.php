<?php
/**
 * Copyright (c) Enalean, 2012 - 2014. All Rights Reserved.
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

/**
 * Class responsible to send requests to an indexation server
 */
class FullTextSearchDocmanActions {

    /**
     * @var FullTextSearch_IIndexDocuments
     */
    private $client;

    /** @var ElasticSearch_1_2_RequestDocmanDataFactory */
    private $request_data_factory;

    /** @var BackendLogger */
    private $logger;

    public function __construct(
        FullTextSearch_IIndexDocuments $client,
        ElasticSearch_1_2_RequestDocmanDataFactory $request_data_factory,
        BackendLogger $logger
    ) {
        $this->client               = $client;
        $this->request_data_factory = $request_data_factory;
        $this->logger               = $logger;
    }

    public function checkProjectMappingExists($project_id) {
        $this->logger->debug('[Docman] ElasticSearch: get the mapping for project #' . $project_id);

        return count($this->client->getMapping($project_id)) > 0;
    }

    public function initializeProjetMapping($project_id) {
        $this->logger->debug('[Docman] ElasticSearch: initialize the mapping for project #' . $project_id);

        $this->client->setMapping(
            $project_id,
            $this->request_data_factory->getPUTMappingData($project_id)
        );
    }

    /**
     * Index a new document with permissions
     *
     * @param Docman_Item    $item    The docman item
     * @param Docman_Version $version The version to index
     */
    public function indexNewDocument(Docman_Item $item, Docman_Version $version) {
        $this->logger->debug('[Docman] ElasticSearch: index new document #' . $item->getId());

        $indexed_data = $this->getIndexedData($item) + $this->getItemContent($version);

        $this->client->index($item->getGroupId(), $item->getId(), $indexed_data);
    }

    public function indexNewEmptyDocument(Docman_Item $item) {
        $this->logger->debug('[Docman] ElasticSearch: index new empty document #' . $item->getId());

        $indexed_data = $this->getIndexedData($item);

        $this->client->index($item->getGroupId(), $item->getId(), $indexed_data);
    }

    public function indexNewLinkDocument(Docman_Item $item) {
        $this->logger->debug('[Docman] ElasticSearch: index new link document #' . $item->getId());

        $indexed_data = $this->getIndexedData($item) + $this->getLinkContent($item);

        $this->client->index($item->getGroupId(), $item->getId(), $indexed_data);
    }

    public function indexNewDocmanFolder(Docman_Item $item) {
        $this->logger->debug('[Docman] ElasticSearch: index new folder #' . $item->getId());

        $indexed_data = $this->getIndexedData($item);

        $this->client->index($item->getGroupId(), $item->getId(), $indexed_data);
    }

    /**
     * Index a new wiki document with permissions
     *
     * @param Docman_Item    $item                The docman item
     * @param array          $wiki_page_metadata  The wiki page metadata
     */
    public function indexNewWikiDocument(Docman_Item $item, array $wiki_page_metadata) {
        $this->logger->debug('[Docman] ElasticSearch: index new docman wiki document #' . $item->getId());

        $indexed_data = $this->getIndexedData($item) + $this->getWikiContent($wiki_page_metadata);

        $this->client->index($item->getGroupId(), $item->getId(), $indexed_data);
    }

    public function indexCopiedItem(Docman_Item $item) {
        $this->logger->debug('[Docman] ElasticSearch: index new copied item #' . $item->getId() . ' and its children');

        $item_factory = $this->getDocmanItemFactory($item);
        $items        = array_merge(array($item), $item_factory->getAllChildrenFromParent($item));

        foreach($items as $item_to_index) {
            $this->logger->debug('[Docman] ElasticSearch: index item #' . $item_to_index->getId());

            $indexed_data = $this->getIndexedData($item_to_index) + $this->getContent($item_to_index);
            $this->client->index($item_to_index->getGroupId(), $item_to_index->getId(), $indexed_data);
        }
    }

    /**
     * @param Docman_Item $item
     *
     * @return Docman_ItemFactory
     */
    private function getDocmanItemFactory(Docman_Item $item) {
        return Docman_ItemFactory::instance($item->getGroupId());
    }

    private function getContent(Docman_Item $item) {
        $item_factory = $this->getDocmanItemFactory($item);
        $item_type    = $item_factory->getItemTypeForItem($item);

        switch ($item_type) {
            case PLUGIN_DOCMAN_ITEM_TYPE_EMPTY:
            case PLUGIN_DOCMAN_ITEM_TYPE_FOLDER:
                return array();
                break;

            case PLUGIN_DOCMAN_ITEM_TYPE_WIKI:
                $wiki_page = new WikiPage($item->getGroupId(), $item->getPagename());

                return $this->getWikiContent($wiki_page->getMetadata());
                break;

            case PLUGIN_DOCMAN_ITEM_TYPE_LINK:
                return $this->getLinkContent($item);
                break;

            case PLUGIN_DOCMAN_ITEM_TYPE_EMBEDDEDFILE:
            case PLUGIN_DOCMAN_ITEM_TYPE_FILE:
                return $this->getItemContent($item->getCurrentVersion());
                break;

            default:
                $this->logger->debug("[Docman] ElasticSearch: unrecognized item type, can't index content");

                return array();
                break;
        }
    }

    /**
     * Update document approval comments
     *
     * @param Docman_Item $item
     * @param Docman_Version $version
     */
    public function indexDocumentApprovalComment(Docman_Item $item, Docman_Version $version) {
        $this->logger->debug('[Docman] ElasticSearch: index new document approval comment #' . $item->getId());

        $update_data = array(
            'approval_table_comments' => $this->request_data_factory->getDocumentApprovalTableComments($item, $version)
        );

        $this->indexOrUpdate($item->getGroupId(), $item->getId(), $update_data);
    }

    /**
     * Index a new document with permissions
     *
     * @param Docman_Item    $item    The docman item
     * @param Docman_Version $version The version to index
     */
    public function indexNewVersion(Docman_Item $item, Docman_Version $version) {
        try {
            $this->client->getIndexedElement($item->getGroupId(), $item->getId());

            $this->logger->debug('[Docman] ElasticSearch: index new version #' . $version->getId() . ' for document #' . $item->getId());

            $update_data = array();
            $this->request_data_factory->updateFile($update_data, $version->getPath());
            $this->client->update($item->getGroupId(), $item->getId(), $update_data);

        } catch (ElasticSearch_ElementNotIndexed $exception) {
            $this->indexNewDocument($item, $version);
            return;
        }
    }

    /**
     * Index a new wiki document with permissions
     *
     * @param Docman_Item    $item               The docman item
     * @param Docman_Version $version            The version to index
     * @param array          $wiki_page_metadata WikiPage metadata
     */
    public function indexNewWikiVersion(Docman_Item $item, array $wiki_page_metadata) {
        try {
            $this->client->getIndexedElement($item->getGroupId(), $item->getId());

            $this->logger->debug('[Docman] ElasticSearch: index new version for wiki document #' . $item->getId());

            $update_data = array();
            $this->request_data_factory->updateContent($update_data, $wiki_page_metadata['content']);
            $this->client->update($item->getGroupId(), $item->getId(), $update_data);

        } catch (ElasticSearch_ElementNotIndexed $exception) {
            $this->indexNewWikiDocument($item, $wiki_page_metadata);
            return;
        }
    }

    /**
     * Update title, description and custom textual metadata of a document
     *
     * @param Docman_Item $item The item
     */
    public function updateDocument(Docman_Item $item) {
        try {
            $this->client->getIndexedElement($item->getGroupId(), $item->getId());

            $this->logger->debug('[Docman] ElasticSearch: update document #' . $item->getId());

            $update_data = array();
            $this->request_data_factory->setUpdatedData($update_data, 'title',       $item->getTitle());
            $this->request_data_factory->setUpdatedData($update_data, 'description', $item->getDescription());

            $this->updateContent($item, $update_data);

            $update_data = $this->request_data_factory->updateCustomTextualMetadata($item, $update_data);
            $update_data = $this->updateCustomDateMetadata($item, $update_data);

            $this->client->update($item->getGroupId(), $item->getId(), $update_data);

        } catch (ElasticSearch_ElementNotIndexed $exception) {
            $this->indexNonexistantDocument($item);
            return;
        }
    }

    private function indexNonexistantDocument(Docman_Item $item) {
        $item_factory = $this->getDocmanItemFactory($item);
        $item_type    = $item_factory->getItemTypeForItem($item);

        switch ($item_type) {
            case PLUGIN_DOCMAN_ITEM_TYPE_EMPTY:
                $this->indexNewEmptyDocument($item);
                break;

            case PLUGIN_DOCMAN_ITEM_TYPE_FOLDER:
                $this->indexNewDocmanFolder($item);
                break;

            case PLUGIN_DOCMAN_ITEM_TYPE_WIKI:
                $wiki_page = new WikiPage($item->getGroupId(), $item->getPagename());
                $this->indexNewWikiDocument($item, $wiki_page->getMetadata());
                break;

            case PLUGIN_DOCMAN_ITEM_TYPE_LINK:
                $this->indexNewLinkDocument($item);
                break;

            case PLUGIN_DOCMAN_ITEM_TYPE_EMBEDDEDFILE:
            case PLUGIN_DOCMAN_ITEM_TYPE_FILE:
                $this->indexNewDocument($item, $item->getCurrentVersion());
                break;

            default:
                $this->logger->debug("[Docman] ElasticSearch: unrecognized item type, can't index it");
                break;
        }
    }

    private function updateContent(Docman_Item $item, array &$update_data) {
        $item_factory = Docman_ItemFactory::instance($item->getGroupId());
        $item_type    = $item_factory->getItemTypeForItem($item);

        switch ($item_type) {
            case PLUGIN_DOCMAN_ITEM_TYPE_EMPTY:
                break;

            case PLUGIN_DOCMAN_ITEM_TYPE_WIKI:
                $wiki_page = new WikiPage($item->getGroupId(), $item->getPagename());
                $this->request_data_factory->updateContent($update_data, $wiki_page->getContent());
                break;

            case PLUGIN_DOCMAN_ITEM_TYPE_LINK:
                $this->request_data_factory->updateContent($update_data, $item->getUrl());
                break;

            case PLUGIN_DOCMAN_ITEM_TYPE_EMBEDDEDFILE:
            case PLUGIN_DOCMAN_ITEM_TYPE_FILE:
                $this->request_data_factory->updateFile($update_data, $item->getCurrentVersion()->getPath());
                break;

            default:
                $this->logger->debug("[Docman] ElasticSearch: unrecognized item type, can't update content");
                break;
        }
    }

    /**
     * Index the new permissions of a document
     *
     * @param Docman_Item the document
     */
    public function updatePermissions(Docman_Item $item) {
        $this->logger->debug('[Docman] ElasticSearch: update permissions of document #' . $item->getId(). ' and its children');

        $item_factory = $this->getDocmanItemFactory($item);
        $items        = array_merge(array($item), $item_factory->getAllChildrenFromParent($item));

        foreach($items as $item_to_index) {
            try {
                $this->client->getIndexedElement($item->getGroupId(), $item->getId());

                $this->logger->debug('[Docman] ElasticSearch: update permissions of item #' . $item_to_index->getId());

                $update_data = array();
                $this->request_data_factory->setUpdatedData(
                    $update_data,
                    'permissions',
                    $this->request_data_factory->getCurrentPermissions($item)
                );

                $this->client->update($item_to_index->getGroupId(), $item_to_index->getId(), $update_data);

            } catch(ElasticSearch_ElementNotIndexed $exception) {
                $this->indexNonexistantDocument($item_to_index);
                return;
            }
        }
    }

    /**
     * Remove an indexed document
     *
     * @param Docman_Item $item The item to delete
     */
    public function delete(Docman_Item $item) {
        $this->logger->debug('[Docman] ElasticSearch: delete document #' . $item->getId());

        try{
            $this->client->getIndexedElement($item->getGroupId(), $item->getId());
            $this->client->delete($item->getGroupId(), $item->getId());

        } catch (ElasticSearch_ElementNotIndexed $exception) {
            $this->logger->debug('[Docman] ElasticSearch: element #' . $item->getId() . ' not indexed, nothing to delete');
            return;
        }

    }

    public function reIndexProjectDocuments(Docman_ProjectItemsBatchIterator $document_iterator, $project_id) {
        $this->deleteForProject($project_id);
        $this->indexAllProjectDocuments($document_iterator, $project_id);
    }

    private function deleteForProject($project_id) {
        $this->logger->debug('[Docman] ElasticSearch: deleting all project documents #' . $project_id);

        try{
            $this->client->getIndexedType($project_id);
            $this->client->deleteType($project_id);

        } catch (ElasticSearch_TypeNotIndexed $exception) {
            $this->logger->debug('[Docman] ElasticSearch: project #' . $project_id . ' not indexed, nothing to delete');
            return;
        }
    }

    private function indexAllProjectDocuments(Docman_ProjectItemsBatchIterator $document_iterator, $project_id) {
        $this->logger->debug('[Docman] ElasticSearch: indexing all project documents #' . $project_id);

        $this->initializeProjetMapping($project_id);
        $document_iterator->rewind();
        $docman_item_factory = Docman_ItemFactory::instance($project_id);
        while ($batch = $document_iterator->next()) {
            foreach ($batch as $item) {
                $this->indexNonexistantDocument($item);
            }
        }
    }

    private function getIndexedData(Docman_Item $item) {
        return $this->request_data_factory->getIndexedDataForItemVersion($item) +
            $this->request_data_factory->getCustomTextualMetadataValue($item) +
            $this->getCustomDateMetadata($item);
    }

    private function getItemContent(Docman_Version $version) {
        return $this->request_data_factory->getFileContent($version);
    }

    private function getWikiContent(array $wiki_metadata) {
        return $this->request_data_factory->getWikiContent($wiki_metadata);
    }

    private function getLinkContent(Docman_Item $item) {
        return $this->request_data_factory->getLinkContent($item);
    }

    /**
     * Get the user defined item date metadata
     *
     * @param Docman_Item $item The item indexed
     *
     * @return array
     */

    private function getCustomDateMetadata(Docman_Item $item) {
        $this->updateMappingWithNewDateMetadata($item);

        return $this->request_data_factory->getCustomDateMetadataValues($item);
    }


    private function updateCustomDateMetadata(Docman_Item $item, array $update_data) {
        $this->updateMappingWithNewDateMetadata($item);

        return $this->request_data_factory->updateCustomDateMetadata($item, $update_data);
    }

    private function updateMappingWithNewDateMetadata(Docman_Item $item) {
        $mapping_data = $this->request_data_factory->getPUTDateMappingMetadata(
            $item,
            $this->client->getMapping($item->getGroupId())
        );

        if (! $this->mappingNeedsToBoUpdated($item, $mapping_data)) {
            return;
        }

        $this->logger->debug('[Docman] ElasticSearch: update mapping of project #' . $item->getGroupId() .
            ' with new custom date metadata');

        $this->client->setMapping(
            $item->getGroupId(),
            $mapping_data
        );
    }

    private function mappingNeedsToBoUpdated(Docman_Item $item, array $mapping_data) {
        return $mapping_data[$item->getGroupId()][ElasticSearch_1_2_RequestDocmanDataFactory::MAPPING_PROPERTIES_KEY]
            !== array();
    }

}
