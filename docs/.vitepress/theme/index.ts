import type { Theme } from 'vitepress'
import DefaultTheme from 'vitepress/theme'
import { VPButton } from 'vitepress/theme'
import { useRoute } from 'vitepress';
import { onMounted, nextTick, watch } from 'vue';

import './style.css'
import 'virtual:group-icons.css'

export default {
    extends: DefaultTheme,
    enhanceApp({ app }) {
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
