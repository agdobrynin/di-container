export default {
    '/documentation/': [
        {
            base: '/documentation/',
            text: 'Руководство',
            collapsed: false,
            items: [
                { text: 'Обзор', link: 'index.md' },
                {
                    base: '/documentation/container-builder/',
                    text: 'Сборка контейнера зависимостей',
                    link: 'index.md',
                    collapsed: true,
                    items: [
                        {
                            text: 'DiContainerBuilder',
                            link: 'index.md',
                            collapsed: false,
                            items: [
                                { text: 'Обзор', link: 'index.md' },
                                { text: 'Объявление из файлов конфигураций', link: 'methods/load-definitions-from-file.md' },
                                { text: 'Объявление через коллекцию', link: 'methods/load-definitions-from-collection.md' },
                                { text: 'Регистрация параметров контейнера', link: 'methods/load-parameters.md' },
                                { text: 'Объявление через сканирование файловой системы', link: 'methods/import.md' },
                                { text: 'Компиляция контейнера', link: 'methods/compile-to-file.md' },
                                { text: 'Контекст для файлов конфигураций', link: 'methods/configuration-context.md' },
                            ],
                        },
                        { text: 'Файлы конфигураций', link: 'configuration_files.md' },
                        {
                            base: '/documentation/container-builder/definitions-configurator/',
                            text: 'Конфигуратор определений',
                            link: 'index.md',
                            collapsed: true,
                            items: [
                                { text: 'Обзор', link: 'index.md' },
                                { text: 'Объявление и получение определений', link: 'get_remove.md' },
                                { text: 'Объявление из файлов конфигураций', link: 'load.md' },
                                { text: 'Управление параметрами контейнера', link: 'params.md' },
                                { text: 'Контекст для файлов конфигураций', link: 'context.md' },
                            ],
                        },
                        { text: 'Динамическое объявление объекта в контейнере', link: 'set.md' },
                    ]
                },
                {
                    text: 'Конфигурация для DiContainer',
                    link: 'container-config/index.md'
                },
                {
                    base: '/documentation/php-definition/',
                    text: 'Объявление в файлах конфигураций',
                    link: 'index.md',
                    collapsed: true,
                    items: [
                        { text: 'Обзор', link: 'index.md' },
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
                    base: '/documentation/attribute-definition/',
                    text: 'Объявление через атрибуты',
                    link: 'index.md',
                    collapsed: true,
                    items: [
                        { text: 'Обзор', link: 'index.md' },
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
                    text: 'Метод контейнера для вызываемых типов',
                    link: 'call/index.md',
                },
                {
                    base: '/documentation/tags/',
                    text: 'Теги',
                    link: 'index.md',
                    collapsed: true,
                    items: [
                        { text: 'Обзор', link: 'index.md' },
                        { text: 'Объявление тега в файлах конфигураций', link: 'php-definition-tag.md' },
                        { text: 'Объявление тега через атрибут', link: 'attribute-definition-tag.md' },
                        { text: 'PHP интерфейс как имя тега', link: 'interface-as-tag.md' },
                        { text: 'Исключение элементов из коллекции', link: 'exclide-from-collection.md' },
                        {
                            base: '/documentation/tags/collection-priority/',
                            text: 'Приоритет элементов коллекции',
                            link: 'index.md',
                            collapsed: true,
                            items: [
                                { text: 'Обзор', link: 'index.md' },
                                { text: 'Приоритет тега', link: 'priority-by-tag.md' },
                                { text: '«Метод-приоритета» тега', link: 'priority-by-method.md' },
                                { text: '«Метод-приоритета» по умолчанию', link: 'priority-by-method-default.md' },
                            ],
                        },
                        {
                            base: '/documentation/tags/using-collecton-key/',
                            text: 'Идентификаторы элементов коллекции',
                            link: 'index.md',
                            collapsed: true,
                            items: [
                                { text: 'Обзор', link: 'index.md' },
                                { text: 'Идентификатор элемента как целое число', link: 'key-as-int.md' },
                                { text: 'Идентификатор элемента в метаданных тега', link: 'key-in-metadata.md' },
                                { text: '«Метод идентификатора» элемента коллекции', link: 'key-from-method.md' },
                                { text: '«Метод идентификатора» элемента коллекции по умолчанию', link: 'key-from-method-default.md' },
                                { text: 'Совпадение идентификаторов элементов в коллекции', link: 'keys-equals.md' },
                            ],
                        },
                        { text: 'Соглашение по именованию тегов и мета-данных', link: 'naming-convention.md' },
                        { text: 'Метаданные тега', link: 'tag-metadata.md' },
                    ],
                },
                {
                    base: '/documentation/container-parameters/',
                    text: 'Параметры контейнера',
                    link: 'index.md',
                    collapsed: true,
                    items: [
                        { text: 'Обзор', link: 'index.md' },
                        { text: 'Объявление параметров контейнера', link: 'declare_params.md' },
                        { text: 'Ссылки на имена параметров контейнера', link: 'expression_in_params.md' },
                        { text: 'Внедрение параметров контейнера', link: 'inject_params.md' },
                        { text: 'Параметры контейнера времени выполнения', link: 'inject_runtime_params.md' },
                    ],
                },
                {
                    text: 'Объявление объекта в рантайм контейнере',
                    link: 'runtime-definition/index.md',
                },
                {
                    base: '/documentation/factories/',
                    text: 'Внедрение зависимостей через фабрику',
                    link: 'index.md',
                    collapsed: true,
                    items: [
                        {text: 'Обзор', link: 'index.md'},
                        {text: 'Аргументы для метода фабрики', link: 'args.md'},
                        {text: 'Объявление PHP-класса', link: 'declare_class.md'},
                        {text: 'Объявление внедрения зависимости', link: 'inject_into_params.md'},
                        {text: 'Фактическое имя класса фабрики', link: 'factory_class_container_id.md'},
                    ],
                },
                {
                    text: 'Сброс контейнера',
                    link: 'container-reset/index.md',
                },
                {
                    base: '/documentation/object-resetters/',
                    text: 'Сброс состояния объектов в долго-живущих процессах',
                    link: 'index.md',
                    collapsed: true,
                    items: [
                        {text: 'Обзор', link: 'index.md'},
                        {text: 'Конфигурация из определений контейнера', link: 'definition_config.md'},
                        {text: 'Конфигурация отдельных значений сброса состояния объектов', link: 'manual_config.md'},
                        {text: 'Сброс состояния объекта через рефлексию объекта', link: 'reset_vendor_class.md'},
                    ],
                },
                {
                    base: '/documentation/lazy-injection/',
                    text: 'Внедрение «ленивых» объектов контейнером',
                    link: 'index.md',
                    collapsed: true,
                    items: [
                        {text: 'Обзор', link: 'index.md'},
                        {text: 'Объявление ленивого PHP-класса в контейнере', link: 'inject_as_lazy_global.md'},
                        {text: 'Объявление внедрения ленивого PHP-класса в параметр', link: 'inject_lazy_arg.md'},
                    ],
                },
            ]
        }
    ],
        '/cookbook/': [
        {
            base: '/cookbook/',
            text: 'Рецепты',
            collapsed: false,
            items: [
                { text: 'Обзор', link: 'index.md' },
                { text: 'Внедрение зависимостей через сеттер-методы', link: 'autowire-setup.md' },
                {
                    base: '/cookbook/inject-interface/',
                    text: 'Внедрение зависимости в параметр с типом интерфейс',
                    collapsed: true,
                    link: 'index.md',
                    items: [
                        {text: 'Обзор', link: 'index.md'},
                        {text: 'Глобальное объявление реализации интерфейса', link: 'global.md'},
                        {text: 'Объявление внедрения в конкретный параметр', link: 'arg.md'},
                    ],
                },
                { text: 'Внедрение зависимостей в параметры переменной длины', link: 'inject-variadic-params.md' },
                { text: 'Внедрение зависимостей для параметров объединенного типа', link: 'inject-union-type-params.md' },
                { text: 'Внедрение зависимостей PHP-классов реализующих интерфейс сторонних производителей', link: 'resolve-vendor-interface.md' },
            ],
        },
    ],
};
