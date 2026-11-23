export default {
    '/en/documentation/': [
        {
            base: '/en/documentation/',
            text: 'Manual',
            collapsed: false,
            items: [
                { text: 'Overview', link: 'index.md' },
                {
                    base: '/en/documentation/container-builder/',
                    text: 'Building the dependency container',
                    link: 'index.md',
                    collapsed: true,
                    items: [
                        {
                            text: 'DiContainerBuilder',
                            link: 'index.md',
                            collapsed: false,
                            items: [
                                { text: 'Overview', link: 'index.md' },
                                { text: 'Declaring definitions in configuration files', link: 'methods/load-definitions-from-file.md' },
                                { text: 'Declaration via a collection', link: 'methods/load-definitions-from-collection.md' },
                                { text: 'Registration of container parameters', link: 'methods/load-parameters.md' },
                                { text: 'Registering PHP classes in a container from the file system', link: 'methods/import.md' },
                                { text: 'Container compilation', link: 'methods/compile-to-file.md' },
                                { text: 'Context for configuration files', link: 'methods/configuration-context.md' },
                            ],
                        },
                        { text: 'Definition configuration files', link: 'configuration_files.md' },
                        {
                            base: '/en/documentation/container-builder/definitions-configurator/',
                            text: 'Definition Configurator',
                            link: 'index.md',
                            collapsed: true,
                            items: [
                                { text: 'Overview', link: 'index.md' },
                                { text: 'Declaring and retrieving container definitions', link: 'get_remove.md' },
                                { text: 'Declaring definitions from configuration files', link: 'load.md' },
                                { text: 'Managing container parameters', link: 'params.md' },
                                { text: 'Context for configuration files', link: 'context.md' },
                            ],
                        },
                        { text: 'Dynamic declaration of an object within a container', link: 'set.md' },
                    ]
                },
                {
                    text: 'Configuration for DiContainer',
                    link: 'container-config/index.md'
                },
                {
                    base: '/en/documentation/php-definition/',
                    text: 'Declaration in configuration files',
                    link: 'index.md',
                    collapsed: true,
                    items: [
                        { text: 'Overview', link: 'index.md' },
                        { text: 'diAutowire', link: 'di-autowire.md' },
                        { text: 'diCallable', link: 'di-callable.md' },
                        { text: 'diFactory', link: 'di-factory.md' },
                        { text: 'diGet', link: 'di-get.md' },
                        { text: 'diParameter', link: 'di-parameter.md' },
                        { text: 'diParameterRuntime', link: 'di-parameter-runtime.md' },
                        { text: 'diProxyClosure', link: 'di-proxy-closure.md' },
                        { text: 'diRuntime', link: 'di-runtime.md' },
                        { text: 'diTaggedAs', link: 'di-tagged-as.md' },
                        { text: 'diValue', link: 'di-value.md' },
                    ]
                },
                {
                    base: '/en/documentation/attribute-definition/',
                    text: 'Declaration via attributes',
                    link: 'index.md',
                    collapsed: true,
                    items: [
                        { text: 'Overview', link: 'index.md' },
                        { text: 'Autowire', link: 'autowire.md' },
                        { text: 'AutowireExclude', link: 'autowire-exclude.md' },
                        { text: 'DiFactory', link: 'di-factory.md' },
                        { text: 'DiRuntime', link: 'di-runtime.md' },
                        { text: 'Inject', link: 'inject.md' },
                        { text: 'InjectByCallable', link: 'inject-by-callable.md' },
                        { text: 'Parameter', link: 'parameter.md' },
                        { text: 'ParameterRuntime', link: 'parameter-runtime.md' },
                        { text: 'ProxyClosure', link: 'proxy-closure.md' },
                        { text: 'Service', link: 'service.md' },
                        { text: 'Setup', link: 'setup.md' },
                        { text: 'SetupImmutable', link: 'setup-immutable.md' },
                        { text: 'SetupPriority', link: 'setup-priority.md' },
                        { text: 'Tag', link: 'tag.md' },
                        { text: 'TaggedAs', link: 'tagged-as.md' },
                    ]
                },
                {
                    text: 'Container method for callable types',
                    link: 'call/index.md',
                },
                {
                    base: '/en/documentation/tags/',
                    text: 'Tags',
                    link: 'index.md',
                    collapsed: true,
                    items: [
                        { text: 'Overview', link: 'index.md' },
                        { text: 'Declaring a tag in configuration files', link: 'php-definition-tag.md' },
                        { text: 'Declaring a tag via an attribute', link: 'attribute-definition-tag.md' },
                        { text: 'PHP interface as a tag name', link: 'interface-as-tag.md' },
                        { text: 'Removing elements from a collection', link: 'exclide-from-collection.md' },
                        {
                            base: '/en/documentation/tags/collection-priority/',
                            text: 'Priority of collection elements',
                            link: 'index.md',
                            collapsed: true,
                            items: [
                                { text: 'Overview', link: 'index.md' },
                                { text: 'Tag priority', link: 'priority-by-tag.md' },
                                { text: 'Tag priority method', link: 'priority-by-method.md' },
                                { text: 'Default priority method', link: 'priority-by-method-default.md' },
                            ],
                        },
                        {
                            base: '/en/documentation/tags/using-collecton-key/',
                            text: 'Collection element identifiers',
                            link: 'index.md',
                            collapsed: true,
                            items: [
                                { text: 'Overview', link: 'index.md' },
                                { text: 'Element identifier as an integer', link: 'key-as-int.md' },
                                { text: 'Element identifier in tag metadata', link: 'key-in-metadata.md' },
                                { text: 'Collection\'s identifier method', link: 'key-from-method.md' },
                                { text: 'Default "identifier method" for a collection element', link: 'key-from-method-default.md' },
                                { text: 'Intersection of element identifiers in a collection', link: 'keys-equals.md' },
                            ],
                        },
                        { text: 'Naming convention for tags and metadata', link: 'naming-convention.md' },
                        { text: 'Tag metadata', link: 'tag-metadata.md' },
                    ],
                },
                {
                    base: '/en/documentation/container-parameters/',
                    text: 'Container parameters',
                    link: 'index.md',
                    collapsed: true,
                    items: [
                        { text: 'Overview', link: 'index.md' },
                        { text: 'Defining container parameters', link: 'declare_params.md' },
                        { text: 'References to the names of other container parameters', link: 'expression_in_params.md' },
                        { text: 'Declaring dependency injection via container parameters', link: 'inject_params.md' },
                        { text: 'Runtime container parameters', link: 'inject_runtime_params.md' },
                    ],
                },
                {
                    text: 'Declaring an object in a runtime container',
                    link: 'runtime-definition/index.md',
                },
                {
                    base: '/en/documentation/factories/',
                    text: 'Using factory methods for dependency injection',
                    link: 'index.md',
                    collapsed: true,
                    items: [
                        {text: 'Overview', link: 'index.md'},
                        {text: 'Arguments for a factory method', link: 'args.md'},
                        {text: 'PHP class declaration', link: 'declare_class.md'},
                        {text: 'Declaring dependency injection via a factory', link: 'inject_into_params.md'},
                        {text: 'Actual name of the factory class', link: 'factory_class_container_id.md'},
                    ],
                },
                {
                    text: 'Container reset',
                    link: 'container-reset/index.md',
                },
                {
                    base: '/en/documentation/object-resetters/',
                    text: 'Resetting object state for long-running processes',
                    link: 'index.md',
                    collapsed: true,
                    items: [
                        {text: 'Overview', link: 'index.md'},
                        {text: 'Configuration for resetting object state based on container definitions', link: 'definition_config.md'},
                        {text: 'Конфигурация отдельных значений сброса состояния объектов', link: 'manual_config.md'},
                        {text: 'Сброс состояния объекта через рефлексию объекта', link: 'reset_vendor_class.md'},
                    ],
                },
                {
                    base: '/en/documentation/lazy-injection/',
                    text: 'Injection of lazy objects by the container',
                    link: 'index.md',
                    collapsed: true,
                    items: [
                        {text: 'Overview', link: 'index.md'},
                        {text: 'Объявление ленивого PHP-класса в контейнере', link: 'inject_as_lazy_global.md'},
                        {text: 'Объявление внедрения ленивого PHP-класса в параметр', link: 'inject_lazy_arg.md'},
                    ],
                },
            ]
        }
    ],
        '/en/cookbook/': [
        {
            base: '/en/cookbook/',
            text: 'Cookbook',
            collapsed: false,
            items: [
                { text: 'Overview', link: 'index.md' },
                { text: 'Dependency injection via setter methods', link: 'autowire-setup.md' },
                {
                    base: '/en/cookbook/inject-interface/',
                    text: 'Dependency injection into a parameter with an interface type',
                    collapsed: true,
                    link: 'index.md',
                    items: [
                        {text: 'Overview', link: 'index.md'},
                        {text: 'Global declaration of interface implementation', link: 'global.md'},
                        {text: 'Declaration of an insertion into a specific parameter', link: 'arg.md'},
                    ],
                },
                { text: 'Dependency injection into variable-length parameters', link: 'inject-variadic-params.md' },
                { text: 'Dependency injection into a union-typed parameter', link: 'inject-union-type-params.md' },
                { text: 'Dependency injection for PHP classes that implement third-party interfaces', link: 'resolve-vendor-interface.md' },
            ],
        },
    ],
};
