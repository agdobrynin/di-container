export default {
    '/v4.8.x/documentation/': [
        {
            text: 'Руководство',
            collapsed: false,
            items: [
                { text: 'Обзор', link: '/documentation/index.md' },
                {
                    text: 'Сборка контейнера зависимостей',
                    link: '/documentation/container-builder/index.md',
                    collapsed: true,
                    items: [
                        {
                            text: 'DiContainerBuilder',
                            link: 'index.md',
                            collapsed: false,
                            items: [
                                { text: 'Обзор', link: '/documentation/container-builder/index.md' },
                                { text: 'Объявление из файлов конфигураций', link: '/documentation/container-builder/methods/load-definitions-from-file.md' },
                                { text: 'Объявление через коллекцию', link: '/documentation/container-builder/methods/load-definitions-from-collection.md' },
                                { text: 'Регистрация параметров контейнера', link: '/documentation/container-builder/methods/load-parameters.md' },
                                { text: 'Объявление через сканирование файловой системы', link: '/documentation/container-builder/methods/import.md' },
                                { text: 'Компиляция контейнера', link: '/documentation/container-builder/methods/compile-to-file.md' },
                                { text: 'Контекст для файлов конфигураций', link: '/documentation/container-builder/methods/configuration-context.md' },
                            ],
                        },
                        { text: 'Файлы конфигураций', link: '/documentation/container-builder/configuration_files.md' },
                        {
                            text: 'Конфигуратор определений',
                            link: '/documentation/container-builder/definitions-configurator/index.md',
                            collapsed: true,
                            items: [
                                { text: 'Обзор', link: '/documentation/container-builder/definitions-configurator/index.md' },
                                { text: 'Объявление и получение определений', link: '/documentation/container-builder/definitions-configurator/get_remove.md' },
                                { text: 'Объявление из файлов конфигураций', link: '/documentation/container-builder/definitions-configurator/load.md' },
                                { text: 'Управление параметрами контейнера', link: '/documentation/container-builder/definitions-configurator/params.md' },
                                { text: 'Контекст для файлов конфигураций', link: '/documentation/container-builder/definitions-configurator/context.md' },
                            ],
                        },
                        { text: 'Динамическое объявление объекта в контейнере', link: '/documentation/container-builder/set.md' },
                    ]
                },
                {
                    text: 'Конфигурация для DiContainer',
                    link: '/documentation/container-config/index.md'
                },
                {
                    text: 'Объявление в файлах конфигураций',
                    link: '/documentation/php-definition/index.md',
                    collapsed: true,
                    items: [
                        { text: 'Обзор', link: '/documentation/php-definition/index.md' },
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
                    text: 'Объявление через атрибуты',
                    link: '/documentation/attribute-definition/index.md',
                    collapsed: true,
                    items: [
                        { text: 'Обзор', link: '/documentation/attribute-definition/index.md' },
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
                    text: 'Метод контейнера для вызываемых типов',
                    link: '/documentation/call/index.md',
                },
                {
                    text: 'Теги',
                    link: '/documentation/tags/index.md',
                    collapsed: true,
                    items: [
                        { text: 'Обзор', link: '/documentation/tags/index.md' },
                        { text: 'Объявление тега в файлах конфигураций', link: '/documentation/tags/php-definition-tag.md' },
                        { text: 'Объявление тега через атрибут', link: '/documentation/tags/attribute-definition-tag.md' },
                        { text: 'PHP интерфейс как имя тега', link: '/documentation/tags/interface-as-tag.md' },
                        { text: 'Исключение элементов из коллекции', link: '/documentation/tags/exclide-from-collection.md' },
                        {
                            text: 'Приоритет элементов коллекции',
                            link: '/documentation/tags/collection-priority/index.md',
                            collapsed: true,
                            items: [
                                { text: 'Обзор', link: '/documentation/tags/collection-priority/index.md' },
                                { text: 'Приоритет тега', link: '/documentation/tags/collection-priority/priority-by-tag.md' },
                                { text: '«Метод-приоритета» тега', link: '/documentation/tags/collection-priority/priority-by-method.md' },
                                { text: '«Метод-приоритета» по умолчанию', link: '/documentation/tags/collection-priority/priority-by-method-default.md' },
                            ],
                        },
                        {
                            text: 'Идентификаторы элементов коллекции',
                            link: '/documentation/tags/using-collecton-key/index.md',
                            collapsed: true,
                            items: [
                                { text: 'Обзор', link: '/documentation/tags/using-collecton-key/index.md' },
                                { text: 'Идентификатор элемента как целое число', link: '/documentation/tags/using-collecton-key/key-as-int.md' },
                                { text: 'Идентификатор элемента в метаданных тега', link: '/documentation/tags/using-collecton-key/key-in-metadata.md' },
                                { text: '«Метод идентификатора» элемента коллекции', link: '/documentation/tags/using-collecton-key/key-from-method.md' },
                                { text: '«Метод идентификатора» элемента коллекции по умолчанию', link: '/documentation/tags/using-collecton-key/key-from-method-default.md' },
                                { text: 'Совпадение идентификаторов элементов в коллекции', link: '/documentation/tags/using-collecton-key/keys-equals.md' },
                            ],
                        },
                        { text: 'Соглашение по именованию тегов и мета-данных', link: '/documentation/tags/naming-convention.md' },
                        { text: 'Метаданные тега', link: '/documentation/tags/tag-metadata.md' },
                    ],
                },
                {
                    text: 'Параметры контейнера',
                    link: '/documentation/container-parameters/index.md',
                    collapsed: true,
                    items: [
                        { text: 'Обзор', link: '/documentation/container-parameters/index.md' },
                        { text: 'Объявление параметров контейнера', link: '/documentation/container-parameters/declare_params.md' },
                        { text: 'Ссылки на имена параметров контейнера', link: '/documentation/container-parameters/expression_in_params.md' },
                        { text: 'Внедрение параметров контейнера', link: '/documentation/container-parameters/inject_params.md' },
                        { text: 'Параметры контейнера времени выполнения', link: '/documentation/container-parameters/inject_runtime_params.md' },
                    ],
                },
                {
                    text: 'Объявление объекта в рантайм контейнере',
                    link: '/documentation/runtime-definition/index.md',
                },
                {
                    text: 'Внедрение зависимостей через фабрику',
                    link: '/documentation/factories/index.md',
                    collapsed: true,
                    items: [
                        {text: 'Обзор', link: '/documentation/factories/index.md'},
                        {text: 'Аргументы для метода фабрики', link: '/documentation/factories/args.md'},
                        {text: 'Объявление PHP-класса', link: '/documentation/factories/declare_class.md'},
                        {text: 'Объявление внедрения зависимости', link: '/documentation/factories/inject_into_params.md'},
                        {text: 'Фактическое имя класса фабрики', link: '/documentation/factories/factory_class_container_id.md'},
                    ],
                },
                {
                    text: 'Сброс контейнера',
                    link: '/documentation/container-reset/index.md',
                },
                {
                    text: 'Сброс состояния объектов в долго-живущих процессах',
                    link: '/documentation/object-resetters/index.md',
                    collapsed: true,
                    items: [
                        {text: 'Обзор', link: '/documentation/object-resetters/index.md'},
                        {text: 'Конфигурация сброса состояния объектов из определений контейнера', link: '/documentation/object-resetters/definition_config.md'},
                        {text: 'Конфигурация отдельных значений сброса состояния объектов', link: '/documentation/object-resetters/manual_config.md'},
                        {text: 'Сброс состояния объекта через рефлексию объекта', link: '/documentation/object-resetters/reset_vendor_class.md'},
                    ],
                },
                {
                    text: 'Внедрение «ленивых» объектов контейнером',
                    link: '/documentation/lazy-injection/index.md',
                    collapsed: true,
                    items: [
                        {text: 'Обзор', link: '/documentation/lazy-injection/index.md'},
                        {text: 'Объявление ленивого PHP-класса в контейнере', link: '/documentation/lazy-injection/inject_as_lazy_global.md'},
                        {text: 'Объявление внедрения ленивого PHP-класса в параметр', link: '/documentation/lazy-injection/inject_lazy_arg.md'},
                    ],
                },
            ]
        }
    ],
    '/v4.8.x/cookbook/': [
        {
            text: 'Рецепты',
            collapsed: false,
            items: [
                { text: 'Обзор', link: '/cookbook/index.md' },
                { text: 'Внедрение зависимостей через сеттер-методы', link: '/cookbook/autowire-setup.md' },
                {
                    text: 'Внедрение зависимости в параметр с типом интерфейс',
                    collapsed: true,
                    link: '/cookbook/inject-interface/index.md',
                    items: [
                        {text: 'Обзор', link: '/cookbook/inject-interface/index.md'},
                        {text: 'Глобальное объявление реализации интерфейса', link: '/cookbook/inject-interface/global.md'},
                        {text: 'Объявление внедрения в конкретный параметр', link: '/cookbook/inject-interface/arg.md'},
                    ],
                },
                { text: 'Внедрение зависимостей в параметры переменной длины', link: '/cookbook/inject-variadic-params.md' },
                { text: 'Внедрение зависимостей для параметров объединенного типа', link: '/cookbook/inject-union-type-params.md' },
                { text: 'Внедрение зависимостей PHP-классов реализующих интерфейс сторонних производителей', link: '/cookbook/resolve-vendor-interface.md' },
            ],
        },
    ],
};
