---
# https://vitepress.dev/reference/default-theme-home-page
layout: home
isHome: true

hero:
    name: kaspi/di-container
    text: Контейнер внедрения зависимостей для PHP 8.1 - 8.5
    tagline: Легковесная кодовая база без внешних зависимостей и лишнего «мусора».
    image:
        src: /logo_optimize.svg
        alt: kaspi/di-container
    actions:
      - theme: brand
        text: Руководство
        link: documentation/
      - theme: alt
        text: Рецепты
        link: cookbook/
      - theme: alt
        text: GitHub
        link: https://github.com/agdobrynin/di-container
features:
    - title: Autowiring & Zero configuration
      details: Автоматическое внедрение зависимостей на основе указанной конфигурации. Если класс не имеет зависимостей или зависит только от других конкретных классов указывать конфигурацию не нужно.
      icon: 🧊
    - title: Hi performance
      details: Внедряет объекты как ленивые зависимости. Компиляция контейнера в высоко производительный PHP-код оптимизированный специально для вашей конфигурации.
      icon: ⚡
    - title: Attribute-driven & PSR-11
      details: Точечное объявление внедрений зависимостей с помощью нативных атрибутов PHP 8+. Совместим с рекомендацией PSR-11 для контейнера внедрения зависимостей.
      icon:  #️⃣ 
