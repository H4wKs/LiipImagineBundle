<?php

/*
 * This file is part of the `liip/LiipImagineBundle` project.
 *
 * (c) https://github.com/liip/LiipImagineBundle/graphs/contributors
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Liip\ImagineBundle\Service;

use Liip\ImagineBundle\Exception\Imagine\Filter\NonExistingFilterException;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Liip\ImagineBundle\Imagine\Data\DataManager;
use Liip\ImagineBundle\Imagine\Filter\FilterManager;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class FilterService
{
    /**
     * @var DataManager
     */
    private $dataManager;

    /**
     * @var FilterManager
     */
    private $filterManager;

    /**
     * @var CacheManager
     */
    private $cacheManager;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * FilterService constructor.
     * @param DataManager $dataManager
     * @param FilterManager $filterManager
     * @param CacheManager $cacheManager
     * @param LoggerInterface $logger
     */
    public function __construct(
        DataManager $dataManager,
        FilterManager $filterManager,
        CacheManager $cacheManager,
        LoggerInterface $logger = null
    ) {
        $this->dataManager = $dataManager;
        $this->filterManager = $filterManager;
        $this->cacheManager = $cacheManager;
        $this->logger = $logger ? $logger : new NullLogger();
    }

    /**
     * @param string $path
     * @param string $filter
     * @param string $resolver
     * @throws \Liip\ImagineBundle\Exception\Binary\Loader\NotLoadableException
     * @throws \Liip\ImagineBundle\Exception\Imagine\Filter\NonExistingFilterException
     */
    public function createFilteredImage($path, $filter, $resolver = null)
    {
        if ($this->cacheManager->isStored($path, $filter, $resolver)) {
            return;
        }

        $filteredBinary = $this->createFilteredBinary(
            $path,
            $filter
        );

        $this->cacheManager->store(
            $filteredBinary,
            $path,
            $filter,
            $resolver
        );
    }

    /**
     * @param string $path
     * @param string $filter
     * @param string $resolver
     * @param array $runtimeFilters
     * @throws \Liip\ImagineBundle\Exception\Binary\Loader\NotLoadableException
     * @throws \Liip\ImagineBundle\Exception\Imagine\Filter\NonExistingFilterException
     */
    public function createFilteredImageWithRuntimeFilters($path, $filter, array $runtimeFilters = array(), $resolver = null)
    {
        $runtimePath = $this->cacheManager->getRuntimePath($path, $runtimeFilters);
        if ($this->cacheManager->isStored($runtimePath, $filter, $resolver)) {
            return;
        }

        $filteredBinary = $this->createFilteredBinary(
            $path,
            $filter,
            $runtimeFilters
        );

        $this->cacheManager->store(
            $filteredBinary,
            $runtimePath,
            $filter,
            $resolver
        );
    }

    /**
     * @param string $path
     * @param string $filter
     * @param string $resolver
     * @return string
     */
    public function getUrlOfFilteredImage($path, $filter, $resolver = null)
    {
        return $this->cacheManager->resolve($path, $filter, $resolver);
    }

    /**
     * @param string $path
     * @param string $filter
     * @param string $resolver
     * @param array $runtimeFilters
     * @return string
     */
    public function getUrlOfFilteredImageWithRuntimeFilters($path, $filter, array $runtimeFilters = array(), $resolver = null)
    {
        $runtimePath = $this->cacheManager->getRuntimePath($path, $runtimeFilters);
        return $this->cacheManager->resolve($runtimePath, $filter, $resolver);
    }

    /**
     * @param string $path
     * @param string $filter
     * @param array|null $runtimeFilters
     * @return \Liip\ImagineBundle\Binary\BinaryInterface
     * @throws \Liip\ImagineBundle\Exception\Imagine\Filter\NonExistingFilterException
     */
    private function createFilteredBinary($path, $filter, array $runtimeFilters = array())
    {
        $binary = $this->dataManager->find($filter, $path);

        try {
            return $this->filterManager->applyFilter($binary, $filter, array(
                'filters' => $runtimeFilters,
            ));
        } catch (NonExistingFilterException $e) {
            $message = sprintf('Could not locate filter "%s" for path "%s". Message was "%s"', $filter, $path, $e->getMessage());

            $this->logger->debug($message);

            throw $e;
        }
    }
}
