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
    - title: Autowire
      details: Автоматическое внедрение зависимостей для конструктора класса и параметров вызываемого типа.
      icon: 🛠️
    - title: Lazy injection
      details: Отложенная инициализация внедряемых объектов.
      icon: 💤
    - title: Attribute-driven
      details: Контейнер можно настроить с помощью нативных атрибутов PHP 8+.
      icon:  #️⃣ 
    - title: Zero configuration
      details: Если класс не имеет зависимостей или зависит только от других конкретных классов, контейнеру не нужно указывать, как внедрить этот класс.
      icon: 🧊
    - title: Definition Tags
      details: Получение коллекции определений и сервисов в контейнере по тегу.
      icon: 🏷️
    - title: Compiling the Container
      details: Генерация настроенного контейнера в PHP-код оптимизированный специально для вашей конфигурации и ваших классов.
      icon: 📦

