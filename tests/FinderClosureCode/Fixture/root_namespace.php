<?php

return [
    /** static fn (\A $a, \B\C $c, ?bool $isParsed = null) => $a->doMake($isParsed, '', c: $c) */
    'services.roots' => static fn (A $a, B\C $c, ?bool $isParsed = null) => $a->doMake($isParsed, __NAMESPACE__, c: $c),

    /**
     * static function (
     *     \App\Environment $environment,
     *     \Psr\Log\LoggerInterface $logger,
     * ) {
     *     $tempDir = $environment->getTempDirectory() . \DIRECTORY_SEPARATOR . 'cache';
     *     $cacheInterface = new \Symfony\Component\Cache\Adapter\FilesystemAdapter(
     *         directory: $tempDir
     *     );
     *
     *     $cacheInterface->setLogger($logger);
     *     return $cacheInterface;
     * }
     */
    'services.roots.from_proj' => static function (
        Tests\_var\cache\Environment $environment,
        Psr\Log\LoggerInterface      $logger,
    ) {
        $tempDir = $environment->getTempDirectory() . DIRECTORY_SEPARATOR . 'cache';
        $cacheInterface = new Symfony\Component\Cache\Adapter\FilesystemAdapter(
            directory: $tempDir
        );

        $cacheInterface->setLogger($logger);
        return $cacheInterface;
    }
];
