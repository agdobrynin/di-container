import { defineConfig } from 'vitepress'
import { groupIconMdPlugin, groupIconVitePlugin } from 'vitepress-plugin-group-icons'


// https://vitepress.dev/reference/site-config
export default defineConfig({
  lang: 'ru-RU',
  title: "kaspi/di-container",
  titleTemplate: "Dependency injection container for PHP",
  description: "Dependency injection container for PHP",
  srcDir: './src',
  srcExclude: ['**/_include/'],
  vite: {
    publicDir: '../public',
    plugins: [
      groupIconVitePlugin()
    ],
  },
  locales: {
    root: {
      label: 'Русский',
      lang: 'ru',
      link: '/',
      themeConfig: {
        outline: {
          label: 'На этой странице',
        },
        nav: [
          { text: 'Руководство', link: '/documentation/', activeMatch: '/documentation/' },
          { text: 'Рецепты', link: '/cookbook/', activeMatch: '/cookbook/' },
        ],
        sidebar: {
          '/documentation/': [
              {
                base: '/documentation/',
                text: 'Руководство',
                items: [
                  {
                    base: '/documentation/container-builder/',
                    text: 'Сборка контейнера зависимостей',
                    link: 'index.md',
                    collapsed: true,
                    items: [
                      { text: 'Обзор', link: 'index.md' },
                      { text: 'Установка индивидуальной конфигурации контейнера', link: 'config.md' },
                      { text: 'Файлы конфигураций определений контейнера', link: 'configuration_files.md' },
                      { text: 'Динамическое добавление определений в контейнер', link: 'set.md' },
                    ]
                  },
                  {
                    base: '/documentation/container-config/',
                    text: 'Конфигурация для DiContainer',
                    link: 'index.md'
                  },
                  {
                    base: '/documentation/php-definition/',
                    text: 'Настройка в конфигурационных файлах определений',
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
                      { text: 'diTaggedAs', link: 'di-tagged-as.md' },
                      { text: 'diValue', link: 'di-value.md' },
                      { text: 'diRuntime', link: 'di-runtime.md' },
                    ]
                  },
                  {
                    base: '/documentation/attribute-definition/',
                    text: 'Настройка определений через PHP атрибуты',
                    link: 'index.md',
                    collapsed: true,
                    items: [
                      { text: 'Обзор', link: 'index.md' },
                      { text: 'Autowire', link: 'autowire.md' },
                      { text: 'AutowireExclude', link: 'autowire-exclude.md' },
                      { text: 'DiFactory', link: 'di-factory.md' },
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
                      { text: 'DiRuntime', link: 'di-runtime.md' },
                    ]
                  },
                  {
                    base: '/documentation/call/',
                    text: 'Метод контейнера для вызываемых типов',
                    link: 'index.md',
                  },
                  {
                    base: '/documentation/tags/',
                    text: 'Работа с тегами в контейнере',
                    link: 'index.md',
                    collapsed: true,
                    items: [
                      { text: 'Обзор', link: 'index.md' },
                      { text: 'Настройка тегов в файлах конфигураций', link: 'php-definition-tag.md' },
                      { text: 'Настройка тегов через php атрибут', link: 'attribute-definition-tag.md' },
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
                          { text: '«Метод-приоритета»', link: 'priority-by-method.md' },
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
                      { text: 'Объявление «параметров контейнера»', link: 'declare_params.md' },
                      { text: 'Ссылки на имена «параметров контейнера»', link: 'expression_in_params.md' },
                      { text: 'Внедрение «параметров контейнера»', link: 'inject_params.md' },
                      { text: '«Параметры контейнера» времени выполнения', link: 'inject_runtime_params.md' },
                    ],
                  },
                  {
                    base: '/documentation/runtime-definition/',
                    text: 'Внедрение объекта в рантайм контейнер',
                    link: 'index.md',
                  }
                ]
              }
          ],
          '/cookbook/': [
              {
                base: '/cookbook/',
                text: 'Рецепты',
                collapsed: true,
                items: [
                  { text: 'Внедрение зависимостей через сеттер-методы', link: 'autowire-setup.md' },
                  { text: 'Внедрение зависимости по интерфейсу', link: 'inject-interface.md' },
                  { text: 'Внедрение зависимостей в параметры переменной длины', link: 'inject-variadic-params.md' },
                  { text: 'Внедрение зависимостей для параметров объединенного типа', link: 'inject-union-type-params.md' },
                  { text: 'Внедрение зависимостей по интерфейсу сторонних производителей', link: 'resolve-vendor-interface.md' },
                  { text: 'Внедрение коллекции по интерфейсу', link: 'tagged-as-interface.md' },
                ],
              },
          ],
        }
      },
      markdown: {
        config(md) {
          md.use(groupIconMdPlugin, {
            titleBar: { includeSnippet: true },
          })
        },
        container: {
          noteLabel: 'Замечание',
          tipLabel: 'Подсказка',
          importantLabel: 'Важно',
          warningLabel: 'Предупреждение'
          // ...остальные метки, а также заголовки `customContainers`
        },
        codeCopyButton: {
          tooltipText: 'Копировать код',
          copiedText: 'Скопировано'
        }
      }
    },
    en: {
      label: 'English',
      lang: 'en',
      link: '/en/',
      themeConfig: {
        nav: [
          { text: 'Documentation', link: '/en/documentation/' },
          { text: 'Cookbook', link: '/en/cookbook/' },
        ],
        sidebar: {
          '/en/documentation/': [{
            base: '/en/documentation/',
            text: 'Building the dependency container',
            items: [
              { text: 'Overview', link: 'container-builder' },
              { text: 'Configuration files', link: 'container-builder/configuration_files' },
            ]
          }],
          '/cookbook/': [{
            base: '/cookbook/',
            text: 'Cookbook',
            items: [
              { text: 'Overview', link: '/php-definition' },
            ]
          }],
        }
      },
      markdown: {
        config(md) {
          md.use(groupIconMdPlugin)
        },
      }
    },
  },
  themeConfig: {
    // https://vitepress.dev/reference/default-theme-config
    logo: '/logo_optimize.svg',
    base: '/di-container/',
    socialLinks: [
      { icon: 'github', link: 'https://github.com/agdobrynin/di-container', ariaLabel: 'Git Hub' },
      { icon: 'packagist', link: 'https://packagist.org/packages/kaspi/di-container', ariaLabel: 'Packagist' },
    ],
    head: [
      ['link', { rel: 'icon', href: '/favicon.ico' }]
    ]
  }
})
