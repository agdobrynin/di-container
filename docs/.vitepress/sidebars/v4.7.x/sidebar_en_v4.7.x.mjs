export default {
    '/v4.7.x/documentation/': [
        {
            text: 'Manual',
            collapsed: false,
            items: [
                { text: 'Overview', link: '/documentation/index.md' },
                {
                    text: 'Building the dependency container',
                    link: '/documentation/container-builder/index.md',
                    collapsed: true,
                    items: [
                        {
                            text: 'DiContainerBuilder',
                            link: '/documentation/container-builder/index.md',
                            collapsed: false,
                            items: [
                                { text: 'Overview', link: '/documentation/container-builder/index.md' },
                                { text: 'Declaring definitions in configuration files', link: '/documentation/container-builder/methods/load-definitions-from-file.md' },
                                { text: 'Declaration via a collection', link: '/documentation/container-builder/methods/load-definitions-from-collection.md' },
                                { text: 'Registration of container parameters', link: '/documentation/container-builder/methods/load-parameters.md' },
                                { text: 'Registering PHP classes in a container from the file system', link: '/documentation/container-builder/methods/import.md' },
                                { text: 'Container compilation', link: '/documentation/container-builder/methods/compile-to-file.md' },
                                { text: 'Context for configuration files', link: '/documentation/container-builder/methods/configuration-context.md' },
                            ],
                        },
                        { text: 'Definition configuration files', link: '/documentation/container-builder/configuration_files.md' },
                        {
                            text: 'Definition Configurator',
                            link: '/documentation/container-builder/definitions-configurator/index.md',
                            collapsed: true,
                            items: [
                                { text: 'Overview', link: '/documentation/container-builder/definitions-configurator/index.md' },
                                { text: 'Declaring and retrieving container definitions', link: '/documentation/container-builder/definitions-configurator/get_remove.md' },
                                { text: 'Declaring definitions from configuration files', link: '/documentation/container-builder/definitions-configurator/load.md' },
                                { text: 'Managing container parameters', link: '/documentation/container-builder/definitions-configurator/params.md' },
                                { text: 'Context for configuration files', link: '/documentation/container-builder/definitions-configurator/context.md' },
                            ],
                        },
                        { text: 'Dynamic declaration of an object within a container', link: '/documentation/container-builder/set.md' },
                    ]
                },
                {
                    text: 'Configuration for DiContainer',
                    link: '/documentation/container-config/index.md'
                },
                {
                    text: 'Declaration in configuration files',
                    link: '/documentation/php-definition/index.md',
                    collapsed: true,
                    items: [
                        { text: 'Overview', link: '/documentation/php-definition/index.md' },
                        { text: 'diAutowire', link: '/documentation/php-definition/di-autowire.md' },
                        { text: 'diCallable', link: '/documentation/php-definition/di-callable.md' },
                        { text: 'diFactory', link: '/documentation/php-definition/di-factory.md' },
                        { text: 'diGet', link: '/documentation/php-definition/di-get.md' },
                        { text: 'diParameter', link: '/documentation/php-definition/di-parameter.md' },
                        { text: 'diParameterRuntime', link: '/documentation/php-definition/di-parameter-runtime.md' },
                        { text: 'diProxyClosure', link: '/documentation/php-definition/di-proxy-closure.md' },
                        { text: 'diRuntime', link: '/documentation/php-definition/di-runtime.md' },
                        { text: 'diTaggedAs', link: '/documentation/php-definition/di-tagged-as.md' },
                        { text: 'diValue', link: '/documentation/php-definition/di-value.md' },
                    ]
                },
                {
                    text: 'Declaration via attributes',
                    link: '/documentation/attribute-definition/index.md',
                    collapsed: true,
                    items: [
                        { text: 'Overview', link: '/documentation/attribute-definition/index.md' },
                        { text: 'Autowire', link: '/documentation/attribute-definition/autowire.md' },
                        { text: 'AutowireExclude', link: '/documentation/attribute-definition/autowire-exclude.md' },
                        { text: 'DiFactory', link: '/documentation/attribute-definition/di-factory.md' },
                        { text: 'DiRuntime', link: '/documentation/attribute-definition/di-runtime.md' },
                        { text: 'Inject', link: '/documentation/attribute-definition/inject.md' },
                        { text: 'InjectByCallable', link: '/documentation/attribute-definition/inject-by-callable.md' },
                        { text: 'Parameter', link: '/documentation/attribute-definition/parameter.md' },
                        { text: 'ParameterRuntime', link: '/documentation/attribute-definition/parameter-runtime.md' },
                        { text: 'ProxyClosure', link: '/documentation/attribute-definition/proxy-closure.md' },
                        { text: 'Service', link: '/documentation/attribute-definition/service.md' },
                        { text: 'Setup', link: '/documentation/attribute-definition/setup.md' },
                        { text: 'SetupImmutable', link: '/documentation/attribute-definition/setup-immutable.md' },
                        { text: 'SetupPriority', link: '/documentation/attribute-definition/setup-priority.md' },
                        { text: 'Tag', link: '/documentation/attribute-definition/tag.md' },
                        { text: 'TaggedAs', link: '/documentation/attribute-definition/tagged-as.md' },
                    ]
                },
                {
                    text: 'Container method for callable types',
                    link: '/documentation/call/index.md',
                },
                {
                    text: 'Tags',
                    link: '/documentation/tags/index.md',
                    collapsed: true,
                    items: [
                        { text: 'Overview', link: '/documentation/tags/index.md' },
                        { text: 'Declaring a tag in configuration files', link: '/documentation/tags/php-definition-tag.md' },
                        { text: 'Declaring a tag via an attribute', link: '/documentation/tags/attribute-definition-tag.md' },
                        { text: 'PHP interface as a tag name', link: '/documentation/tags/interface-as-tag.md' },
                        { text: 'Removing elements from a collection', link: '/documentation/tags/exclide-from-collection.md' },
                        {
                            text: 'Priority of collection elements',
                            link: '/documentation/tags/collection-priority/index.md',
                            collapsed: true,
                            items: [
                                { text: 'Overview', link: '/documentation/tags/collection-priority/index.md' },
                                { text: 'Tag priority', link: '/documentation/tags/collection-priority/priority-by-tag.md' },
                                { text: 'Tag priority method', link: '/documentation/tags/collection-priority/priority-by-method.md' },
                                { text: 'Default priority method', link: '/documentation/tags/collection-priority/priority-by-method-default.md' },
                            ],
                        },
                        {
                            text: 'Collection element identifiers',
                            link: '/documentation/tags/using-collecton-key/index.md',
                            collapsed: true,
                            items: [
                                { text: 'Overview', link: '/documentation/tags/using-collecton-key/index.md' },
                                { text: 'Element identifier as an integer', link: '/documentation/tags/using-collecton-key/key-as-int.md' },
                                { text: 'Element identifier in tag metadata', link: '/documentation/tags/using-collecton-key/key-in-metadata.md' },
                                { text: 'Collection\'s identifier method', link: '/documentation/tags/using-collecton-key/key-from-method.md' },
                                { text: 'Default "identifier method" for a collection element', link: '/documentation/tags/using-collecton-key/key-from-method-default.md' },
                                { text: 'Intersection of element identifiers in a collection', link: '/documentation/tags/using-collecton-key/keys-equals.md' },
                            ],
                        },
                        { text: 'Naming convention for tags and metadata', link: '/documentation/tags/naming-convention.md' },
                        { text: 'Tag metadata', link: '/documentation/tags/tag-metadata.md' },
                    ],
                },
                {
                    text: 'Container parameters',
                    link: '/documentation/container-parameters/index.md',
                    collapsed: true,
                    items: [
                        { text: 'Overview', link: '/documentation/container-parameters/index.md' },
                        { text: 'Defining container parameters', link: '/documentation/container-parameters/declare_params.md' },
                        { text: 'References to the names of other container parameters', link: '/documentation/container-parameters/expression_in_params.md' },
                        { text: 'Declaring dependency injection via container parameters', link: '/documentation/container-parameters/inject_params.md' },
                        { text: 'Runtime container parameters', link: '/documentation/container-parameters/inject_runtime_params.md' },
                    ],
                },
                {
                    text: 'Declaring an object in a runtime container',
                    link: '/documentation/runtime-definition/index.md',
                },
                {
                    text: 'Using factory methods for dependency injection',
                    link: '/documentation/factories/index.md',
                    collapsed: true,
                    items: [
                        {text: 'Overview', link: '/documentation/factories/index.md'},
                        {text: 'Arguments for a factory method', link: '/documentation/factories/args.md'},
                        {text: 'PHP class declaration', link: '/documentation/factories/declare_class.md'},
                        {text: 'Declaring dependency injection via a factory', link: '/documentation/factories/inject_into_params.md'},
                        {text: 'Actual name of the factory class', link: '/documentation/factories/factory_class_container_id.md'},
                    ],
                },
                {
                    text: 'Container reset',
                    link: '/documentation/container-reset/index.md',
                },
                {
                    text: 'Resetting object state for long-running processes',
                    link: '/documentation/object-resetters/index.md',
                    collapsed: true,
                    items: [
                        {text: 'Overview', link: '/documentation/object-resetters/index.md'},
                        {text: 'Configuration for resetting object state based on container definitions', link: '/documentation/object-resetters/definition_config.md'},
                        {text: 'Configuration of individual object state reset values', link: '/documentation/object-resetters/manual_config.md'},
                        {text: 'Resetting an object\'s state via object reflection', link: '/documentation/object-resetters/reset_vendor_class.md'},
                    ],
                },
                {
                    text: 'Inject lazy objects by the container',
                    link: '/documentation/lazy-injection/index.md',
                    collapsed: true,
                    items: [
                        {text: 'Overview', link: '/documentation/lazy-injection/index.md'},
                        {text: 'Declaring a lazy PHP class in a container', link: '/documentation/lazy-injection/inject_as_lazy_global.md'},
                        {text: 'Declaring the injection of a lazy PHP class into a parameter', link: '/documentation/lazy-injection/inject_lazy_arg.md'},
                    ],
                },
            ]
        }
    ],
    '/v4.7.x/cookbook/': [
        {
            text: 'Cookbook',
            collapsed: false,
            items: [
                { text: 'Overview', link: '/cookbook/index.md' },
                { text: 'Dependency injection via setter methods', link: '/cookbook/autowire-setup.md' },
                {
                    text: 'Dependency injection into a parameter with an interface type',
                    collapsed: true,
                    link: '/cookbook/inject-interface/index.md',
                    items: [
                        {text: 'Overview', link: '/cookbook/inject-interface/index.md'},
                        {text: 'Global declaration of interface implementation', link: '/cookbook/inject-interface/global.md'},
                        {text: 'Injecting a PHP class as an interface implementation for a specific parameter type', link: '/cookbook/inject-interface/arg.md'},
                    ],
                },
                { text: 'Dependency injection into variable-length parameters', link: '/cookbook/inject-variadic-params.md' },
                { text: 'Dependency injection into a union-typed parameter', link: '/cookbook/inject-union-type-params.md' },
                { text: 'Dependency injection for PHP classes that implement third-party interfaces', link: '/cookbook/resolve-vendor-interface.md' },
            ],
        },
    ],
};
