<?php

declare(strict_types=1);

return [
    'default' => 'default',
    'documentations' => [
        'default' => [
            'api' => [
                'title' => 'Credit Consultation API',
                'version' => '1.0.0',
                'description' => 'API para consulta de ofertas de crédito integrada com múltiplas instituições financeiras',
                'contact' => [
                    'name' => 'GoSat API Support',
                    'email' => 'marcusmazzon@gmail.com',
                ],
                'license' => [
                    'name' => 'MIT',
                ],
            ],

            'routes' => [
                /*
                 * Route for accessing api documentation interface
                 */
                'api' => '/api/docs',

                /*
                 * Route for accessing parsed swagger annotations.
                 */
                'docs' => '/api/docs.json',

                /*
                 * Route for Oauth2 authentication callback.
                 */
                'oauth2_callback' => '/api/oauth2-callback',

                /*
                 * Middleware allows to prevent unexpected access to API documentation
                 */
                'middleware' => [
                    'api' => [],
                    'asset' => [],
                    'docs' => [],
                    'oauth2_callback' => [],
                ],

                /*
                 * Route Group options
                 */
                'group_options' => [],
            ],

            'paths' => [
                /*
                 * Absolute path to location where parsed annotations will be stored
                 */
                'docs' => storage_path('api-docs'),

                /*
                 * Absolute path to directory where to export views
                 */
                'views' => base_path('resources/views/vendor/swagger'),

                /*
                 * Edit to set the api's base path
                 */
                'base' => env('SWAGGER_BASE_PATH', null),

                /*
                 * Edit to set path where swagger ui assets should be stored
                 */
                'swagger_ui_assets_path' => env('SWAGGER_UI_ASSETS_PATH', 'vendor/swagger-api/swagger-ui/dist/'),

                /*
                 * Absolute path to directories that should be exclude from scanning
                 * @deprecated Please use `scanOptions.exclude`
                 * `scanOptions.exclude` overwrites this
                 */
                'excludes' => [],
            ],

            'scanOptions' => [
                /*
                 * analyser: defaults to \OpenApi\StaticAnalyser .
                 *
                 * @see \OpenApi\scan
                 */
                'analyser' => null,

                /*
                 * analysis: defaults to a new \OpenApi\Analysis .
                 *
                 * @see \OpenApi\scan
                 */
                'analysis' => null,

                /*
                 * Custom query path processors classes.
                 *
                 * @see \OpenApi\scan
                 */
                'processors' => [
                    // @see \OpenApi\Processors\AugmentSchemas::class,
                    // @see \OpenApi\Processors\AugmentProperties::class,
                    // @see \OpenApi\Processors\AugmentParameters::class,
                    // @see \OpenApi\Processors\AugmentRefs::class,
                    // @see \OpenApi\Processors\MergeIntoOpenApi::class,
                    // @see \OpenApi\Processors\MergeIntoComponents::class,
                    // @see \OpenApi\Processors\ExpandClasses::class,
                    // @see \OpenApi\Processors\AugmentRequestBody::class,
                ],

                /*
                 * Pattern(s) of directories to scan
                 */
                'pattern' => null,

                /*
                 * Paths or patterns to exclude when scanning for annotations
                 *
                 * @see \OpenApi\scan
                 */
                'exclude' => [
                    'tests/*',
                    'database/*',
                    'storage/*',
                    'vendor/*',
                ],

                /*
                 * openapi: defaults to OpenApi\scan
                 *
                 * @see \OpenApi\scan
                 */
                'open_api_spec_version' => env('SWAGGER_VERSION', '3.0.0'),

                /*
                 * Custom default definitions. Usually these are models
                 *
                 * @see https://swagger.io/docs/specification/components/
                 */
                'default_processors_configuration' => [
                    // Enables loading model examples from database
                    // \OpenApi\Processors\AugmentSchemas::class => [
                    //     'augmentOperationId' => false,
                    // ],
                ],
            ],

            /*
             * API security definitions. Will be generated into documentation file.
             */
            'securityDefinitions' => [
                'securitySchemes' => [
                    /*
                     * Examples of Security schemes
                     */
                    /*
                    'api_key_security_example' => [ // Unique name of security
                        'type' => 'apiKey', // The type of the security scheme. Valid values are "basic", "apiKey" or "oauth2".
                        'description' => 'A short description for security scheme',
                        'name' => 'api_key', // The name of the header or query parameter to be used.
                        'in' => 'header', // The location of the API key. Valid values are "query" or "header".
                    ],
                    'oauth2_security_example' => [ // Unique name of security
                        'type' => 'oauth2', // The type of the security scheme. Valid values are "basic", "apiKey" or "oauth2".
                        'description' => 'A short description for oauth2 security scheme.',
                        'flow' => 'implicit', // The flow used by the OAuth2 security scheme. Valid values are "implicit", "password", "application" or "accessCode".
                        'authorizationUrl' => 'http://example.com/auth', // The authorization URL to be used for (implicit/accessCode)
                        //'tokenUrl' => 'http://example.com/auth' // The authorization URL to be used for (password/application/accessCode)
                        'scopes' => [
                            'read:projects' => 'read your projects',
                            'write:projects' => 'modify projects in your account',
                        ]
                    ],
                    */

                    /* Open API 3.0 support
                    'passport' => [ // Unique name of security
                        'type' => 'oauth2', // The type of the security scheme. Valid values are "basic", "apiKey" or "oauth2".
                        'description' => 'Laravel passport oauth2 security.',
                        'in' => 'header',
                        'scheme' => 'https',
                        'flows' => [
                            "password" => [
                                "authorizationUrl" => config('app.url') . '/oauth/authorize',
                                "tokenUrl" => config('app.url') . '/oauth/token',
                                "refreshUrl" => config('app.url') . '/token/refresh',
                                "scopes" => []
                            ],
                        ],
                    ],
                    'sanctum' => [ // Unique name of security
                        'type' => 'apiKey', // Valid values are "basic", "apiKey" or "oauth2".
                        'description' => 'Laravel Sanctum token authentication',
                        'name' => 'Authorization',
                        'in' => 'header',
                    ],
                    */
                ],
                'security' => [
                    /*
                     * Examples of Securities
                     */
                    [
                        /*
                        'oauth2_security_example' => [
                            'read',
                            'write'
                        ],

                        'passport' => []
                        */
                    ],
                ],
            ],

            /*
             * Set this to `true` in development mode so that docs would be regenerated on each request
             * Set this to `false` to disable swagger generation on production
             */
            'generate_always' => env('SWAGGER_GENERATE_ALWAYS', false),

            /*
             * Set this to `true` to make a deeper search for initializers.
             */
            'generate_yaml_copy' => env('SWAGGER_GENERATE_YAML_COPY', false),

            /*
             * Proxy settings
             */
            'proxy' => false,

            /*
             * Additional route information for swagger generation
             */
            'additional_config_url' => null,
            'operations_sort' => env('SWAGGER_OPERATIONS_SORT', null),
            'validator_url' => null,

            /*
             * Constants which can be used in annotations
             */
            'constants' => [
                'SWAGGER_LUME_CONST_HOST' => env('SWAGGER_LUME_CONST_HOST', 'http://my-default-host.com'),
            ],
        ],
    ],
    'defaults' => [
        'routes' => [
            /*
             * Route for accessing api documentation interface
             */
            'docs' => '/docs',
            /*
             * Route for accessing parsed swagger annotations.
             */
            'api' => '/docs/api',
            /*
             * Route for Oauth2 authentication callback.
             */
            'oauth2_callback' => '/api/oauth2-callback',
            /*
             * Route for serving assets
             */
            'assets' => '/docs/asset',
            /*
             * Middleware allows to prevent unexpected access to API documentation
             */
            'middleware' => [
                'docs' => [],
                'api' => [],
                'assets' => [],
                'oauth2_callback' => [],
            ],
        ],

        'paths' => [
            /*
             * Path to the swagger annotations base directory
             */
            'annotations' => [
                base_path('app'),
            ],

            /*
             * Path to the swagger docs file(s)
             */
            'docs' => storage_path('api-docs'),

            /*
             * Path for swagger ui assets, defaults to: 'vendor/swagger-api/swagger-ui/dist/'
             */
            'assets' => null,

            /*
             * Absolute path to directory where to export views
             */
            'views' => base_path('resources/views/vendor/swagger'),

            /*
             * Path delimiter to use for the router
             */
            'base' => env('SWAGGER_BASE_PATH', null),
        ],

        'scanOptions' => [
            'exclude' => [
                //
            ],
        ],

        /*
         * API security definitions. Will be generated into documentation file.
         */
        'security' => [
            /*
             * Examples of Securities
             */
            [
                /*
                'oauth2_security_example' => [
                    'read',
                    'write'
                ],
                'passport' => []
                */
            ],
        ],

        /*
         * Set this to `true` in development mode so that docs would be regenerated on each request
         * Set this to `false` to disable swagger generation on production
         */
        'generate_always' => env('SWAGGER_GENERATE_ALWAYS', false),
        /*
         * Set this to `true` to make a deeper search for initializers.
         */
        'generate_yaml_copy' => env('SWAGGER_GENERATE_YAML_COPY', false),

        /*
         * Edit to trust the proxy's ip address - needed for AWS Load Balancer
         * string[]
         */
        'proxy' => false,

        /*
         * Configs plugin allows to fetch external configs instead of passing them to SwaggerUIBundle.
         * See more at: https://petstore.swagger.io/?url=https://petstore3.swagger.io/api/v3/openapi.json#/
         */
        'additional_config_url' => null,

        /*
         * Apply a sort to the operation list of each API. It can be 'alpha' (sort by paths alphanumerically),
         * 'method' (sort by HTTP method).
         * Default is the order returned by the server unchanged.
         */
        'operations_sort' => env('SWAGGER_OPERATIONS_SORT', null),

        /*
         * Pass the validatorUrl parameter to SwaggerUi init on the JS side.
         * A null value here disables validation.
         */
        'validator_url' => null,

        /*
         * Swagger UI configuration parameters
         */
        'ui' => [
            'display' => [
                /*
                 * Controls the default expansion setting for the operations and tags. It can be :
                 * 'list' (expands only the tags),
                 * 'full' (expands the tags and operations),
                 * 'none' (expands nothing).
                 */
                'doc_expansion' => env('SWAGGER_UI_DOC_EXPANSION', 'none'),

                /*
                 * If set, enables filtering. The top bar will show an edit box that
                 * you can use to filter the tagged operations that are shown. Can be
                 * Boolean to enable or disable, or a string, in which case filtering
                 * will be enabled using that string as the filter expression. Filtering
                 * is case sensitive matching the filter expression anywhere inside
                 * the tag.
                 */
                'filter' => env('SWAGGER_UI_FILTERS', true),
            ],

            'authorization' => [
                /*
                 * If set to true, it persists authorization data and it would not be lost on refreshing the browser
                 */
                'persist_authorization' => env('SWAGGER_UI_PERSIST_AUTHORIZATION', false),
            ],
        ],

        /*
         * Constants which can be used in annotations
         */
        'constants' => [
            'SWAGGER_LUME_CONST_HOST' => env('SWAGGER_LUME_CONST_HOST', 'http://my-default-host.com'),
        ],
    ],
];
