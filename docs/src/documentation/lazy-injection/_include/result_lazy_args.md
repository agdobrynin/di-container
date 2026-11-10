```console{1}
object(App\Services\FooService)#23 (1) {
  ["heavyDependency"]=>
  lazy proxy object(App\Services\HeavyDependency)#20 (0) {
    ["bazService"]=>
    uninitialized(App\Services\BazService)
  }
}

object(App\Services\BarService)#33 (1) {
  ["heavyDependency"]=>
  object(App\Services\HeavyDependency)#30 (1) {
    ["bazService"]=>
    object(App\Services\BazService)#27 (0) {
    }
  }
}

object(App\Services\HeavyDependency)#40 (0) {
  ["bazService"]=>
  object(App\Services\BazService)#37 {
  }
}
```
