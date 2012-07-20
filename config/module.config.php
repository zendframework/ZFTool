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
            'ZFTool\Controller\Index' => 'ZFTool\Controller\InfoController'
        ),
    ),

    'console' => array(
        'router' => array(
            'routes' => array(
                'version' => array(
                    'options' => array(
                        'route'    => 'version',
                        'defaults' => array(
                            'controller' => 'ZFTool\Controller\Index',
                            'action'     => 'version',
                        ),
                    ),
                ),
            ),
        ),
    ),

);
