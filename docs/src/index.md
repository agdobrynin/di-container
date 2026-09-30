---
# https://vitepress.dev/reference/default-theme-home-page
layout: home
isHome: true

hero:
  name: "Kaspi/DiContainer"
  text: Контейнер внедрения зависимостей для PHP
  tagline: Реализует рекомендацию PSR-11 
  actions:
    - theme: brand
      text: Быстрый старт
      link: /overview
    - theme: alt
      text: API Examples
      link: /api-examples

features:
  - title: Autowire
    details: Автоматическое разрешение зависимостей для конструктора класса или для параметров вызываемого тип.
  - title: Lazy injection
    details: Отложенная инициализация внедряемых объектов.
  - title: Php-атрибуты
    details: Гибкое конфигурирование определений через php атрибуты.
  - title: Zero configuration
    details: Если класс не имеет зависимостей или зависит только от других конкретных классов, контейнеру не нужно указывать, как разрешить этот класс.
  - title: Поддержка тегов
    details: Получение коллекции определений и сервисов в контейнере по тегу.
  - title: Компиляция контейнера
    details: генерация настроенного контейнера в PHP-код оптимизированный специально для вашей конфигурации и ваших классов.

---


