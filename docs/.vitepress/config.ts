/**
 * Type-only imports erased during TypeScript compilation.
 */
import type {MarkdownRenderer, PageData} from 'vitepress';

/**
 * Imports
 */
import { defineVersionedConfig } from '@viteplus/versions'
import { groupIconMdPlugin, groupIconVitePlugin } from 'vitepress-plugin-group-icons'
import { fileURLToPath, URL } from 'node:url'

/**
 * Configuration files
 */
import sidebar_ru_v48X from './sidebars/v4.8.x/sidebar_ru_v4.8.x.mjs'
import sidebar_en_v48X from './sidebars/v4.8.x/sidebar_en_v4.8.x.mjs'
import sidebar_ru_v47X from './sidebars/v4.7.x/sidebar_ru_v4.7.x.mjs'
import sidebar_en_v47X from './sidebars/v4.7.x/sidebar_en_v4.7.x.mjs'
import search_ru from './search_ru.mjs'

export default defineVersionedConfig({
  base: '/di-container/',
  lang: 'ru-RU',
  title: 'kaspi/di-container',
  titleTemplate: 'Dependency injection container for PHP',
  description: 'Dependency injection container for PHP',
  lastUpdated: true,
  head: [
    ['link', {rel: 'icon', href: '/di-container/favicon.ico'}],
  ],
  srcExclude: ['**/_include/', '**/_code/'],
  vite: {
    // @ts-ignore
    plugins: [
      groupIconVitePlugin()
    ],
    resolve: {
      alias: {
        '@vp': fileURLToPath(new URL('.', import.meta.url))
      }
    }
  },
  markdown: {
    config(md: MarkdownRenderer) {
      md.use(groupIconMdPlugin, {
        titleBar: { includeSnippet: true },
      })
    },
  },
  themeConfig: {
    logo: '/logo_optimize.svg',
    socialLinks: [
      { icon: 'github', link: 'https://github.com/agdobrynin/di-container', ariaLabel: 'Git Hub' },
      { icon: 'packagist', link: 'https://packagist.org/packages/kaspi/di-container', ariaLabel: 'Packagist' },
    ],
    search: {
      provider: 'local',
      options: {
        locales: {
          root: {
            translations: search_ru
          },
          'v4.8.x': {
            translations: search_ru
          },
          'v4.7.x': {
            translations: search_ru
          },
        },
      },
    },
    editLink: {
      pattern: ({ filePath, relativePath }: PageData) => {
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
    },
    lastUpdated: {
      formatOptions: {
        dateStyle: 'medium',
        timeStyle: 'short',
        forceLocale: true,
      }
    }
  },
  versionsConfig: {
    current: 'v4.8.x',
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
          { text: 'Руководство', link: '/documentation/', activeMatch: '/documentation/' },
          { text: 'Рецепты', link: '/cookbook/', activeMatch: '/cookbook/' },
        ],
        editLink: {
          text: 'Предложите изменения для этой страницы',
        },
        lastUpdated: {
          text: 'Обновлено',
        },
        sidebar: {...sidebar_ru_v48X ,...sidebar_ru_v47X},
      },
      // @ts-ignore
      markdown: {
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
        sidebar: {...sidebar_en_v48X, ...sidebar_en_v47X},
      },
    },
  },
})
