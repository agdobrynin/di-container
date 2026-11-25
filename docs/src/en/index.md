---
# https://vitepress.dev/reference/default-theme-home-page
layout: home
isHome: true

hero:
    name: kaspi/di-container
    text: Dependency injection container for PHP 8.1 – 8.5
    tagline: A lightweight codebase with no external dependencies or unnecessary «clutter».
    image:
        src: /logo_optimize.svg
        alt: kaspi/di-container
    actions:
      - theme: brand
        text: Manual
        link: documentation/
      - theme: alt
        text: Cookbook
        link: cookbook/
      - theme: alt
        text: GitHub
        link: https://github.com/agdobrynin/di-container
features:
    - title: Autowiring & Zero configuration
      details: Automatic dependency injection based on a specified configuration. If a class has no dependencies, or depends only on other concrete classes, no configuration is required.
      icon: 🧊
    - title: Hi performance
      details: Injects objects as lazy dependencies. Compilation of the container into high-performance PHP code, optimized specifically for your configuration.
      icon: ⚡
    - title: Attribute-driven & PSR-11
      details: Declarative dependency injection using native PHP 8+ attributes. Compatible with the PSR-11 recommendation for dependency injection containers.
      icon:  #️⃣ 
