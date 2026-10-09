# Внедрение по интерфейсу сторонних производителей

## Обзор { #overview }

Для глобального конфигурирования внедрения по интерфейсу от сторонних производителей, например из других PHP пакетов, 
нужно указать контейнеру какой PHP класс будет реализацией этого интерфейса. Это можно сделать используя методы [DiContainerBuilder::load()](../container-builder/index.md#load-definitions-from-file) или [DiContainerBuilder::addDefinitions()](../container-builder/index.md#load-definitions-from-collection) при сборке контейнера.

🤔 PsrLogger ?