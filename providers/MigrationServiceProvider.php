<?php

/**
 * Isotope eCommerce Migration Tool
 *
 * Copyright (C) 2014 terminal42 gmbh
 *
 * @link       http://isotopeecommerce.org
 * @license    http://opensource.org/licenses/lgpl-3.0.html
 */

namespace Isotope\Migration\Provider;


use Isotope\Migration\Service\ConstructorInjectionService;
use Isotope\Migration\Service\DatabaseVerificationService;
use Isotope\Migration\Service\DbafsService;
use Silex\Application;
use Silex\Provider\DoctrineServiceProvider;
use Silex\Provider\ServiceControllerServiceProvider;
use Silex\Provider\SessionServiceProvider;
use Silex\Provider\TranslationServiceProvider;
use Silex\Provider\TwigServiceProvider;
use Silex\ServiceProviderInterface;
use Silex\Translator;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBag;

class MigrationServiceProvider implements ServiceProviderInterface
{
    /**
     * Version constant for the migration tool
     */
    const VERSION = '1.0.0-dev';


    function register(Application $app)
    {

        $app->register(new TranslationServiceProvider());
        $app['translator'] = $app->share($app->extend('translator', function(Translator $translator) {
            $translator->addResource('array', include(__DIR__.'/../locales/en.php'), 'en');
            $translator->addResource('array', include(__DIR__.'/../locales/de.php'), 'de');

            return $translator;
        }));

        $app->register(new TwigServiceProvider(), array(
            'twig.path' => __DIR__.'/../views',
        ));

        $app->register(new DoctrineServiceProvider());
        $app->register(new ServiceControllerServiceProvider());
        $app->register(new SessionServiceProvider());

        $app['migration.dbcheck'] = $app->share(function() use ($app) {
            return new DatabaseVerificationService($app['db'], $app['translator']);
        });

        $app['migration.dbafs'] = $app->share(function() use ($app) {
            return new DbafsService($app['db']);
        });

        $app['class_factory'] = $app->share(function() use ($app) {
            return new ConstructorInjectionService($app);
        });

        $app['migration.service.classes'] = new \Pimple();

        /** @type \Isotope\Migration\Service\MigrationServiceInterface[] $services */
        $services = array(
            // this order DOES MATTER!!
            '\\Isotope\\Migration\\Service\\AddressBookMigrationService',
            '\\Isotope\\Migration\\Service\\AttributeMigrationService',
            '\\Isotope\\Migration\\Service\\ProductTypeMigrationService',
            '\\Isotope\\Migration\\Service\\ProductDataMigrationService',

            '\\Isotope\\Migration\\Service\\FrontendModuleMigrationService',
            '\\Isotope\\Migration\\Service\\MailTemplateMigrationService',

            // here we don't know (yet) ;-)

            '\\Isotope\\Migration\\Service\\ProductCollectionMigrationService',
            '\\Isotope\\Migration\\Service\\ShopConfigMigrationService',
            '\\Isotope\\Migration\\Service\\RelatedProductMigrationService',
            '\\Isotope\\Migration\\Service\\TranslationMigrationService',
            '\\Isotope\\Migration\\Service\\PaymentMethodMigrationService',
            '\\Isotope\\Migration\\Service\\ShippingMethodMigrationService',
            '\\Isotope\\Migration\\Service\\DownloadMigrationService',
        );

        foreach ($services as $class) {
            $slug = $class::getSlug();
            $app['migration.service.classes'][$slug] = $class;
        }
    }

    function boot(Application $app)
    {
        $app['migration.controller'] = $app['class_factory']->share('\\Isotope\\Migration\\Controller\\MigrationController');
        $app['migration.services'] = new \Pimple();

        foreach ($app['migration.service.classes']->keys() as $slug) {
            $class = $app['migration.service.classes'][$slug];

            $configBag = new AttributeBag('config_' . $slug);
            $configBag->setName('config_' . $slug);
            $app['session']->registerBag($configBag);

            $summaryBag = new AttributeBag('summary_' . $slug);
            $summaryBag->setName('summary_' . $slug);
            $app['session']->registerBag($summaryBag);

            $app['migration.services'][$slug] = $app->share(
                function() use ($app, $slug, $class) {
                    $config = $app['session']->getBag('config_' . $slug);
                    $summary = $app['session']->getBag('summary_' . $slug);
                    return $app['class_factory']->create($class, array(
                        'summary'   => $summary,
                        'config'    => $config
                    ));
                }
            );
        }
    }
}
