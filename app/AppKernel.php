<?php

use Symfony\Component\HttpKernel\Bundle\BundleInterface;

class AppKernel extends Kernel implements CompilerPassInterface {
    /**
     * Returns an array of bundles to register.
     *
     * @return iterable|BundleInterface[] An iterable of bundle instances
     */
    public function registerBundles() {
        $bundles = [
            //...
            new ElasticSearchBundle\ElasticSearchBundle(),
            new KontentAiBundle\KontentAiBundle()
        ];

        return $bundles;
    }
}