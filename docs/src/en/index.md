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
    - title: Lazy object & Tags
      details: Injects objects as lazy dependencies. Tags provide the ability to embed or retrieve a collection of elements from a container tagged with the same name.
      icon: 💤
    - title: Attribute-driven & PSR-11
      details: Declarative dependency injection using native PHP 8+ attributes. Compatible with the PSR-11 recommendation for dependency injection containers.
      icon:  #️⃣ 
    - title: Hi performance
      details: Compilation of the container into high-performance PHP code, optimized specifically for your configuration. A mechanism for resetting the state of objects in long-running processes.
      icon: 💎
