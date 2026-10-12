// https://vitepress.dev/guide/custom-theme
import { useRoute } from 'vitepress'
import { h, watch, onMounted, nextTick } from 'vue'
import DefaultTheme from 'vitepress/theme'
import { VPButton } from 'vitepress/theme'
import './style.css'
import 'virtual:group-icons.css'

/** @type {import('vitepress').Theme} */
export default {
  extends: DefaultTheme,
  Layout: () => {
    return h(DefaultTheme.Layout, null, {
      // https://vitepress.dev/guide/extending-default-theme#layout-slots
    })
  },
  enhanceApp({ app, router, siteData }) {
    app.component('VPButton', VPButton);
  },
  setup() {
    const route = useRoute()
    
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
    }
    
    // Срабатывает при первой загрузке страницы
    onMounted(async () => {
      await nextTick()
      scrollToActiveSidebarItem()
    })
    
    // Срабатывает при переходах между страницами
    watch(
        () => route.path,
        async () => {
          await nextTick()
          scrollToActiveSidebarItem()
        }
    )
  }
}
