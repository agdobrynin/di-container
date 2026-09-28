```console{3-6,11-14,17-20}
object(App\Services\FooService)#23 (1) {
  ["heavyDependency"]=>
  lazy proxy object(App\Services\HeavyDependency)#20 (0) {
    ["bazService"]=>
    uninitialized(App\Services\BazService)
  }
}

object(App\Services\BarService)#33 (1) {
  ["heavyDependency"]=>
  lazy proxy object(App\Services\HeavyDependency)#30 (0) {
    ["bazService"]=>
    uninitialized(App\Services\BazService)
  }
}

lazy proxy object(App\Services\HeavyDependency)#40 (0) {
  ["bazService"]=>
  uninitialized(App\Services\BazService)
}
```
