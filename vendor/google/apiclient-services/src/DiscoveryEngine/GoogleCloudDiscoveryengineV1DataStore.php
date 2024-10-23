<?php
/*
 * Copyright 2014 Google Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License"); you may not
 * use this file except in compliance with the License. You may obtain a copy of
 * the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS, WITHOUT
 * WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the
 * License for the specific language governing permissions and limitations under
 * the License.
 */

namespace Google\Service\DiscoveryEngine;

class GoogleCloudDiscoveryengineV1DataStore extends \Google\Collection
{
  protected $collection_key = 'solutionTypes';
  protected $billingEstimationType = GoogleCloudDiscoveryengineV1DataStoreBillingEstimation::class;
  protected $billingEstimationDataType = '';
  protected $cmekConfigType = GoogleCloudDiscoveryengineV1CmekConfig::class;
  protected $cmekConfigDataType = '';
  /**
   * @var string
   */
  public $contentConfig;
  /**
   * @var string
   */
  public $createTime;
  /**
   * @var string
   */
  public $defaultSchemaId;
  /**
   * @var string
   */
  public $displayName;
  protected $documentProcessingConfigType = GoogleCloudDiscoveryengineV1DocumentProcessingConfig::class;
  protected $documentProcessingConfigDataType = '';
  /**
   * @var string
   */
  public $industryVertical;
  /**
   * @var string
   */
  public $kmsKeyName;
  /**
   * @var string
   */
  public $name;
  protected $servingConfigDataStoreType = GoogleCloudDiscoveryengineV1ServingConfigDataStore::class;
  protected $servingConfigDataStoreDataType = '';
  /**
   * @var string[]
   */
  public $solutionTypes;
  protected $startingSchemaType = GoogleCloudDiscoveryengineV1Schema::class;
  protected $startingSchemaDataType = '';
  protected $workspaceConfigType = GoogleCloudDiscoveryengineV1WorkspaceConfig::class;
  protected $workspaceConfigDataType = '';

  /**
   * @param GoogleCloudDiscoveryengineV1DataStoreBillingEstimation
   */
  public function setBillingEstimation(GoogleCloudDiscoveryengineV1DataStoreBillingEstimation $billingEstimation)
  {
    $this->billingEstimation = $billingEstimation;
  }
  /**
   * @return GoogleCloudDiscoveryengineV1DataStoreBillingEstimation
   */
  public function getBillingEstimation()
  {
    return $this->billingEstimation;
  }
  /**
   * @param GoogleCloudDiscoveryengineV1CmekConfig
   */
  public function setCmekConfig(GoogleCloudDiscoveryengineV1CmekConfig $cmekConfig)
  {
    $this->cmekConfig = $cmekConfig;
  }
  /**
   * @return GoogleCloudDiscoveryengineV1CmekConfig
   */
  public function getCmekConfig()
  {
    return $this->cmekConfig;
  }
  /**
   * @param string
   */
  public function setContentConfig($contentConfig)
  {
    $this->contentConfig = $contentConfig;
  }
  /**
   * @return string
   */
  public function getContentConfig()
  {
    return $this->contentConfig;
  }
  /**
   * @param string
   */
  public function setCreateTime($createTime)
  {
    $this->createTime = $createTime;
  }
  /**
   * @return string
   */
  public function getCreateTime()
  {
    return $this->createTime;
  }
  /**
   * @param string
   */
  public function setDefaultSchemaId($defaultSchemaId)
  {
    $this->defaultSchemaId = $defaultSchemaId;
  }
  /**
   * @return string
   */
  public function getDefaultSchemaId()
  {
    return $this->defaultSchemaId;
  }
  /**
   * @param string
   */
  public function setDisplayName($displayName)
  {
    $this->displayName = $displayName;
  }
  /**
   * @return string
   */
  public function getDisplayName()
  {
    return $this->displayName;
  }
  /**
   * @param GoogleCloudDiscoveryengineV1DocumentProcessingConfig
   */
  public function setDocumentProcessingConfig(GoogleCloudDiscoveryengineV1DocumentProcessingConfig $documentProcessingConfig)
  {
    $this->documentProcessingConfig = $documentProcessingConfig;
  }
  /**
   * @return GoogleCloudDiscoveryengineV1DocumentProcessingConfig
   */
  public function getDocumentProcessingConfig()
  {
    return $this->documentProcessingConfig;
  }
  /**
   * @param string
   */
  public function setIndustryVertical($industryVertical)
  {
    $this->industryVertical = $industryVertical;
  }
  /**
   * @return string
   */
  public function getIndustryVertical()
  {
    return $this->industryVertical;
  }
  /**
   * @param string
   */
  public function setKmsKeyName($kmsKeyName)
  {
    $this->kmsKeyName = $kmsKeyName;
  }
  /**
   * @return string
   */
  public function getKmsKeyName()
  {
    return $this->kmsKeyName;
  }
  /**
   * @param string
   */
  public function setName($name)
  {
    $this->name = $name;
  }
  /**
   * @return string
   */
  public function getName()
  {
    return $this->name;
  }
  /**
   * @param GoogleCloudDiscoveryengineV1ServingConfigDataStore
   */
  public function setServingConfigDataStore(GoogleCloudDiscoveryengineV1ServingConfigDataStore $servingConfigDataStore)
  {
    $this->servingConfigDataStore = $servingConfigDataStore;
  }
  /**
   * @return GoogleCloudDiscoveryengineV1ServingConfigDataStore
   */
  public function getServingConfigDataStore()
  {
    return $this->servingConfigDataStore;
  }
  /**
   * @param string[]
   */
  public function setSolutionTypes($solutionTypes)
  {
    $this->solutionTypes = $solutionTypes;
  }
  /**
   * @return string[]
   */
  public function getSolutionTypes()
  {
    return $this->solutionTypes;
  }
  /**
   * @param GoogleCloudDiscoveryengineV1Schema
   */
  public function setStartingSchema(GoogleCloudDiscoveryengineV1Schema $startingSchema)
  {
    $this->startingSchema = $startingSchema;
  }
  /**
   * @return GoogleCloudDiscoveryengineV1Schema
   */
  public function getStartingSchema()
  {
    return $this->startingSchema;
  }
  /**
   * @param GoogleCloudDiscoveryengineV1WorkspaceConfig
   */
  public function setWorkspaceConfig(GoogleCloudDiscoveryengineV1WorkspaceConfig $workspaceConfig)
  {
    $this->workspaceConfig = $workspaceConfig;
  }
  /**
   * @return GoogleCloudDiscoveryengineV1WorkspaceConfig
   */
  public function getWorkspaceConfig()
  {
    return $this->workspaceConfig;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GoogleCloudDiscoveryengineV1DataStore::class, 'Google_Service_DiscoveryEngine_GoogleCloudDiscoveryengineV1DataStore');
