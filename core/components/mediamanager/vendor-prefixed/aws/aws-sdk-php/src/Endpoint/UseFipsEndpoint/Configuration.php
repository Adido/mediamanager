<?php
namespace Sterc\MediaManager\Vendor\Aws\Endpoint\UseFipsEndpoint;

use Sterc\MediaManager\Vendor\Aws;
use Sterc\MediaManager\Vendor\Aws\Endpoint\UseFipsEndpoint\Exception\ConfigurationException;

class Configuration implements ConfigurationInterface
{
    private $useFipsEndpoint;

    public function __construct($useFipsEndpoint)
    {
        $this->useFipsEndpoint = \Sterc\MediaManager\Vendor\Aws\boolean_value($useFipsEndpoint);
        if (is_null($this->useFipsEndpoint)) {
            throw new ConfigurationException("'use_fips_endpoint' config option"
                . " must be a boolean value.");
        }
    }

    /**
     * {@inheritdoc}
     */
    public function isUseFipsEndpoint()
    {
        return $this->useFipsEndpoint;
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        return [
            'use_fips_endpoint' => $this->isUseFipsEndpoint(),
        ];
    }
}
