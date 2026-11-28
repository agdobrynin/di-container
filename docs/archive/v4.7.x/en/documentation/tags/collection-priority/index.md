---
aside: false
title: Priority of collection elements
---
### Priority of collection elements { #title }

The priority of collection elements refers to the order in which they are positioned within the resulting collection. Priority values can be of type `int`, `string`, or `null`.

To sort the elements of a collection, the priority values of each element are compared. Strings are compared as sequences of bytes.

**The higher the priority value, the higher the position of the service within the collection.**

Determining the priority of a collection element (in order of significance):

1. [Tag priority](priority-by-tag.md).
2. [Tag "priority method"](priority-by-method.md).
3. [Default "priority method"](priority-by-method-default.md).
