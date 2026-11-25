import type { VNode } from '@vue/runtime-core'
import type { Awaitable, Theme } from 'vitepress'

import DefaultTheme from 'vitepress/theme'
import { VPButton } from 'vitepress/theme'
import { useRoute } from 'vitepress';
import { onMounted, nextTick, watch, h } from 'vue'
// @ts-ignore
import VersionSwitcher from '@viteplus/versions/components/version-switcher.component.vue'
import { installVersionFallback } from '@viteplus/versions/fallback'

// @ts-ignore
import './style.css'
// @ts-ignore
import 'virtual:group-icons.css'

export default {
    extends: DefaultTheme,

    Layout: (): VNode => {
        return h(DefaultTheme.Layout, null, {
        });
    },

    enhanceApp({ app, router, siteData }): Awaitable<void> {
        app.component('VersionSwitcher', VersionSwitcher);
        installVersionFallback(router, siteData);
        app.component('VPButton', VPButton);
    },

    setup() {
        const route = useRoute();
        // Функция для прокрутки к активному пункту меню
        const scrollToActiveSidebarItem = () => {
            // Ищем активную ссылку в сайдбаре VitePress
            const activeLink = document.querySelector(
                '#VPSidebarNav .is-link.is-active'
            )

            if (activeLink) {
                activeLink.scrollIntoView({
                    behavior: 'smooth', // Плавная прокрутка
                    block: 'center',
                })
            }
        };

        // Срабатывает при первой загрузке страницы
        onMounted(async () => {
            await nextTick()
            scrollToActiveSidebarItem()
        });

        // Срабатывает при переходах между страницами
        watch(
            () => route.path,
            async () => {
                await nextTick()
                scrollToActiveSidebarItem()
            }
        );
    },
} satisfies Theme
