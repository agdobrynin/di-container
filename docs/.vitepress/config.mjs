import { defineVersionedConfig } from '@viteplus/versions'
import { groupIconMdPlugin, groupIconVitePlugin } from 'vitepress-plugin-group-icons'

import sidebar_ru_v47X from './sidebar_ru_v4.7.x.mjs'
import sidebar_en_v47X from './sidebar_en_v4.7.x.mjs'
import search_ru from './search_ru.mjs'

export default defineVersionedConfig({
  base: '/di-container/',
  lang: 'ru-RU',
  title: "kaspi/di-container",
  titleTemplate: "Dependency injection container for PHP",
  description: "Dependency injection container for PHP",
  srcExclude: ['**/_include/', '**/_code/'],
  vite: {
    plugins: [
      groupIconVitePlugin()
    ],
  },
  versionsConfig: {
    current: 'v4.7.x',
    versionSwitcher: false,
  },
  locales: {
    root: {
      label: 'Русский',
      lang: 'ru',
      link: '/',
      themeConfig: {
        docFooter: {
          prev: 'Предыдущая страница',
          next: 'Следующая страница'
        },
        outline: {
          label: 'На этой странице',
        },
        nav: [
          { component: 'VersionSwitcher', props: { label: 'Версия', latestLabel: 'последняя' } },
          { text: 'Руководство', link: '/documentation/', activeMatch: '/documentation/' },
          { text: 'Рецепты', link: '/cookbook/', activeMatch: '/cookbook/' },
        ],
        editLink: {
          text: 'Предложите изменения для этой страницы',
        },
        sidebar: {...sidebar_ru_v47X},
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
          { text: 'Manual', link: '/documentation/' },
          { text: 'Cookbook', link: '/cookbook/' },
          { component: 'VersionSwitcher' },
        ],
        sidebar: {...sidebar_en_v47X},
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
    socialLinks: [
      { icon: 'github', link: 'https://github.com/agdobrynin/di-container', ariaLabel: 'Git Hub' },
      { icon: 'packagist', link: 'https://packagist.org/packages/kaspi/di-container', ariaLabel: 'Packagist' },
    ],
    head: [
      ['link', { rel: 'icon', href: '/favicon.ico' }]
    ],
    search: {
      provider: 'local',
      options: {
        locales: {
          root: {
            translations: search_ru
          },
          'v4.7.x': {
            translations: search_ru
          },
        },
      },
    },
    editLink: {
      pattern: ({ filePath, relativePath }) => {
        const owner = 'agdobrynin';
        const repo = 'di-container';
        const branch = 'docs';

        // GitHub blob link to the specific file
        const fileUrl = `https://github.com/${ owner }/${ repo }/blob/${ branch }/docs/${ filePath }`;

        // Pre-filled issue parameters
        const title = encodeURIComponent(`Suggestion for: ${ relativePath }`);
        const body = encodeURIComponent(
            '### Suggested changes\n\n' +
            `File: [${ relativePath }](${ fileUrl })\n\n` +
            '---\n\n' +
            '**Describe your suggestion:**\n\n'
        );
        const labels = encodeURIComponent('enhancement,documentation');

        return `https://github.com/${ owner }/${ repo }/issues/new?title=${ title }&body=${ body }&labels=${ labels }`;
      },

      text: 'Suggest changes to this page'
    }
  }
})
