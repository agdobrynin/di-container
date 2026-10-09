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
                link: 'index.md',
                items: [
                  {
                    base: '/documentation/container-builder/',
                    text: '👷‍♂️ Сборка контейнера зависимостей',
                    link: 'index.md',
                    collapsed: true,
                    items: [
                      { text: 'Обзор', link: 'index.md' },
                      { text: 'Установка индивидуальной конфигурации контейнера', link: 'config.md' },
                      { text: 'Файлы конфигураций', link: 'configuration_files.md' },
                      { text: 'Динамическое добавление определений в контейнер', link: 'set.md' },
                    ]
                  },
                  {
                    base: '/documentation/container-config/',
                    text: '⚙️ Конфигурация для DiContainer',
                    link: 'index.md'
                  },
                  {
                    base: '/documentation/php-definition/',
                    text: '🐘 Конфигурирование в стиле php определений',
                    link: 'index.md',
                    collapsed: true,
                    items: [
                      { text: 'Обзор', link: 'index.md' },
                      { text: 'diAutowire', link: 'di-autowire.md' },
                      { text: 'diCallable', link: 'di-callable.md' },
                      { text: 'diGet', link: 'di-get.md' },
                      { text: 'diValue', link: 'di-value.md' },
                      { text: 'diProxyClosure', link: 'di-proxy-closure.md' },
                      { text: 'diTaggedAs', link: 'di-tagged-as.md' },
                      { text: 'diFactory', link: 'di-factory.md' },
                      { text: 'diParameter', link: 'di-parameter.md' },
                      { text: 'diParameterRuntime', link: 'di-parameter-runtime.md' },
                    ]
                  },
                  {
                    base: '/documentation/attribute-definition/',
                    text: '#️⃣  Конфигурирование через PHP атрибуты',
                    link: 'index.md',
                    collapsed: true,
                    items: [
                      { text: 'Обзор', link: 'index.md' },
                      { text: 'Autowire', link: 'autowire.md' },
                      { text: 'AutowireExclude', link: 'autowire-exclude.md' },
                      { text: 'Setup', link: 'setup.md' },
                      { text: 'SetupImmutable', link: 'setup-immutable.md' },
                      { text: 'SetupPriority', link: 'setup-priority.md' },
                      { text: 'Inject', link: 'inject.md' },
                      { text: 'InjectByCallable', link: 'inject-by-callable.md' },
                      { text: 'Service', link: 'service.md' },
                      { text: 'DiFactory', link: 'di-factory.md' },
                      { text: 'ProxyClosure', link: 'proxy-closure.md' },
                      { text: 'Tag', link: 'tag.md' },
                    ]
                  },
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
                  { text: 'Внедрение по интерфейсу сторонних производителей', link: 'resolve-vendor-interface.md' },
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
