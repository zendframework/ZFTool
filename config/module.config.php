<?php
return array(
    'ZFTool' => array(
        'disableUsage' => false,    // set to true to disable showing available ZFTool commands in Console.
    ),

    // -----=-----=-----=-----=-----=-----=-----=-----=-----=-----=-----=-----=-----=-----=-----=-----=

    'service_manager' => array(
        'factories' => array(
            'translator' => 'Zend\I18n\Translator\TranslatorServiceFactory',
        ),
    ),

    'controllers' => array(
        'invokables' => array(
            'ZFTool\Controller\Info'     => 'ZFTool\Controller\InfoController',
            'ZFTool\Controller\Module'   => 'ZFTool\Controller\ModuleController',
            'ZFTool\Controller\Classmap' => 'ZFTool\Controller\ClassmapController',
            'ZFTool\Controller\Create'   => 'ZFTool\Controller\CreateController',
            'ZFTool\Controller\Install'  => 'ZFTool\Controller\InstallController',
        ),
    ),

    'console' => array(
        'router' => array(
            'routes' => array(
                'zftool-version' => array(
                    'options' => array(
                        'route'    => 'version',
                        'defaults' => array(
                            'controller' => 'ZFTool\Controller\Info',
                            'action'     => 'version',
                        ),
                    ),
                ),
                'zftool-version2' => array(
                    'options' => array(
                        'route'    => '--version',
                        'defaults' => array(
                            'controller' => 'ZFTool\Controller\Info',
                            'action'     => 'version',
                        ),
                    ),
                ),
                'zftool-config-list' => array(
                    'options' => array(
                        'route'    => 'config [list]',
                        'defaults' => array(
                            'controller' => 'ZFTool\Controller\Info',
                            'action'     => 'config',
                        ),
                    ),
                ),
                'zftool-classmap-generate' => array(
                    'options' => array(
                        'route'    => 'classmap generate <directory> [<destination>] [--append|-a] [--overwrite|-w]',
                        'defaults' => array(
                            'controller' => 'ZFTool\Controller\Classmap',
                            'action'     => 'generate',
                        ),
                    ),
                ),
                'zftool-modules-list' => array(
                    'options' => array(
                        'route'    => 'modules [list]',
                        'defaults' => array(
                            'controller' => 'ZFTool\Controller\Module',
                            'action'     => 'list',
                        ),
                    ),
                ),
                'zftool-create-project' => array(
                    'options' => array(
                        'route'    => 'create project <path>',
                        'defaults' => array(
                            'controller' => 'ZFTool\Controller\Create',
                            'action'     => 'project',
                        ),
                    ),
                ),
                'zftool-create-module' => array(
                    'options' => array(
                        'route'    => 'create module <name> [<path>]',
                        'defaults' => array(
                            'controller' => 'ZFTool\Controller\Create',
                            'action'     => 'module',
                        ),
                    ),
                ),
                'zftool-install-zf' => array(
                    'options' => array(
                        'route'    => 'install zf <path> [<version>]',
                        'defaults' => array(
                            'controller' => 'ZFTool\Controller\Install',
                            'action'     => 'zf',
                        ),
                    ),
                ),
            ),
        ),
    ),

);
