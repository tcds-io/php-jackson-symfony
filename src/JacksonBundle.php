<?php

namespace Tcds\Io\Jackson\Symfony;

use Override;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

class JacksonBundle extends AbstractBundle
{
    private const string CONFIG_DIR = __DIR__ . '/../config';

    #[Override]
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);
    }

    #[Override]
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        parent::loadExtension($config, $container, $builder);

        $fileLocator = new FileLocator(self::CONFIG_DIR);
        $loader = new YamlFileLoader($builder, $fileLocator);

        $loader->load('jackson.yaml');
    }
}
