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
    - title: Autowire
      details: Automatic dependency injection for parameters of class constructor and callable type.
      icon: 🛠️
    - title: Lazy injection
      details: Lazy initialization of injected objects.
      icon: 💤
    - title: Attribute-driven
      details: The container can be configured using native PHP 8+ attributes.
      icon:  #️⃣ 
    - title: Zero configuration
      details: If a class has no dependencies or depends only on other concrete classes, the container does not need to be told how to instantiate that class.
      icon: 🧊
    - title: Definition Tags
      details: Retrieving a collection of definitions and services from a container by tag.
      icon: 🏷️
    - title: Compiling the Container
      details: Generating a configured container into PHP code, optimized specifically for your configuration and your classes.
      icon: 📦

