import { defineConfig } from 'vitepress'

// https://vitepress.dev/reference/site-config
export default defineConfig({
  lang: 'ru-RU',
  title: "Kaspi/DiContainer",
  description: "Dependency injection container for PHP",
  srcDir: './src',
  locales: {
    root: {
      label: 'Русский',
      lang: 'ru',
      link: '/',
      markdown: {
        container: {
          noteLabel: 'Замечание',
          tipLabel: 'Подсказка',
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
