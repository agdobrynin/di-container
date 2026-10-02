import { defineConfig } from 'vitepress'
import { groupIconMdPlugin, groupIconVitePlugin } from 'vitepress-plugin-group-icons'


// https://vitepress.dev/reference/site-config
export default defineConfig({
  lang: 'ru-RU',
  title: "kaspi/di-container",
  titleTemplate: "Dependency injection container for PHP",
  description: "Dependency injection container for PHP",
  srcDir: './src',
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
          { text: 'Рецепты', link: '/cookbook/' },
        ],
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
    logo: '/logo.svg',
    base: '/di-container/',
    socialLinks: [
      { icon: 'github', link: 'https://github.com/agdobrynin/di-container' }
    ],
    outline: [2, 4],
  }
})
